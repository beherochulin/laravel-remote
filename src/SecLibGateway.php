<?php
namespace Collective\Remote;

use Illuminate\Filesystem\Filesystem;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;
use phpseclib\System\SSH\Agent;

class SecLibGateway implements GatewayInterface {
    protected $host;
    protected $port = 22;
    protected $timeout = 10;
    protected $auth;
    protected $files;
    protected $connection;

    public function __construct($host, array $auth, Filesystem $files, $timeout) {
        $this->auth = $auth;
        $this->files = $files;
        $this->setHostAndPort($host);
        $this->setTimeout($timeout);
    }
    protected function setHostAndPort($host) {
        if (!str_contains($host, ':')) {
            $this->host = $host;
        } else {
            list($this->host, $this->port) = explode(':', $host);

            $this->port = (int) $this->port;
        }
    }
    public function connect($username) {
        return $this->getConnection()->login($username, $this->getAuthForLogin());
    }
    public function getConnection() {
        if ($this->connection) {
            return $this->connection;
        }

        return $this->connection = new SFTP($this->host, $this->port, $this->timeout);
    }
    protected function getAuthForLogin() {
        if ($this->useAgent()) {
            return $this->getAgent();
        }

        // If a "key" was specified in the auth credentials, we will load it into a
        // secure RSA key instance, which will be used to connect to the servers
        // in place of a password, and avoids the developer specifying a pass.
        elseif ($this->hasRsaKey()) {
            return $this->loadRsaKey($this->auth);
        }

        // If a plain password was set on the auth credentials, we will just return
        // that as it can be used to connect to the server. This will be used if
        // there is no RSA key and it gets specified in the credential arrays.
        elseif (isset($this->auth['password'])) {
            return $this->auth['password'];
        }

        throw new \InvalidArgumentException('Password / key is required.');
    }
    protected function useAgent() {
        return isset($this->auth['agent']) && $this->auth['agent'] === true;
    }
    public function getAgent() {
        return new Agent();
    }
    protected function hasRsaKey() {
        $hasKey = (isset($this->auth['key']) && trim($this->auth['key']) != '');

        return $hasKey || (isset($this->auth['keytext']) && trim($this->auth['keytext']) != '');
    }
    protected function loadRsaKey(array $auth) {
        with($key = $this->getKey($auth))->loadKey($this->readRsaKey($auth));

        return $key;
    }
    protected function getKey(array $auth) {
        with($key = $this->getNewKey())->setPassword(array_get($auth, 'keyphrase'));

        return $key;
    }
    public function getNewKey() {
        return new RSA();
    }
    protected function readRsaKey(array $auth) {
        if (isset($auth['key'])) {
            return $this->files->get($auth['key']);
        }

        return $auth['keytext'];
    }
    public function getTimeout() {
        return $this->timeout;
    }
    protected function setTimeout($timeout) {
        $this->timeout = (int) $timeout;
    }
    public function connected() {
        return $this->getConnection()->isConnected();
    }
    public function run($command) {
        $this->getConnection()->exec($command, false);
    }
    public function get($remote, $local) {
        $this->getConnection()->get($remote, $local);
    }
    public function getString($remote) {
        return $this->getConnection()->get($remote);
    }
    public function put($local, $remote) {
        $this->getConnection()->put($remote, $local, SFTP::SOURCE_LOCAL_FILE);
    }
    public function putString($remote, $contents) {
        $this->getConnection()->put($remote, $contents);
    }
    public function exists($remote) {
        return $this->getConnection()->file_exists($remote);
    }
    public function rename($remote, $newRemote) {
        return $this->getConnection()->rename($remote, $newRemote);
    }
    public function delete($remote) {
        return $this->getConnection()->delete($remote);
    }
    public function nextLine() {
        $value = $this->getConnection()->_get_channel_packet(SSH2::CHANNEL_EXEC);

        return $value === true ? null : $value;
    }
    public function status() {
        return $this->getConnection()->getExitStatus();
    }
    public function getHost() {
        return $this->host;
    }
    public function getPort() {
        return $this->port;
    }
}

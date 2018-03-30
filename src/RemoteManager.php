<?php
namespace Collective\Remote;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;

class RemoteManager {
    protected $app;

    public function __construct(Container $app) {
        $this->app = $app;
    }
    public function into($name) {
        if (is_string($name) || is_array($name)) {
            return $this->connection($name);
        } else {
            return $this->connection(func_get_args());
        }
    }
    public function connection($name = null) {
        if (is_array($name)) {
            return $this->multiple($name);
        }

        return $this->resolve($name ?: $this->getDefaultConnection());
    }
    public function connect($config) {
        return $this->makeConnection($config['host'], $config);
    }
    public function multiple(array $names) {
        return new MultiConnection(array_map([$this, 'resolve'], $names));
    }
    public function resolve($name) {
        return $this->makeConnection($name, $this->getConfig($name));
    }
    protected function makeConnection($name, array $config) {
        $timeout = isset($config['timeout']) ? $config['timeout'] : 10;

        $this->setOutput($connection = new Connection(

            $name, $config['host'], $config['username'], $this->getAuth($config), null, $timeout

        ));

        return $connection;
    }
    protected function setOutput(Connection $connection) {
        $output = php_sapi_name() == 'cli' ? new ConsoleOutput() : new NullOutput();

        $connection->setOutput($output);
    }
    protected function getAuth(array $config) {
        if (isset($config['agent']) && $config['agent'] === true) {
            return ['agent' => true];
        } elseif (isset($config['key']) && trim($config['key']) != '') {
            return ['key' => $config['key'], 'keyphrase' => $config['keyphrase']];
        } elseif (isset($config['keytext']) && trim($config['keytext']) != '') {
            return ['keytext' => $config['keytext']];
        } elseif (isset($config['password'])) {
            return ['password' => $config['password']];
        }

        throw new \InvalidArgumentException('Password / key is required.');
    }
    protected function getConfig($name) {
        $config = $this->app['config']['remote.connections.'.$name];

        if (!is_null($config)) {
            return $config;
        }

        throw new \InvalidArgumentException("Remote connection [$name] not defined.");
    }
    public function getDefaultConnection() {
        return $this->app['config']['remote.default'];
    }
    public function group($name) {
        return $this->connection($this->app['config']['remote.groups.'.$name]);
    }
    public function setDefaultConnection($name) {
        $this->app['config']['remote.default'] = $name;
    }
    public function __call($method, $parameters) {
        return call_user_func_array([$this->connection(), $method], $parameters);
    }
}

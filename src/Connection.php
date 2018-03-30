<?php
namespace Collective\Remote;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Connection implements ConnectionInterface {
    protected $gateway;
    protected $name;
    protected $host;
    protected $username;
    protected $tasks = [];
    protected $output;

    public function __construct($name, $host, $username, array $auth, GatewayInterface $gateway = null, $timeout = 10) {
        $this->name = $name;
        $this->host = $host;
        $this->username = $username;
        $this->gateway = $gateway ?: new SecLibGateway($host, $auth, new Filesystem(), $timeout);
    }
    public function define($task, $commands) {
        $this->tasks[$task] = $commands;

        return $this;
    }
    public function task($task, Closure $callback = null) {
        if (isset($this->tasks[$task])) {
            $this->run($this->tasks[$task], $callback);
        }
    }
    public function run($commands, Closure $callback = null) {
        // First, we will initialize the SSH gateway, and then format the commands so
        // they can be run. Once we have the commands formatted and the server is
        // ready to go we will just fire off these commands against the server.
        $gateway = $this->getGateway();

        $callback = $this->getCallback($callback);

        $gateway->run($this->formatCommands($commands));

        // After running the commands against the server, we will continue to ask for
        // the next line of output that is available, and write it them out using
        // our callback. Once we hit the end of output, we'll bail out of here.
        while (true) {
            if (is_null($line = $gateway->nextLine())) {
                break;
            }

            call_user_func($callback, $line, $this);
        }
    }
    public function getGateway() {
        if (!$this->gateway->connected() && !$this->gateway->connect($this->username)) {
            throw new \RuntimeException('Unable to connect to remote server.');
        }

        return $this->gateway;
    }
    protected function getCallback($callback) {
        if (!is_null($callback)) {
            return $callback;
        }

        return function ($line) {
            $this->display($line);
        };
    }
    public function display($line) {
        $server = $this->username.'@'.$this->host;

        $lead = '<comment>['.$server.']</comment> <info>('.$this->name.')</info>';

        $this->getOutput()->writeln($lead.' '.$line);
    }
    public function getOutput() {
        if (is_null($this->output)) {
            $this->output = new NullOutput();
        }

        return $this->output;
    }
    public function setOutput(OutputInterface $output) {
        $this->output = $output;
    }
    protected function formatCommands($commands) {
        return is_array($commands) ? implode(' && ', $commands) : $commands;
    }
    public function get($remote, $local) {
        $this->getGateway()->get($remote, $local);
    }
    public function getString($remote) {
        return $this->getGateway()->getString($remote);
    }
    public function put($local, $remote) {
        $this->getGateway()->put($local, $remote);
    }
    public function putString($remote, $contents) {
        $this->getGateway()->putString($remote, $contents);
    }
    public function exists($remote) {
        return $this->getGateway()->exists($remote);
    }
    public function rename($remote, $newRemote) {
        return $this->getGateway()->rename($remote, $newRemote);
    }
    public function delete($remote) {
        return $this->getGateway()->delete($remote);
    }
    public function status() {
        return $this->gateway->status();
    }
}

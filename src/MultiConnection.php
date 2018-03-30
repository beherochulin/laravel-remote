<?php
namespace Collective\Remote;

use Closure;

class MultiConnection implements ConnectionInterface {
    protected $connections;

    public function __construct(array $connections) {
        $this->connections = $connections;
    }
    public function define($task, $commands) {
        foreach ($this->connections as $connection) {
            $connection->define($task, $commands);
        }
    }
    public function task($task, Closure $callback = null) {
        foreach ($this->connections as $connection) {
            $connection->task($task, $callback);
        }
    }
    public function run($commands, Closure $callback = null) {
        foreach ($this->connections as $connection) {
            $connection->run($commands, $callback);
        }
    }
    public function get($remote, $local) {
        foreach ($this->connections as $connection) {
            $connection->get($remote, $local);
        }
    }
    public function getString($remote) {
        foreach ($this->connections as $connection) {
            $connection->getString($remote);
        }
    }
    public function put($local, $remote) {
        foreach ($this->connections as $connection) {
            $connection->put($local, $remote);
        }
    }
    public function putString($remote, $contents) {
        foreach ($this->connections as $connection) {
            $connection->putString($remote, $contents);
        }
    }
    public function exists($remote) {
        foreach ($this->connections as $connection) {
            $connection->exists($remote);
        }
    }
    public function rename($remote, $newRemote) {
        foreach ($this->connections as $connection) {
            $connection->rename($remote, $newRemote);
        }
    }
    public function delete($remote) {
        foreach ($this->connections as $connection) {
            $connection->delete($remote);
        }
    }
}

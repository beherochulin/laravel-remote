<?php
namespace Collective\Remote;

use Closure;

interface ConnectionInterface {
    public function define($task, $commands);
    public function task($task, Closure $callback = null);
    public function run($commands, Closure $callback = null);
    public function get($remote, $local);
    public function getString($remote);
    public function put($local, $remote);
    public function putString($remote, $contents);
    public function exists($remote);
    public function rename($remote, $newRemote);
    public function delete($remote);
}

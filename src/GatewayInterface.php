<?php
namespace Collective\Remote;

interface GatewayInterface {
    public function connect($username);
    public function connected();
    public function run($command);
    public function get($remote, $local);
    public function getString($remote);
    public function put($local, $remote);
    public function putString($remote, $contents);
    public function exists($remote);
    public function rename($remote, $newRemote);
    public function delete($remote);
    public function nextLine();
    public function status();
}

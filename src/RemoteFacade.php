<?php
namespace Collective\Remote;

use Illuminate\Support\Facades\Facade;

class RemoteFacade extends Facade {
    protected static function getFacadeAccessor() {
        return 'remote';
    }
}

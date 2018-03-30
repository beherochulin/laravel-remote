<?php
namespace Collective\Remote;

use Collective\Remote\Console\TailCommand;
use Illuminate\Support\ServiceProvider;

class RemoteServiceProvider extends ServiceProvider {
    protected $commands = [
        'Tail' => 'command.tail',
    ];
    protected $defer = true;

    public function boot() {
        if (!$this->isLumen()) {
            $this->publishes([
              __DIR__.'/../config/remote.php' => config_path('remote.php'),
            ]);
        }

        $this->registerCommands();
    }
    protected function isLumen() {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
    public function register() {
        $this->app->singleton('remote', function ($app) {
            return new RemoteManager($app);
        });
    }
    protected function registerCommands() {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";
            call_user_func_array([$this, $method], []);
        }
        $this->commands(array_values($this->commands));
    }
    protected function registerTailCommand() {
        $this->app->singleton('command.tail', function ($app) {
            return new TailCommand();
        });
    }
    public function provides() {
        return ['remote'];
    }
}

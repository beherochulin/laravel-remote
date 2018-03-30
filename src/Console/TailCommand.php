<?php
namespace Collective\Remote\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class TailCommand extends Command {
    protected $name = 'tail';
    protected $description = 'Tail a log file on a remote server';
    
    public function fire() {
        $path = $this->getPath($this->argument('connection'));

        if ($path) {
            $this->tailLogFile($path, $this->argument('connection'));
        } else {
            $this->error('Could not determine path to log file.');
        }
    }
    protected function tailLogFile($path, $connection) {
        if (is_null($connection)) {
            $this->tailLocalLogs($path);
        } else {
            $this->tailRemoteLogs($path, $connection);
        }
    }
    protected function tailLocalLogs($path) {
        $path = $this->findNewestLocalLogfile($path);

        $output = $this->output;

        $lines = $this->option('lines');

        ( new Process('tail -f -n '.$lines.' '.escapeshellarg($path)) )->setTimeout(null)->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        return $path;
    }
    protected function tailRemoteLogs($path, $connection) {
        $out = $this->output;

        $lines = $this->option('lines');

        $this->getRemote($connection)->run('cd '.escapeshellarg($path).' && tail -f $(ls -t | head -n 1) -n '.$lines, function ($line) use ($out) {
            $out->write($line);
        });
    }
    protected function findNewestLocalLogfile($path) {
        $files = glob($path.'/*.log');

        $files = array_combine($files, array_map('filemtime', $files));

        arsort($files);

        $newestLogFile = key($files);

        return $newestLogFile;
    }
    protected function getRemote($connection) {
        return $this->laravel['remote']->connection($connection);
    }
    protected function getPath($connection) {
        if ($this->option('path')) {
            return $this->option('path');
        }

        if (is_null($connection) && $this->option('path')) {
            return storage_path($this->option('path'));
        } elseif (is_null($connection) && !$this->option('path')) {
            return storage_path('logs');
        }

        return $this->getRoot($connection).str_replace(base_path(), '', storage_path('logs'));
    }
    protected function getRoot($connection) {
        return $this->laravel['config']['remote.connections.'.$connection.'.root'];
    }
    protected function getArguments() {
        return [
            ['connection', InputArgument::OPTIONAL, 'The remote connection name'],
        ];
    }
    protected function getOptions() {
        return [
            ['path', null, InputOption::VALUE_OPTIONAL, 'The fully qualified path to the log file.'],
            ['lines', null, InputOption::VALUE_OPTIONAL, 'The number of lines to tail.', 20],
        ];
    }
}

<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Concerns;

use Closure;
use Illuminate\Console\Command;

trait HandlesLogging
{
    protected array $loggers = [];

    protected bool $sublog = false;

    /**
     * @return $this
     */
    public function addLogger($closure)
    {
        $this->loggers[] = $closure;

        return $this;
    }

    public function addCommandLogger(Command $command)
    {
        $this->addLogger(function ($message, $level = 'info') use ($command) {
            if ($level === 'warning') {
                $command->warn($message);
            } else {
                $command->info($message);
            }
        });
    }

    public function log($message)
    {
        $this->write($message, 'info');
    }

    public function warning($message)
    {
        $this->write($message, 'warning');
    }

    protected function write($message, $level)
    {
        foreach ($this->loggers as $logger) {
            $logger(($this->sublog ? '          ↳' : '[Sidecar]') . " $message", $level);
        }
    }

    /**
     * @return mixed|Closure
     */
    public function sublog($callback)
    {
        $cached = $this->sublog;

        $undo = function () use ($cached) {
            $this->sublog = $cached;
        };

        $this->sublog = true;

        if ($callback) {
            $result = $callback();
            $undo();

            return $result;
        }

        return $undo;
    }
}

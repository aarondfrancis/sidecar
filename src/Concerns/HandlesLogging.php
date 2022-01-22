<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Concerns;

use Closure;
use Illuminate\Console\Command;

trait HandlesLogging
{
    /**
     * @var array
     */
    protected $loggers = [];

    /**
     * @var bool
     */
    protected $sublog = false;

    /**
     * @param $closure
     * @return $this
     */
    public function addLogger($closure)
    {
        $this->loggers[] = $closure;

        return $this;
    }

    /**
     * @param  Command  $command
     */
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

    /**
     * @param $message
     */
    public function log($message)
    {
        $this->write($message, 'info');
    }

    /**
     * @param $message
     */
    public function warning($message)
    {
        $this->write($message, 'warning');
    }

    /**
     * @param $message
     * @param $level
     */
    protected function write($message, $level)
    {
        foreach ($this->loggers as $logger) {
            $logger(($this->sublog ? '          ↳' : '[Sidecar]') . " $message", $level);
        }
    }

    /**
     * @param $callback
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

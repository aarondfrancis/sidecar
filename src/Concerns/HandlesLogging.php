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
     * @param Command $command
     */
    public function addCommandLogger(Command $command)
    {
        $this->addLogger(function ($message) use ($command) {
            $command->info($message);
        });
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        foreach ($this->loggers as $logger) {
            $logger(($this->sublog ? '          ↳' : '[Sidecar]') . " $message");
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

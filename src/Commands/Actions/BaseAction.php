<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Hammerstone\Sidecar\Commands\Configure;

abstract class BaseAction
{
    /**
     * @var Configure
     */
    public $command;

    /**
     * @var string
     */
    public $region;

    public function __construct($region, Configure $command)
    {
        $this->region = $region;

        $this->command = $command;
    }

    abstract public function invoke();

    protected function progress($message)
    {
        $this->command->text("==> $message");
    }
}

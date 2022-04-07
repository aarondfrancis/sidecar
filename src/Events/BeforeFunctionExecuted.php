<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Events;

use Hammerstone\Sidecar\ServerlessFunction;

class BeforeFunctionExecuted
{
    /**
     * @var ServerlessFunction
     */
    public $function;

    /**
     * @var mixed
     */
    public $payload;

    /**
     * @param $payload
     * @param $function
     */
    public function __construct($function, $payload)
    {
        $this->payload = $payload;

        $this->function = $function;
    }
}

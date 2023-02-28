<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Events;

use Hammerstone\Sidecar\LambdaFunction;

class BeforeFunctionExecuted
{
    /**
     * @var LambdaFunction
     */
    public $function;

    /**
     * @var mixed
     */
    public $payload;

    public function __construct($function, $payload)
    {
        $this->payload = $payload;

        $this->function = $function;
    }
}

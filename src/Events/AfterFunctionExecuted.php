<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Events;

use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Results\PendingResult;
use Hammerstone\Sidecar\Results\SettledResult;

class AfterFunctionExecuted
{
    /**
     * @var LambdaFunction
     */
    public $function;

    /**
     * @var mixed
     */
    public $payload;

    /**
     * @var PendingResult|SettledResult
     */
    public $result;

    /**
     * @param $function
     * @param $payload
     * @param $result
     */
    public function __construct($function, $payload, $result)
    {
        $this->payload = $payload;

        $this->function = $function;

        $this->result = $result;
    }
}

<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Events;

use Hammerstone\Sidecar\LambdaFunction;

class BeforeFunctionExecuted
{
    public function __construct(
        public LambdaFunction $function,
        public mixed $payload
    ) {}
}

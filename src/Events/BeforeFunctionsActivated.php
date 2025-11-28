<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Events;

class BeforeFunctionsActivated
{
    public function __construct(
        public array $functions = []
    ) {}
}

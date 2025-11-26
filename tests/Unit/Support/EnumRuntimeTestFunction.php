<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Runtime;

class EnumRuntimeTestFunction extends LambdaFunction
{
    public function handler()
    {
        return 'index.handler';
    }

    public function package()
    {
        return [];
    }

    public function runtime(): Runtime|string
    {
        return Runtime::PYTHON_312;
    }
}

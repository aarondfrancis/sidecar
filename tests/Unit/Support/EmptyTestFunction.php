<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

use Hammerstone\Sidecar\LambdaFunction;

class EmptyTestFunction extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function package()
    {
        return optional();
    }
}

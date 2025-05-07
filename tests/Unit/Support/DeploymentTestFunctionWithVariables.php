<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

class DeploymentTestFunctionWithVariables extends DeploymentTestFunction
{
    public function variables()
    {
        return [
            'env' => 'value'
        ];
    }
}

<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

use Hammerstone\Sidecar\Package;
use Hammerstone\Sidecar\ServerlessFunction;

class DeploymentTestFunctionWithImage extends ServerlessFunction
{
    public function handler()
    {
        return Package::CONTAINER_HANDLER;
    }

    public function package()
    {
        return [
            'ImageUri' => '123.dkr.ecr.us-west-2.amazonaws.com/image:latest',
        ];
    }

    public function nameWithPrefix()
    {
        return 'test-FunctionName';
    }

    public function description()
    {
        return 'test-Description';
    }
}

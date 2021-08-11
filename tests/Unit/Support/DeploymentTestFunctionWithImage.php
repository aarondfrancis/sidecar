<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

use Hammerstone\Sidecar\LambdaFunction;

class DeploymentTestFunctionWithImage extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function packageType()
    {
        return 'Image';
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

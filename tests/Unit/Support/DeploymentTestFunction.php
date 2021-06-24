<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\WarmingConfig;

class DeploymentTestFunction extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function package()
    {
        //
    }

    public function nameWithPrefix()
    {
        return 'test-FunctionName';
    }

    public function warmingConfig()
    {
        return WarmingConfig::instances(2)->withPayload([
            'test' => 'payload'
        ]);
    }

    public function toDeploymentArray()
    {
        return [
            'FunctionName' => $this->nameWithPrefix(),
            'Runtime' => 'test-Runtime',
            'Role' => 'test-Role',
            'Handler' => 'test-Handler',
            'Code' => [
                'S3Bucket' => 'test-bucket',
                'S3Key' => 'test-key',
            ],
            'Description' => 'test-Description',
            'Timeout' => 'test-Timeout',
            'MemorySize' => 'test-MemorySize',
            'Layers' => 'test-Layers',
            'Publish' => 'test-Publish',
        ];
    }
}

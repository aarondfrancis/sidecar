<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Support\DeploymentTestFunction;

class DeployTest extends DeploymentTest
{
    public function mockCreatingFunction()
    {
        $this->lambda->shouldReceive('createFunction')->once()->with([
            'FunctionName' => 'test-FunctionName',
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
        ]);
    }

    public function mockActivating()
    {
        $this->lambda->shouldReceive('getLatestVersion')
            ->once()
            ->withArgs(function ($function) {
                return $function instanceof DeploymentTestFunction;
            })
            ->andReturn('10');

        $this->lambda->shouldReceive('aliasVersion')
            ->once()
            ->withArgs(function ($function, $alias, $version) {
                return $function instanceof DeploymentTestFunction
                    && $alias === 'active'
                    && $version === '10';
            })
            ->andReturn(LambdaClient::CREATED);
    }

    /** @test */
    public function it_deploys_the_functions_in_the_config()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->mockCreatingFunction();

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->artisan('sidecar:deploy');

        $this->assertEvents($deployed = true, $activated = false);
    }

    /** @test */
    public function it_deploys_and_activates_the_functions_in_the_config()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->mockCreatingFunction();
        $this->mockActivating();

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->artisan('sidecar:deploy', [
            '--activate' => true
        ]);

        $this->assertEvents($deployed = true, $activated = true);
    }

    /** @test */
    public function it_uses_a_fake_environment()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->mockCreatingFunction();

        $this->artisan('sidecar:deploy', [
            '--env' => 'faked'
        ]);

        $this->assertEquals('faked', Sidecar::getEnvironment());

        $this->assertEvents($deployed = true, $activated = false);
    }
}

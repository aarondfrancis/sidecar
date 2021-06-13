<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Unit\Support\DeploymentTestFunction;

class DeployTest extends DeploymentTest
{
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

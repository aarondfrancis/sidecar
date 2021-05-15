<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Support\DeploymentTestFunction;

class DeployTest extends DeploymentTest
{
    /** @test */
    public function it_deploys_the_functions_in_the_config()
    {
        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->mockCreatingFunction();

        $this->artisan('sidecar:deploy');

        $this->assertEvents($deployed = true, $activated = false);
    }

    /** @test */
    public function it_deploys_and_activates_the_functions_in_the_config()
    {
        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->mockCreatingFunction();
        $this->mockActivating();

        $this->artisan('sidecar:deploy', [
            '--activate' => true
        ]);

        $this->assertEvents($deployed = true, $activated = true);
    }

    /** @test */
    public function it_uses_a_fake_environment()
    {
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

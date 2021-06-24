<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Aws\Result;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Events\AfterFunctionsActivated;
use Hammerstone\Sidecar\Events\AfterFunctionsDeployed;
use Hammerstone\Sidecar\Events\BeforeFunctionsActivated;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeployed;
use Hammerstone\Sidecar\Results\SettledResult;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Unit\Support\DeploymentTestFunction;
use Illuminate\Support\Facades\Event;

class ActivateTest extends BaseTest
{
    protected $lambda;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->lambda = $this->mock(LambdaClient::class);
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

    public function assertEvents($deployed = true, $activated = true)
    {
        if ($deployed) {
            Event::assertDispatched(BeforeFunctionsDeployed::class);
            Event::assertDispatched(AfterFunctionsDeployed::class);
        } else {
            Event::assertNotDispatched(BeforeFunctionsDeployed::class);
            Event::assertNotDispatched(AfterFunctionsDeployed::class);
        }

        if ($activated) {
            Event::assertDispatched(BeforeFunctionsActivated::class);
            Event::assertDispatched(AfterFunctionsActivated::class);
        } else {
            Event::assertNotDispatched(BeforeFunctionsActivated::class);
            Event::assertNotDispatched(AfterFunctionsActivated::class);
        }
    }

    /** @test */
    public function it_should_activate_functions()
    {
        $this->mockActivating();
        
        $this->lambda->shouldNotReceive('invokeAsync');

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->artisan('sidecar:activate');

        $this->assertEvents($deployed = false, $activated = true);
    }

    protected function mockInvokeAsync($with = [], $return = null)
    {
        return $this->mockMethod('invokeAsync', $with, $return);
    }

    /** @test */
    public function it_should_pre_warm_functions_if_the_latest_version_is_different()
    {
        // The latest version is not the active version.
        $this->lambda->shouldReceive('latestVersionHasAlias')
            ->withArgs(function ($function, $alias) {
                return $function instanceof DeploymentTestFunction
                    && $alias === 'active';
            })
            ->andReturn(false);

        $this->lambda->shouldReceive('getLatestVersion')
            ->twice()
            ->andReturn('11');

        // The warming requests.
        $this->lambda->shouldReceive('invokeAsync')
            // Twice, as the warming config specifies two instances.
            ->twice()
            ->withArgs(function ($payload) {
                // The right version number
                return $payload['FunctionName'] === 'test-FunctionName:11'
                    // The payload from the warming config.
                    && $payload['Payload'] === '{"test":"payload"}';
            })
            ->andReturn(new Result);

        // Activating after warming. This is tested thoroughly elsewhere.
        $this->lambda->shouldReceive('aliasVersion')
            ->andReturn(LambdaClient::UPDATED);

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->artisan('sidecar:activate --pre-warm');
    }

    /** @test */
    public function it_should_not_pre_warm_functions_if_the_latest_version_is_different()
    {
        $this->lambda->shouldReceive('latestVersionHasAlias')
            ->andReturn(true);

        $this->lambda->shouldReceive('getLatestVersion')
            ->once()
            ->andReturn('11');

        $this->lambda->shouldNotReceive('invokeAsync');

        $this->lambda->shouldReceive('aliasVersion')
            ->andReturn(LambdaClient::UPDATED);

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->artisan('sidecar:activate --pre-warm');
    }


    /** @test */
    public function it_should_activate_functions_with_env()
    {
        $this->mockActivating();

        $this->lambda->shouldNotReceive('invokeAsync');

        config()->set('sidecar.functions', [
            DeploymentTestFunction::class
        ]);

        $this->artisan('sidecar:activate', [
            '--env' => 'faked'
        ]);

        $this->assertEquals('faked', Sidecar::getEnvironment());

        $this->assertEvents($deployed = false, $activated = true);
    }
}

<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Aws\Lambda\Exception\LambdaException;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Deployment;
use Hammerstone\Sidecar\Events\AfterFunctionsActivated;
use Hammerstone\Sidecar\Events\AfterFunctionsDeployed;
use Hammerstone\Sidecar\Events\BeforeFunctionsActivated;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeployed;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Support\DeploymentTestFunction;
use Illuminate\Support\Facades\Event;
use Mockery;

class ActivateTest extends BaseTest
{
    protected $lambda;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->lambda = $this->mock(LambdaClient::class);
    }

    public function mockListingVersions()
    {
        $this->lambda->shouldReceive('listVersionsByFunction')
            ->once()
            ->with([
                'FunctionName' => 'test-FunctionName',
                'MaxItems' => 100,
                'Marker' => null
            ])
            ->andReturn([
                'Versions' => [[
                    'FunctionName' => 'test-FunctionName',
                    'Version' => '10',
                ], [
                    'FunctionName' => 'test-FunctionName',
                    'Version' => '11',
                ], [
                    'FunctionName' => 'test-FunctionName',
                    'Version' => '12',
                ]]
            ]);
    }

    public function mockActivating()
    {
        $this->mockListingVersions();

        $this->lambda->shouldReceive('deleteAlias')->once()->with([
            'FunctionName' => 'test-FunctionName',
            'Name' => 'active',
        ]);

        $this->lambda->shouldReceive('createAlias')->once()->with([
            'FunctionName' => 'test-FunctionName',
            'FunctionVersion' => '12',
            'Name' => 'active',
        ]);
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
        $this->artisan('sidecar:activate');

        $this->assertEvents($deployed = false, $activated = true);
    }

    /** @test */
    public function it_should_activate_functions_with_env()
    {
        $this->artisan('sidecar:activate', [
            '--env' => 'faked'
        ]);

        $this->assertEquals('faked', Sidecar::getEnvironment());

        $this->assertEvents($deployed = false, $activated = true);
    }

}

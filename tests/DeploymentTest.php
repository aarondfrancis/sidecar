<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Aws\Lambda\Exception\LambdaException;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Events\AfterFunctionsActivated;
use Hammerstone\Sidecar\Events\AfterFunctionsDeployed;
use Hammerstone\Sidecar\Events\BeforeFunctionsActivated;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeployed;
use Hammerstone\Sidecar\Tests\Support\DeploymentTestFunction;
use Illuminate\Support\Facades\Event;
use Mockery;

class DeploymentTest extends BaseTest
{
    protected $lambda;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->lambda = $this->mock(LambdaClient::class);
    }

    public function notFoundException()
    {
        return Mockery::mock(LambdaException::class)
            ->shouldReceive('getStatusCode')
            ->andReturn(404)
            ->getMock();
    }

    public function mockFunctionNotExisting()
    {
        $this->lambda->shouldReceive('getFunction')->once()->andThrow($this->notFoundException());
    }

    public function mockCreatingFunction()
    {
        $this->mockFunctionNotExisting();

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

        $this->lambda->shouldNotReceive('updateFunctionConfiguration');
        $this->lambda->shouldNotReceive('updateFunctionCode');
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
                    "FunctionName" => 'test-FunctionName',
                    "Version" => '10',
                ], [
                    "FunctionName" => 'test-FunctionName',
                    "Version" => '11',
                ], [
                    "FunctionName" => 'test-FunctionName',
                    "Version" => '12',
                ]]
            ]);
    }

    public function mockUpdating()
    {
        $this->lambda->shouldReceive('getFunction')->andReturn([
            'Configuration' => [
                "FunctionName" => 'test-FunctionName',
                "Description" => 'test-Description',
            ]
        ]);

        $this->lambda->shouldReceive('updateFunctionConfiguration')->with([
            'FunctionName' => 'test-FunctionName',
            'Role' => 'test-Role',
            'Handler' => 'test-Handler',
            'Description' => 'test-Description [5000a525]',
            'Timeout' => 'test-Timeout',
            'MemorySize' => 'test-MemorySize'
        ]);

        $this->lambda->shouldReceive('updateFunctionCode')->with([
            'FunctionName' => 'test-FunctionName',
            'Publish' => 'test-Publish',
            'S3Bucket' => 'test-bucket',
            'S3Key' => 'test-key'
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

    /** @test */
    public function it_deploys_a_function_that_doesnt_exist()
    {
        $this->mockCreatingFunction();

        DeploymentTestFunction::deploy($activate = false);

        Event::assertDispatched(BeforeFunctionsDeployed::class);
        Event::assertDispatched(AfterFunctionsDeployed::class);

        Event::assertNotDispatched(BeforeFunctionsActivated::class);
        Event::assertNotDispatched(AfterFunctionsActivated::class);
    }

    /** @test */
    public function it_deploys_and_activates_a_function_that_doesnt_exist()
    {
        $this->mockCreatingFunction();
        $this->mockActivating();

        DeploymentTestFunction::deploy($activate = true);

        Event::assertDispatched(BeforeFunctionsDeployed::class);
        Event::assertDispatched(AfterFunctionsDeployed::class);

        Event::assertDispatched(BeforeFunctionsActivated::class);
        Event::assertDispatched(AfterFunctionsActivated::class);

    }

    /** @test */
    public function it_updates_an_existing_function()
    {
        $this->mockUpdating();

        DeploymentTestFunction::deploy($activate = false);

        Event::assertDispatched(BeforeFunctionsDeployed::class);
        Event::assertDispatched(AfterFunctionsDeployed::class);

        Event::assertNotDispatched(BeforeFunctionsActivated::class);
        Event::assertNotDispatched(AfterFunctionsActivated::class);
    }

    /** @test */
    public function it_updates_and_activates_an_existing_function()
    {
        $this->mockUpdating();
        $this->mockActivating();

        DeploymentTestFunction::deploy($activate = true);

        Event::assertDispatched(BeforeFunctionsDeployed::class);
        Event::assertDispatched(AfterFunctionsDeployed::class);

        Event::assertDispatched(BeforeFunctionsActivated::class);
        Event::assertDispatched(AfterFunctionsActivated::class);
    }


    /** @test */
    public function it_doesnt_update_if_nothing_has_changed()
    {
        $this->mockActivating();

        $this->lambda->shouldReceive('getFunction')->twice()->andReturn([
            'Configuration' => [
                "FunctionName" => 'test-FunctionName',
                "Description" => 'test-Description [5000a525]',
            ]
        ]);

        $this->lambda->shouldNotReceive('updateFunctionConfiguration');
        $this->lambda->shouldNotReceive('updateFunctionCode');

        DeploymentTestFunction::deploy($activate = true);

        Event::assertDispatched(BeforeFunctionsDeployed::class);
        Event::assertDispatched(AfterFunctionsDeployed::class);

        Event::assertDispatched(BeforeFunctionsActivated::class);
        Event::assertDispatched(AfterFunctionsActivated::class);
    }
}
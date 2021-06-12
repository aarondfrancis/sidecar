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
use Hammerstone\Sidecar\Exceptions\ConfigurationException;
use Hammerstone\Sidecar\Exceptions\NoFunctionsRegisteredException;
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

    public function mockUpdating()
    {
        $this->lambda->shouldReceive('getFunction')->andReturn([
            'Configuration' => [
                'FunctionName' => 'test-FunctionName',
                'Description' => 'test-Description',
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
    public function it_deploys_a_function_that_doesnt_exist()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->mockCreatingFunction();

        DeploymentTestFunction::deploy($activate = false);

        $this->assertEvents($deployed = true, $activated = false);
    }

    /** @test */
    public function it_deploys_a_function_that_doesnt_exist_from_the_deployment_class()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->mockCreatingFunction();

        Deployment::make(DeploymentTestFunction::class)->deploy($activate = false);

        $this->assertEvents($deployed = true, $activated = false);
    }

    /** @test */
    public function it_deploys_an_array_of_functions()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->mockCreatingFunction();

        Deployment::make([DeploymentTestFunction::class])->deploy($activate = false);

        $this->assertEvents($deployed = true, $activated = false);
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

        Deployment::make()->deploy($activate = false);

        $this->assertEvents($deployed = true, $activated = false);
    }

    /** @test */
    public function it_deploys_and_activates_a_function_that_doesnt_exist()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(false);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->mockCreatingFunction();
        $this->mockActivating();

        DeploymentTestFunction::deploy($activate = true);

        $this->assertEvents($deployed = true, $activated = true);
    }

    /** @test */
    public function it_updates_an_existing_function()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(true);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->lambda->shouldReceive('updateExistingFunction')->once()->withArgs(function ($function) {
            return $function instanceof DeploymentTestFunction;
        });

        DeploymentTestFunction::deploy($activate = false);

        $this->assertEvents($deployed = true, $activated = false);
    }

    /** @test */
    public function it_updates_and_activates_an_existing_function()
    {
        $this->lambda->shouldReceive('functionExists')->andReturn(true);
        $this->lambda->shouldReceive('getVersions')->andReturn([]);
        $this->lambda->shouldReceive('updateExistingFunction')->once()->withArgs(function ($function) {
            return $function instanceof DeploymentTestFunction;
        });
        $this->mockActivating();

        DeploymentTestFunction::deploy($activate = true);

        $this->assertEvents($deployed = true, $activated = true);
    }

    /** @test */
    public function it_throws_an_exception_if_there_are_no_functions()
    {
        config()->set('sidecar.functions', [

        ]);

        $this->expectException(NoFunctionsRegisteredException::class);

        Deployment::make()->deploy();
    }
}

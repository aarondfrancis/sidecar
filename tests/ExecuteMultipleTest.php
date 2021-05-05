<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Aws\Result;
use GuzzleHttp\Promise\PromiseInterface;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Results\PendingResult;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Support\EmptyTestFunction;
use Mockery;

class ExecuteMultipleTest extends BaseTest
{
    protected function expectedArgs($args = [])
    {
        return array_merge([
            'FunctionName' => 'SC-Laravel-testing-stone-Sidecar-Tests-Support-EmptyTestFunction:active',
            'InvocationType' => 'RequestResponse',
            'LogType' => 'Tail',
            'Payload' => '[]'
        ], $args);
    }

    protected function mockMultiple()
    {
        $result = Mockery::mock(PromiseInterface::class)
            ->shouldReceive('wait')
            ->andReturn(new Result)
            ->getMock();

        $mock = $this->mock(LambdaClient::class);

        $mock
            ->shouldReceive('invokeAsync')
            ->with($this->expectedArgs([
                'Payload' => '{"A":1}'
            ]))
            ->andReturn($result);

        $mock
            ->shouldReceive('invokeAsync')
            ->with($this->expectedArgs([
                'Payload' => '{"B":2}'
            ]))
            ->andReturn($result);

        $mock
            ->shouldReceive('invokeAsync')
            ->with($this->expectedArgs([
                'Payload' => '{"C":3}'
            ]))
            ->andReturn($result);
    }

    /** @test */
    public function execute_many_by_function()
    {
        $this->mockMultiple();

        EmptyTestFunction::executeMany([[
            'A' => 1
        ], [
            'B' => 2
        ], [
            'C' => 3
        ]]);
    }

    /** @test */
    public function execute_many_by_facade()
    {
        $this->mockMultiple();

        Sidecar::executeMany(EmptyTestFunction::class, [[
            'A' => 1
        ], [
            'B' => 2
        ], [
            'C' => 3
        ]]);
    }

    /** @test */
    public function execute_many_by_facade_with_instantiated_class()
    {
        $this->mockMultiple();

        Sidecar::executeMany(new EmptyTestFunction, [[
            'A' => 1
        ], [
            'B' => 2
        ], [
            'C' => 3
        ]]);
    }

    /** @test */
    public function execute_many_by_function_int()
    {
        $result = Mockery::mock(PromiseInterface::class)
            ->shouldReceive('wait')
            ->andReturn(new Result)
            ->getMock();

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(5)
            ->andReturn($result);

        EmptyTestFunction::executeMany(5);
    }

    /** @test */
    public function execute_many_by_facade_int()
    {
        $result = Mockery::mock(PromiseInterface::class)
            ->shouldReceive('wait')
            ->andReturn(new Result)
            ->getMock();

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(5)
            ->andReturn($result);

        Sidecar::executeMany(EmptyTestFunction::class, 5);
    }

    /** @test */
    public function execute_many_by_facade_int_with_instantiated_class()
    {
        $result = Mockery::mock(PromiseInterface::class)
            ->shouldReceive('wait')
            ->andReturn(new Result)
            ->getMock();

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(5)
            ->andReturn($result);

        Sidecar::executeMany(new EmptyTestFunction, 5);
    }

    /** @test */
    public function execute_many_async_by_function()
    {
        $result = Mockery::mock(PromiseInterface::class)
            ->shouldNotReceive('wait')
            ->getMock();

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(2)
            ->andReturn($result);

        $results = EmptyTestFunction::executeMany(2, $async = true);

        $this->assertInstanceOf(PendingResult::class, $results[0]);
    }
}

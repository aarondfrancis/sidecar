<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Aws\Result;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Sidecar;

class ExecuteMultipleTest extends BaseTest
{
    protected function expectedArgs($args = [])
    {
        return array_merge([
            'FunctionName' => 'SC-Laravel-testing-Hammerstone-Sidecar-Tests-TestFunction:active',
            'InvocationType' => 'RequestResponse',
            'LogType' => 'Tail',
            'Payload' => '[]'
        ], $args);
    }

    protected function mockMultiple()
    {
        $result = new Result;

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

        TestFunction::executeMany([[
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

        Sidecar::executeMany(TestFunction::class, [[
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

        Sidecar::executeMany(new TestFunction, [[
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
        $result = new Result;

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(5)
            ->andReturn($result);

        TestFunction::executeMany(5);
    }

    /** @test */
    public function execute_many_by_facade_int()
    {
        $result = new Result;

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(5)
            ->andReturn($result);

        Sidecar::executeMany(TestFunction::class, 5);
    }

    /** @test */
    public function execute_many_by_facade_int_with_instantiated_class()
    {
        $result = new Result;

        $this->mock(LambdaClient::class)
            ->shouldReceive('invokeAsync')
            ->times(5)
            ->andReturn($result);

        Sidecar::executeMany(new TestFunction, 5);
    }
}

class TestFunction extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function package()
    {
        //
    }
}

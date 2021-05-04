<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Aws\Result;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\Support\EmptyTestFunction;

class ExecuteTest extends BaseTest
{

    protected function mockMethod($method, $with, $return)
    {
        if (is_null($return)) {
            $return = new Result;
        }

        $this->mock(LambdaClient::class)
            ->shouldReceive($method)
            ->once()
            ->with($this->expectedArgs($with))
            ->andReturnUsing(function () use ($return) {
                return $return;
            });
    }

    protected function expectedArgs($args = [])
    {
        return array_merge([
            'FunctionName' => 'SC-Laravel-testing-stone-Sidecar-Tests-Support-EmptyTestFunction:active',
            'InvocationType' => 'RequestResponse',
            'LogType' => 'Tail',
            'Payload' => '[]'
        ], $args);
    }

    protected function mockInvoke($with = [], $return = null)
    {
        return $this->mockMethod('invoke', $with, $return);
    }

    protected function mockInvokeAsync($with = [], $return = null)
    {
        return $this->mockMethod('invokeAsync', $with, $return);
    }

    /** @test */
    public function basic_execution_by_function()
    {
        $this->mockInvoke();

        EmptyTestFunction::execute();
    }

    /** @test */
    public function basic_execution_by_facade()
    {
        $this->mockInvoke();

        Sidecar::execute(EmptyTestFunction::class);
    }

    /** @test */
    public function basic_execution_by_facade_with_instantiated_class()
    {
        $this->mockInvoke();

        Sidecar::execute(new EmptyTestFunction);
    }

    /** @test */
    public function execution_with_payload_by_function()
    {
        $this->mockInvoke([
            'Payload' => '{"foo":"bar"}'
        ]);

        EmptyTestFunction::execute([
            'foo' => 'bar'
        ]);
    }

    /** @test */
    public function execution_with_payload_by_facade()
    {
        $this->mockInvoke([
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::execute(EmptyTestFunction::class, [
            'foo' => 'bar'
        ]);
    }

    /** @test */
    public function execution_with_payload_by_facade_with_instantiated_class()
    {
        $this->mockInvoke([
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::execute(new EmptyTestFunction, [
            'foo' => 'bar'
        ]);
    }

    /** @test */
    public function async_execution_by_function()
    {
        $this->mockInvokeAsync([
            'Payload' => '{"foo":"bar"}'
        ]);

        EmptyTestFunction::execute([
            'foo' => 'bar'
        ], $async = true);
    }

    /** @test */
    public function async_execution_by_facade()
    {
        $this->mockInvokeAsync([
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::execute(EmptyTestFunction::class, [
            'foo' => 'bar'
        ], $async = true);
    }

    /** @test */
    public function async_execution_by_facade_with_instantiated_class()
    {
        $this->mockInvokeAsync([
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::execute(new EmptyTestFunction, [
            'foo' => 'bar'
        ], $async = true);
    }

}


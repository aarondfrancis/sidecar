<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Aws\Result;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Events\AfterFunctionExecuted;
use Hammerstone\Sidecar\Events\BeforeFunctionExecuted;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\BaseTest;
use Hammerstone\Sidecar\Tests\Unit\Support\EmptyTestFunction;
use Illuminate\Support\Facades\Event;

class ExecuteTest extends BaseTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

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
            'FunctionName' => 'SC-Laravel-testing--Sidecar-Tests-Unit-Support-EmptyTestFunction:active',
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

    public function assertEvents($executed = 1)
    {
        if ($executed) {
            Event::assertDispatched(BeforeFunctionExecuted::class, $executed);
            Event::assertDispatched(AfterFunctionExecuted::class, $executed);
        } else {
            Event::assertNotDispatched(BeforeFunctionExecuted::class);
            Event::assertNotDispatched(AfterFunctionExecuted::class);
        }
    }

    /** @test */
    public function basic_execution_by_function()
    {
        $this->mockInvoke();

        EmptyTestFunction::execute();

        $this->assertEvents();
    }

    /** @test */
    public function basic_execution_by_facade()
    {
        $this->mockInvoke();

        Sidecar::execute(EmptyTestFunction::class);

        $this->assertEvents();
    }

    /** @test */
    public function basic_execution_by_facade_with_instantiated_class()
    {
        $this->mockInvoke();

        Sidecar::execute(new EmptyTestFunction);

        $this->assertEvents();
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

        $this->assertEvents();
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

        $this->assertEvents();
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

        $this->assertEvents();
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

        $this->assertEvents();
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

        $this->assertEvents();
    }

    /** @test */
    public function async_execution_by_facade_directly()
    {
        $this->mockInvokeAsync([
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::executeAsync(EmptyTestFunction::class, [
            'foo' => 'bar'
        ]);

        $this->assertEvents();
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

        $this->assertEvents();
    }

    /** @test */
    public function event_invocation_execution_by_function()
    {
        $this->mockInvoke([
            'InvocationType' => 'Event',
            'Payload' => '{"foo":"bar"}'
        ]);

        EmptyTestFunction::execute([
            'foo' => 'bar'
        ], $async = false, $invocationType = 'Event');

        $this->assertEvents();
    }

    /** @test */
    public function event_invocation_execution_by_function_with_event_helper()
    {
        $this->mockInvoke([
            'InvocationType' => 'Event',
            'Payload' => '{"foo":"bar"}'
        ]);

        EmptyTestFunction::executeAsEvent([
            'foo' => 'bar'
        ]);

        $this->assertEvents();
    }

    /** @test */
    public function event_invocation_execution_by_facade()
    {
        $this->mockInvoke([
            'InvocationType' => 'Event',
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::execute(EmptyTestFunction::class, [
            'foo' => 'bar'
        ], $async = false, $invocationType = 'Event');

        $this->assertEvents();
    }

    /** @test */
    public function event_invocation_execution_by_facade_directly()
    {
        $this->mockInvoke([
            'InvocationType' => 'Event',
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::executeAsEvent(EmptyTestFunction::class, [
            'foo' => 'bar'
        ]);

        $this->assertEvents();
    }

    /** @test */
    public function event_invocation_execution_by_facade_with_instantiated_class()
    {
        $this->mockInvoke([
            'InvocationType' => 'Event',
            'Payload' => '{"foo":"bar"}'
        ]);

        Sidecar::execute(new EmptyTestFunction, [
            'foo' => 'bar'
        ], $async = false, $invocationType = 'Event');

        $this->assertEvents();
    }
}

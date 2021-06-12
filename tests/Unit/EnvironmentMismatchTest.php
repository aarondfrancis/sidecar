<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Aws\Lambda\Exception\LambdaException;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Exceptions\FunctionNotFoundException;
use Hammerstone\Sidecar\Tests\Unit\Support\EmptyTestFunction;
use Illuminate\Support\Facades\Event;
use Mockery;

class EnvironmentMismatchTest extends BaseTest
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

    protected function mockInvokeNonExistent()
    {
        return $this->lambda->shouldReceive('invoke')
            ->once()
            ->andThrow($this->notFoundException());
    }

    /** @test */
    public function it_throws_the_right_exception()
    {
        $this->mockInvokeNonExistent();

        $this->expectException(FunctionNotFoundException::class);
        $this->expectExceptionMessage('not found in environment `testing`');
        EmptyTestFunction::execute();
    }
}

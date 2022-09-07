<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Aws\Lambda\Exception\LambdaException;
use Hammerstone\Sidecar\Architecture;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Tests\Unit\Support\DeploymentTestFunction;
use Hammerstone\Sidecar\Tests\Unit\Support\DeploymentTestFunctionWithImage;
use Hammerstone\Sidecar\Tests\Unit\Support\EmptyTestFunction;
use Illuminate\Support\Facades\Event;
use Mockery;

class LambdaClientTest extends BaseTest
{
    protected $lambda;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->lambda = $this->partialMock(LambdaClient::class);
    }

    public function responseFromFile($file)
    {
        return json_decode(file_get_contents(__DIR__ . "/Support/Responses/$file.json"), JSON_OBJECT_AS_ARRAY);
    }

    /** @test */
    public function it_gets_the_latest_version()
    {
        $this->lambda->shouldReceive('getVersions')
            ->once()
            ->andReturn($this->responseFromFile('getVersions'));

        $this->assertSame('81', $this->lambda->getLatestVersion(new EmptyTestFunction));
    }

    /** @test */
    public function it_checks_latest_version_has_alias()
    {
        $this->lambda->shouldReceive('getVersions')
            ->once()
            ->andReturn($this->responseFromFile('getVersions'));

        $this->lambda->shouldReceive('getAliasWithoutException')
            ->andReturn($this->responseFromFile('getAlias'));

        $this->assertTrue($this->lambda->latestVersionHasAlias(new EmptyTestFunction, 'active'));
    }

    /** @test */
    public function it_checks_latest_version_has_alias_different()
    {
        $this->lambda->shouldReceive('getVersions')
            ->once()
            ->andReturn($this->responseFromFile('getVersions'));

        $this->lambda->shouldReceive('getAliasWithoutException')
            ->andReturn(false);

        $this->assertFalse($this->lambda->latestVersionHasAlias(new EmptyTestFunction, 'nonexistent'));
    }

    /** @test */
    public function it_gets_all_versions()
    {
        $this->lambda->shouldReceive('listVersionsByFunction')
            ->andReturn(
                $this->responseFromFile('listVersionsByFunction-page1'),
                $this->responseFromFile('listVersionsByFunction-page2')
            );

        $versions = $this->lambda->getVersions(new EmptyTestFunction);

        $this->assertCount(2, $versions);
    }

    /** @test */
    public function it_returns_false_for_missing_alias()
    {
        $this->lambda->shouldReceive('getAlias')
            ->andThrow(
                Mockery::mock(LambdaException::class)
                    ->shouldReceive('getStatusCode')
                    ->andReturn(404)
                    ->getMock()
            );

        $alias = $this->lambda->getAliasWithoutException(new EmptyTestFunction, 'foo');

        $this->assertEquals(false, $alias);
    }

    /** @test */
    public function delete_function_version_passes_on_qualifier()
    {
        $this->lambda->shouldReceive('deleteFunction')
            ->once()
            ->with([
                'FunctionName' => (new EmptyTestFunction)->nameWithPrefix(),
                'Qualifier' => '10'
            ]);

        $this->lambda->deleteFunctionVersion(new EmptyTestFunction, '10');
    }

    /** @test */
    public function get_function_with_no_checksum()
    {
        $this->lambda->shouldReceive('getFunction')
            ->once()
            ->andReturn($this->responseFromFile('getFunction'));

        $exists = $this->lambda->functionExists(new EmptyTestFunction);

        $this->assertEquals(true, $exists);
    }

    /** @test */
    public function get_function_with_same_checksum()
    {
        $this->lambda->shouldReceive('getFunction')
            ->once()
            ->andReturn($this->responseFromFile('getFunction'));

        $exists = $this->lambda->functionExists(new EmptyTestFunction, 'ab0f64a5');

        $this->assertEquals(true, $exists);
    }

    /** @test */
    public function get_function_with_different_checksum()
    {
        $this->lambda->shouldReceive('getFunction')
            ->once()
            ->andReturn($this->responseFromFile('getFunction'));

        $exists = $this->lambda->functionExists(new EmptyTestFunction, '000000');

        $this->assertEquals(false, $exists);
    }

    /** @test */
    public function get_non_existent_function()
    {
        $this->lambda->shouldReceive('getFunction')
            ->once()
            ->andThrow(
                Mockery::mock(LambdaException::class)
                    ->shouldReceive('getStatusCode')
                    ->andReturn(404)
                    ->getMock()
            );

        $exists = $this->lambda->functionExists(new EmptyTestFunction);

        $this->assertEquals(false, $exists);
    }

    /** @test */
    public function update_existing_function()
    {
        $function = new DeploymentTestFunction;

        $this->lambda->shouldReceive('functionExists')
            ->once()
            ->withArgs(function ($f, $checksum) use ($function) {
                return $f === $function && $checksum === 'e827998e';
            })
            ->andReturn(false);

        $this->lambda->shouldReceive('updateFunctionConfiguration')
            ->once()
            ->with([
                'FunctionName' => 'test-FunctionName',
                'Runtime' => 'test-Runtime',
                'Role' => 'test-Role',
                'Handler' => 'test-Handler',
                'Description' => 'test-Description [e827998e]',
                'Timeout' => 'test-Timeout',
                'EphemeralStorage' => [
                    'Size' => 'test-EphemeralStorage'
                ],
                'MemorySize' => 'test-MemorySize',
                'Layers' => 'test-Layers',
                'Architectures' => [
                    Architecture::X86_64
                ]
            ]);

        $this->lambda->shouldReceive('updateFunctionCode')
            ->once()
            ->with([
                'FunctionName' => 'test-FunctionName',
                'S3Bucket' => 'test-bucket',
                'S3Key' => 'test-key',
                'Publish' => 'test-Publish',
                'Architectures' => [
                    Architecture::X86_64
                ]
            ]);

        $this->lambda->updateExistingFunction($function);
    }

    /** @test */
    public function update_existing_image_function()
    {
        $function = new DeploymentTestFunctionWithImage;

        $this->lambda->shouldReceive('functionExists')
            ->once()
            ->andReturn(false);

        $this->lambda->shouldReceive('updateFunctionConfiguration')
            ->once()
            ->with([
                'FunctionName' => 'test-FunctionName',
                'Role' => null,
                'Description' => 'test-Description [ac420e45]',
                'Timeout' => 300,
                'MemorySize' => 512,
                'EphemeralStorage' => [
                    'Size' => 512
                ],
                'Layers' => [],
                'PackageType' => 'Image',
                'Architectures' => [
                    Architecture::X86_64
                ]
            ]);

        $this->lambda->shouldReceive('updateFunctionCode')
            ->once()
            ->with([
                'FunctionName' => 'test-FunctionName',
                'Publish' => 'test-Publish',
                'ImageUri' => '123.dkr.ecr.us-west-2.amazonaws.com/image:latest',
                'Architectures' => [
                    Architecture::X86_64
                ]
            ]);

        $this->lambda->updateExistingFunction($function);
    }

    /** @test */
    public function existing_function_unchanged()
    {
        $function = new DeploymentTestFunction;

        $this->lambda->shouldReceive('functionExists')
            ->once()
            ->withArgs(function ($f, $checksum) use ($function) {
                return $f === $function && $checksum === 'e827998e';
            })
            ->andReturn(true);

        $this->lambda->shouldNotReceive('updateFunctionConfiguration');
        $this->lambda->shouldNotReceive('updateFunctionCode');

        $result = $this->lambda->updateExistingFunction($function);

        $this->assertSame(LambdaClient::NOOP, $result);
    }

    /** @test */
    public function it_creates_an_alias_for_the_latest_version()
    {
        $this->lambda->shouldReceive('getLatestVersion')->andReturn('82');
        $this->lambda->shouldReceive('getAliasWithoutException')->andReturn(false);

        $this->lambda->shouldReceive('createAlias')
            ->once()
            ->with([
                'FunctionName' => 'sc-laravel-testing-7a7aecar-tests-unit-support-emptytestfunction',
                'Name' => 'active',
                'FunctionVersion' => '82',
            ]);

        $response = $this->lambda->aliasVersion(new EmptyTestFunction, 'active');

        $this->assertSame(LambdaClient::CREATED, $response);
    }

    /** @test */
    public function it_updates_an_alias()
    {
        $this->lambda->shouldReceive('getLatestVersion')->andReturn('82');
        $this->lambda->shouldReceive('getAliasWithoutException')
            ->andReturn($this->responseFromFile('getAlias'));

        $this->lambda->shouldReceive('updateAlias')
            ->once()
            ->with([
                'FunctionName' => 'sc-laravel-testing-7a7aecar-tests-unit-support-emptytestfunction',
                'Name' => 'active',
                'FunctionVersion' => '82',
            ]);

        $response = $this->lambda->aliasVersion(new EmptyTestFunction, 'active');

        $this->assertSame(LambdaClient::UPDATED, $response);
    }

    /** @test */
    public function you_can_specify_the_alias_version()
    {
        $this->lambda->shouldNotReceive('getLatestVersion');
        $this->lambda->shouldReceive('getAliasWithoutException')
            ->andReturn($this->responseFromFile('getAlias'));

        $this->lambda->shouldReceive('updateAlias')
            ->once()
            ->with([
                'FunctionName' => 'sc-laravel-testing-7a7aecar-tests-unit-support-emptytestfunction',
                'Name' => 'foo',
                'FunctionVersion' => '100',
            ]);

        $response = $this->lambda->aliasVersion(new EmptyTestFunction, 'foo', '100');

        $this->assertSame(LambdaClient::UPDATED, $response);
    }

    /** @test */
    public function it_noops_an_alias()
    {
        $this->lambda->shouldReceive('getLatestVersion')->andReturn('81');
        $this->lambda->shouldReceive('getAliasWithoutException')
            ->andReturn($this->responseFromFile('getAlias'));

        $this->lambda->shouldNotReceive('updateAlias');
        $this->lambda->shouldNotReceive('createAlias');

        $response = $this->lambda->aliasVersion(new EmptyTestFunction, 'active');

        $this->assertSame(LambdaClient::NOOP, $response);
    }
}

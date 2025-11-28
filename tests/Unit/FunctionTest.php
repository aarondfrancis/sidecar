<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Architecture;
use Hammerstone\Sidecar\Runtime;
use Hammerstone\Sidecar\Tests\Unit\Support\EmptyTestFunction;
use Hammerstone\Sidecar\Tests\Unit\Support\EnumRuntimeTestFunction;
use Hammerstone\Sidecar\Tests\Unit\Support\StringRuntimeTestFunction;

class FunctionTest extends Base
{
    public function test_app_name_with_a_space_gets_dashed()
    {
        config([
            'sidecar.app_name' => 'Amazing App'
        ]);

        $this->assertEquals(
            'sc-amazing-app-testing-7a7a-tests-unit-support-emptytestfunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );
    }

    public function test_function_names_over_64_get_limited_to_64()
    {
        config([
            'sidecar.app_name' => 'Amazing App Amazing App Amazing App Amazing App Amazing App Amazing App'
        ]);

        $function = new EmptyTestFunction;
        $name = $function->nameWithPrefix();

        $this->assertGreaterThan(32, strlen($function->name()));
        $this->assertEquals(64, strlen($name));
        $this->assertEquals('sc-amazing-app-amazing-app-a0528-7a7at-support-emptytestfunction', $name);
    }

    public function test_short_prefix_means_longer_name()
    {
        config([
            'sidecar.app_name' => 'A'
        ]);

        $function = new EmptyTestFunction;
        $name = $function->nameWithPrefix();

        $this->assertEquals(64, strlen($name));
        $this->assertEquals('sc-a-testing-7a7ane-sidecar-tests-unit-support-emptytestfunction', $name);
    }

    public function test_a_pipe_in_the_app_name_is_ok()
    {
        config([
            'sidecar.app_name' => 'Vriend van de Show | Local'
        ]);

        $function = new EmptyTestFunction;
        $name = $function->nameWithPrefix();

        $this->assertEquals('sc-vriend-van-de-show-local-55c0-7a7at-support-emptytestfunction', $name);
    }

    public function test_changing_the_prefix_changes_the_prefix()
    {
        $function = (new EmptyTestFunction);

        $this->assertEquals(
            'sc-laravel-testing-7a7aecar-tests-unit-support-emptytestfunction',
            $function->nameWithPrefix()
        );

        config([
            'sidecar.lambda_prefix' => 'FOO',
        ]);

        $this->assertEquals(
            'foo-laravel-testing-7a7acar-tests-unit-support-emptytestfunction',
            $function->nameWithPrefix()
        );
    }

    public function test_sidecar_app_name_ignores_laravel_app_name()
    {
        config([
            'app.name' => 'Laravel',
        ]);

        $this->assertEquals(
            'sc-laravel-testing-7a7aecar-tests-unit-support-emptytestfunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );

        config([
            'sidecar.app_name' => 'Hammerstone',
        ]);

        $this->assertEquals(
            'sc-hammerstone-testing-7a7a-tests-unit-support-emptytestfunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );
    }

    public function test_memory_and_timeout_and_storage_get_cast_to_ints()
    {
        config([
            'sidecar.timeout' => '5',
            'sidecar.memory' => '500',
            'sidecar.storage' => '1024'
        ]);

        $array = (new EmptyTestFunction)->toDeploymentArray();

        $this->assertSame(5, $array['Timeout']);
        $this->assertSame(500, $array['MemorySize']);
        $this->assertSame(1024, $array['EphemeralStorage']['Size']);
    }

    public function test_default_runtime_returns_enum()
    {
        $function = new EmptyTestFunction;

        $this->assertInstanceOf(Runtime::class, $function->runtime());
        $this->assertEquals(Runtime::NODEJS_20, $function->runtime());
    }

    public function test_runtime_value_resolves_enum_to_string()
    {
        $function = new EnumRuntimeTestFunction;

        $this->assertInstanceOf(Runtime::class, $function->runtime());
        $this->assertEquals('python3.12', $function->runtimeValue());
    }

    public function test_runtime_value_passes_through_string()
    {
        $function = new StringRuntimeTestFunction;

        $this->assertIsString($function->runtime());
        $this->assertEquals('custom-runtime', $function->runtimeValue());
    }

    public function test_default_architecture_from_config()
    {
        config(['sidecar.architecture' => 'arm64']);

        $function = new EmptyTestFunction;

        $this->assertEquals('arm64', $function->architectureValue());
    }

    public function test_architecture_value_resolves_enum_to_string()
    {
        config(['sidecar.architecture' => Architecture::ARM_64]);

        $function = new EmptyTestFunction;

        $this->assertEquals('arm64', $function->architectureValue());
    }

    public function test_architecture_value_passes_through_string()
    {
        config(['sidecar.architecture' => 'x86_64']);

        $function = new EmptyTestFunction;

        $this->assertEquals('x86_64', $function->architectureValue());
    }

    public function test_deployment_array_contains_architecture()
    {
        config(['sidecar.architecture' => 'arm64']);

        $function = new EmptyTestFunction;
        $array = $function->toDeploymentArray();

        $this->assertEquals(['arm64'], $array['Architectures']);
    }

    public function test_deployment_array_contains_runtime()
    {
        $function = new EmptyTestFunction;
        $array = $function->toDeploymentArray();

        $this->assertEquals('nodejs20.x', $array['Runtime']);
    }
}

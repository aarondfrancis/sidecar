<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Runtime;
use Hammerstone\Sidecar\RuntimeConstants;

class RuntimeTest extends Base
{
    public function test_runtime_is_a_backed_enum()
    {
        $this->assertInstanceOf(\BackedEnum::class, Runtime::NODEJS_20);
    }

    public function test_nodejs_runtimes_have_correct_values()
    {
        $this->assertEquals('nodejs24.x', Runtime::NODEJS_24->value);
        $this->assertEquals('nodejs22.x', Runtime::NODEJS_22->value);
        $this->assertEquals('nodejs20.x', Runtime::NODEJS_20->value);
        $this->assertEquals('nodejs18.x', Runtime::NODEJS_18->value);
        $this->assertEquals('nodejs16.x', Runtime::NODEJS_16->value);
        $this->assertEquals('nodejs14.x', Runtime::NODEJS_14->value);
    }

    public function test_python_runtimes_have_correct_values()
    {
        $this->assertEquals('python3.14', Runtime::PYTHON_314->value);
        $this->assertEquals('python3.13', Runtime::PYTHON_313->value);
        $this->assertEquals('python3.12', Runtime::PYTHON_312->value);
        $this->assertEquals('python3.11', Runtime::PYTHON_311->value);
        $this->assertEquals('python3.10', Runtime::PYTHON_310->value);
        $this->assertEquals('python3.9', Runtime::PYTHON_39->value);
        $this->assertEquals('python3.8', Runtime::PYTHON_38->value);
        $this->assertEquals('python3.7', Runtime::PYTHON_37->value);
    }

    public function test_java_runtimes_have_correct_values()
    {
        $this->assertEquals('java25', Runtime::JAVA_25->value);
        $this->assertEquals('java21', Runtime::JAVA_21->value);
        $this->assertEquals('java17', Runtime::JAVA_17->value);
        $this->assertEquals('java11', Runtime::JAVA_11->value);
        $this->assertEquals('java8.al2', Runtime::JAVA_8_LINUX2->value);
        $this->assertEquals('java8', Runtime::JAVA_8->value);
    }

    public function test_dotnet_runtimes_have_correct_values()
    {
        $this->assertEquals('dotnet9', Runtime::DOT_NET_9->value);
        $this->assertEquals('dotnet8', Runtime::DOT_NET_8->value);
        $this->assertEquals('dotnet7', Runtime::DOT_NET_7->value);
        $this->assertEquals('dotnet6', Runtime::DOT_NET_6->value);
    }

    public function test_ruby_runtimes_have_correct_values()
    {
        $this->assertEquals('ruby3.4', Runtime::RUBY_34->value);
        $this->assertEquals('ruby3.3', Runtime::RUBY_33->value);
        $this->assertEquals('ruby3.2', Runtime::RUBY_32->value);
        $this->assertEquals('ruby2.7', Runtime::RUBY_27->value);
    }

    public function test_provided_runtimes_have_correct_values()
    {
        $this->assertEquals('provided.al2023', Runtime::PROVIDED_AL2023->value);
        $this->assertEquals('provided.al2', Runtime::PROVIDED_AL2->value);
        $this->assertEquals('provided', Runtime::PROVIDED->value);
    }

    public function test_go_runtime_has_correct_value()
    {
        $this->assertEquals('go1.x', Runtime::GO_1X->value);
    }

    public function test_runtime_can_be_created_from_string()
    {
        $this->assertEquals(Runtime::NODEJS_20, Runtime::from('nodejs20.x'));
        $this->assertEquals(Runtime::PYTHON_312, Runtime::from('python3.12'));
    }

    public function test_runtime_constants_class_exists_for_backwards_compatibility()
    {
        $this->assertTrue(class_exists(RuntimeConstants::class));
    }

    public function test_runtime_constants_match_enum_values()
    {
        $this->assertEquals(Runtime::NODEJS_24->value, RuntimeConstants::NODEJS_24);
        $this->assertEquals(Runtime::NODEJS_22->value, RuntimeConstants::NODEJS_22);
        $this->assertEquals(Runtime::NODEJS_20->value, RuntimeConstants::NODEJS_20);
        $this->assertEquals(Runtime::PYTHON_312->value, RuntimeConstants::PYTHON_312);
        $this->assertEquals(Runtime::JAVA_21->value, RuntimeConstants::JAVA_21);
        $this->assertEquals(Runtime::DOT_NET_8->value, RuntimeConstants::DOT_NET_8);
        $this->assertEquals(Runtime::RUBY_33->value, RuntimeConstants::RUBY_33);
    }
}

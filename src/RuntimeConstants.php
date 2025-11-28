<?php

declare(strict_types=1);

namespace Hammerstone\Sidecar;

/**
 * @deprecated Use the Runtime enum instead. This class will be removed in a future version.
 */
abstract class RuntimeConstants
{
    /** @deprecated Use Runtime::NODEJS_24 instead */
    public const NODEJS_24 = 'nodejs24.x';

    /** @deprecated Use Runtime::NODEJS_22 instead */
    public const NODEJS_22 = 'nodejs22.x';

    /** @deprecated Use Runtime::NODEJS_20 instead */
    public const NODEJS_20 = 'nodejs20.x';

    /** @deprecated Use Runtime::NODEJS_18 instead (also deprecated by AWS) */
    public const NODEJS_18 = 'nodejs18.x';

    /** @deprecated Use Runtime::NODEJS_16 instead (also deprecated by AWS) */
    public const NODEJS_16 = 'nodejs16.x';

    /** @deprecated Use Runtime::NODEJS_14 instead (also deprecated by AWS) */
    public const NODEJS_14 = 'nodejs14.x';

    /** @deprecated Use Runtime::PYTHON_314 instead */
    public const PYTHON_314 = 'python3.14';

    /** @deprecated Use Runtime::PYTHON_313 instead */
    public const PYTHON_313 = 'python3.13';

    /** @deprecated Use Runtime::PYTHON_312 instead */
    public const PYTHON_312 = 'python3.12';

    /** @deprecated Use Runtime::PYTHON_311 instead */
    public const PYTHON_311 = 'python3.11';

    /** @deprecated Use Runtime::PYTHON_310 instead */
    public const PYTHON_310 = 'python3.10';

    /** @deprecated Use Runtime::PYTHON_39 instead */
    public const PYTHON_39 = 'python3.9';

    /** @deprecated Use Runtime::PYTHON_38 instead (also deprecated by AWS) */
    public const PYTHON_38 = 'python3.8';

    /** @deprecated Use Runtime::PYTHON_37 instead (also deprecated by AWS) */
    public const PYTHON_37 = 'python3.7';

    /** @deprecated Use Runtime::JAVA_25 instead */
    public const JAVA_25 = 'java25';

    /** @deprecated Use Runtime::JAVA_21 instead */
    public const JAVA_21 = 'java21';

    /** @deprecated Use Runtime::JAVA_17 instead */
    public const JAVA_17 = 'java17';

    /** @deprecated Use Runtime::JAVA_11 instead */
    public const JAVA_11 = 'java11';

    /** @deprecated Use Runtime::JAVA_8_LINUX2 instead */
    public const JAVA_8_LINUX2 = 'java8.al2';

    /** @deprecated Use Runtime::JAVA_8 instead (also deprecated by AWS) */
    public const JAVA_8 = 'java8';

    /** @deprecated Use Runtime::DOT_NET_9 instead */
    public const DOT_NET_9 = 'dotnet9';

    /** @deprecated Use Runtime::DOT_NET_8 instead */
    public const DOT_NET_8 = 'dotnet8';

    /** @deprecated Use Runtime::DOT_NET_7 instead (also deprecated by AWS) */
    public const DOT_NET_7 = 'dotnet7';

    /** @deprecated Use Runtime::DOT_NET_6 instead (also deprecated by AWS) */
    public const DOT_NET_6 = 'dotnet6';

    /** @deprecated Use Runtime::RUBY_34 instead */
    public const RUBY_34 = 'ruby3.4';

    /** @deprecated Use Runtime::RUBY_33 instead */
    public const RUBY_33 = 'ruby3.3';

    /** @deprecated Use Runtime::RUBY_32 instead */
    public const RUBY_32 = 'ruby3.2';

    /** @deprecated Use Runtime::RUBY_27 instead (also deprecated by AWS) */
    public const RUBY_27 = 'ruby2.7';

    /** @deprecated Use Runtime::GO_1X instead (also deprecated by AWS) */
    public const GO_1X = 'go1.x';

    /** @deprecated Use Runtime::PROVIDED_AL2023 instead */
    public const PROVIDED_AL2023 = 'provided.al2023';

    /** @deprecated Use Runtime::PROVIDED_AL2 instead */
    public const PROVIDED_AL2 = 'provided.al2';

    /** @deprecated Use Runtime::PROVIDED instead (also deprecated by AWS) */
    public const PROVIDED = 'provided';
}

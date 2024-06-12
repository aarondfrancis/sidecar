<?php

namespace Hammerstone\Sidecar;

abstract class Runtime
{
    public const NODEJS_20 = 'nodejs20.x';

    public const NODEJS_18 = 'nodejs18.x';

    public const NODEJS_16 = 'nodejs16.x';

    /** @deprecated */
    public const NODEJS_14 = 'nodejs14.x';

    public const PYTHON_312 = 'python3.12';

    public const PYTHON_311 = 'python3.11';

    public const PYTHON_310 = 'python3.10';

    public const PYTHON_39 = 'python3.9';

    public const PYTHON_38 = 'python3.8';

    /** @deprecated */
    public const PYTHON_37 = 'python3.7';

    public const JAVA_21 = 'java21';

    public const JAVA_17 = 'java17';

    public const JAVA_11 = 'java11';

    public const JAVA_8_LINUX2 = 'java8.al2';

    /** @deprecated */
    public const JAVA_8 = 'java8';

    public const DOT_NET_8 = 'dotnet8';

    /** @deprecated */
    public const DOT_NET_7 = 'dotnet7';

    public const DOT_NET_6 = 'dotnet6';

    public const RUBY_33 = 'ruby3.3';

    public const RUBY_32 = 'ruby3.2';

    /** @deprecated */
    public const RUBY_27 = 'ruby2.7';

    /** @deprecated */
    public const GO_1X = 'go1.x';

    public const PROVIDED_AL2023 = 'provided.al2023';

    public const PROVIDED_AL2 = 'provided.al2';

    /** @deprecated */
    public const PROVIDED = 'provided';
}

<?php

declare(strict_types=1);

namespace Hammerstone\Sidecar;

enum Runtime: string
{
    case NODEJS_24 = 'nodejs24.x';
    case NODEJS_22 = 'nodejs22.x';
    case NODEJS_20 = 'nodejs20.x';

    /** @deprecated AWS has deprecated this runtime */
    case NODEJS_18 = 'nodejs18.x';

    /** @deprecated AWS has deprecated this runtime */
    case NODEJS_16 = 'nodejs16.x';

    /** @deprecated AWS has deprecated this runtime */
    case NODEJS_14 = 'nodejs14.x';

    case PYTHON_314 = 'python3.14';
    case PYTHON_313 = 'python3.13';
    case PYTHON_312 = 'python3.12';
    case PYTHON_311 = 'python3.11';
    case PYTHON_310 = 'python3.10';
    case PYTHON_39 = 'python3.9';

    /** @deprecated AWS has deprecated this runtime */
    case PYTHON_38 = 'python3.8';

    /** @deprecated AWS has deprecated this runtime */
    case PYTHON_37 = 'python3.7';

    case JAVA_25 = 'java25';
    case JAVA_21 = 'java21';
    case JAVA_17 = 'java17';
    case JAVA_11 = 'java11';
    case JAVA_8_LINUX2 = 'java8.al2';

    /** @deprecated AWS has deprecated this runtime */
    case JAVA_8 = 'java8';

    case DOT_NET_9 = 'dotnet9';
    case DOT_NET_8 = 'dotnet8';

    /** @deprecated AWS has deprecated this runtime */
    case DOT_NET_7 = 'dotnet7';

    /** @deprecated AWS has deprecated this runtime */
    case DOT_NET_6 = 'dotnet6';

    case RUBY_34 = 'ruby3.4';
    case RUBY_33 = 'ruby3.3';
    case RUBY_32 = 'ruby3.2';

    /** @deprecated AWS has deprecated this runtime */
    case RUBY_27 = 'ruby2.7';

    /** @deprecated AWS has deprecated this runtime */
    case GO_1X = 'go1.x';

    case PROVIDED_AL2023 = 'provided.al2023';
    case PROVIDED_AL2 = 'provided.al2';

    /** @deprecated AWS has deprecated this runtime */
    case PROVIDED = 'provided';
}

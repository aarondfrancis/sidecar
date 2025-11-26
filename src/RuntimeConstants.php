<?php

declare(strict_types=1);

namespace Hammerstone\Sidecar;

/**
 * @deprecated Use the Runtime enum instead. This class will be removed in a future version.
 */
abstract class RuntimeConstants
{
    /** @deprecated Use Runtime::NODEJS_24 instead */
    public const NODEJS_24 = Runtime::NODEJS_24->value;

    /** @deprecated Use Runtime::NODEJS_22 instead */
    public const NODEJS_22 = Runtime::NODEJS_22->value;

    /** @deprecated Use Runtime::NODEJS_20 instead */
    public const NODEJS_20 = Runtime::NODEJS_20->value;

    /** @deprecated Use Runtime::NODEJS_18 instead (also deprecated by AWS) */
    public const NODEJS_18 = Runtime::NODEJS_18->value;

    /** @deprecated Use Runtime::NODEJS_16 instead (also deprecated by AWS) */
    public const NODEJS_16 = Runtime::NODEJS_16->value;

    /** @deprecated Use Runtime::NODEJS_14 instead (also deprecated by AWS) */
    public const NODEJS_14 = Runtime::NODEJS_14->value;

    /** @deprecated Use Runtime::PYTHON_314 instead */
    public const PYTHON_314 = Runtime::PYTHON_314->value;

    /** @deprecated Use Runtime::PYTHON_313 instead */
    public const PYTHON_313 = Runtime::PYTHON_313->value;

    /** @deprecated Use Runtime::PYTHON_312 instead */
    public const PYTHON_312 = Runtime::PYTHON_312->value;

    /** @deprecated Use Runtime::PYTHON_311 instead */
    public const PYTHON_311 = Runtime::PYTHON_311->value;

    /** @deprecated Use Runtime::PYTHON_310 instead */
    public const PYTHON_310 = Runtime::PYTHON_310->value;

    /** @deprecated Use Runtime::PYTHON_39 instead */
    public const PYTHON_39 = Runtime::PYTHON_39->value;

    /** @deprecated Use Runtime::PYTHON_38 instead (also deprecated by AWS) */
    public const PYTHON_38 = Runtime::PYTHON_38->value;

    /** @deprecated Use Runtime::PYTHON_37 instead (also deprecated by AWS) */
    public const PYTHON_37 = Runtime::PYTHON_37->value;

    /** @deprecated Use Runtime::JAVA_25 instead */
    public const JAVA_25 = Runtime::JAVA_25->value;

    /** @deprecated Use Runtime::JAVA_21 instead */
    public const JAVA_21 = Runtime::JAVA_21->value;

    /** @deprecated Use Runtime::JAVA_17 instead */
    public const JAVA_17 = Runtime::JAVA_17->value;

    /** @deprecated Use Runtime::JAVA_11 instead */
    public const JAVA_11 = Runtime::JAVA_11->value;

    /** @deprecated Use Runtime::JAVA_8_LINUX2 instead */
    public const JAVA_8_LINUX2 = Runtime::JAVA_8_LINUX2->value;

    /** @deprecated Use Runtime::JAVA_8 instead (also deprecated by AWS) */
    public const JAVA_8 = Runtime::JAVA_8->value;

    /** @deprecated Use Runtime::DOT_NET_9 instead */
    public const DOT_NET_9 = Runtime::DOT_NET_9->value;

    /** @deprecated Use Runtime::DOT_NET_8 instead */
    public const DOT_NET_8 = Runtime::DOT_NET_8->value;

    /** @deprecated Use Runtime::DOT_NET_7 instead (also deprecated by AWS) */
    public const DOT_NET_7 = Runtime::DOT_NET_7->value;

    /** @deprecated Use Runtime::DOT_NET_6 instead (also deprecated by AWS) */
    public const DOT_NET_6 = Runtime::DOT_NET_6->value;

    /** @deprecated Use Runtime::RUBY_34 instead */
    public const RUBY_34 = Runtime::RUBY_34->value;

    /** @deprecated Use Runtime::RUBY_33 instead */
    public const RUBY_33 = Runtime::RUBY_33->value;

    /** @deprecated Use Runtime::RUBY_32 instead */
    public const RUBY_32 = Runtime::RUBY_32->value;

    /** @deprecated Use Runtime::RUBY_27 instead (also deprecated by AWS) */
    public const RUBY_27 = Runtime::RUBY_27->value;

    /** @deprecated Use Runtime::GO_1X instead (also deprecated by AWS) */
    public const GO_1X = Runtime::GO_1X->value;

    /** @deprecated Use Runtime::PROVIDED_AL2023 instead */
    public const PROVIDED_AL2023 = Runtime::PROVIDED_AL2023->value;

    /** @deprecated Use Runtime::PROVIDED_AL2 instead */
    public const PROVIDED_AL2 = Runtime::PROVIDED_AL2->value;

    /** @deprecated Use Runtime::PROVIDED instead (also deprecated by AWS) */
    public const PROVIDED = Runtime::PROVIDED->value;
}

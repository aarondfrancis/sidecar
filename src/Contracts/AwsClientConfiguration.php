<?php

declare(strict_types=1);

namespace Hammerstone\Sidecar\Contracts;

interface AwsClientConfiguration
{
    /**
     * Retrieve an array of configuration options for Lambda,
     * Cloudwatch and S3 AWS services.
     */
    public function getConfiguration(): array;
}

<?php

namespace Hammerstone\Sidecar\Contracts;

interface AwsClientConfiguration
{
    /**
     * Retrieve an array of configuration options for Lambda,
     * Cloudwatch and S3 AWS services.
     *
     * @return array
     */
    public function getConfiguration();
}

<?php

namespace Hammerstone\Sidecar\Clients\Configurations;

use Hammerstone\Sidecar\Contracts\AwsClientConfiguration as AwsClientConfigurationContract;

class AwsClientConfiguration implements AwsClientConfigurationContract
{
    public function getConfiguration()
    {
        $config = [
            'version' => 'latest',
            'region' => config('sidecar.aws_region'),
        ];

        $credentials = array_filter([
            'key' => config('sidecar.aws_key'),
            'secret' => config('sidecar.aws_secret'),
        ]);

        if ($credentials) {
            $config['credentials'] = $credentials;
        }

        return $config;
    }
}

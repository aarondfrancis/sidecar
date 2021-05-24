<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\File;

class DetermineRegion extends BaseAction
{
    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @var bool
     */
    protected $isVapor;

    /**
     * @return string
     */
    public function invoke()
    {
        $region = config('sidecar.aws_region');

        if ($this->isValidAwsRegion($region)) {
            $this->progress("Region `$region` already set in configuration.");
        } else {
            $region = $this->getRegionFromVapor();
        }

        if (!$region) {
            $region = $this->command->choice('What AWS region would you like your functions to be deployed in?', $this->awsRegions(), 'us-east-2');
        }

        $this->progress("Using region `$region`");

        return $region;
    }

    protected function getRegionFromVapor()
    {
        if (!File::exists(base_path('vapor.yml'))) {
            return;
        }

        try {
            $region = trim(shell_exec('vapor project:describe region'));

            if (!$this->isValidAwsRegion($region)) {
                return;
            }
        } catch (Throwable $e) {
            return;
        }

        $question = implode("\n", [
            "This Vapor project deploys to the AWS `$region` region.",
            ' Would you like to use the same region for your Sidecar functions?'
        ]);

        if ($this->command->confirm($question, $default = true)) {
            return $region;
        }
    }

    protected function isValidAwsRegion($region)
    {
        return in_array($region, $this->awsRegions());
    }

    protected function awsRegions()
    {
        return [
            'us-east-1',
            'us-east-2',
            'us-west-1',
            'us-west-2',
            'af-south-1',
            'ap-east-1',
            'ap-south-1',
            'ap-southeast-1',
            'ap-northeast-2',
            'ap-northeast-3',
            'ap-northeast-1',
            'ap-southeast-2',
            'ca-central-1',
            'cn-north-1',
            'cn-northwest-1',
            'eu-central-1',
            'eu-west-1',
            'eu-west-2',
            'eu-south-1',
            'eu-west-3',
            'eu-north-1',
            'me-south-1',
            'sa-east-1',
            'us-gov-east-1',
            'us-gov-west-1',
        ];
    }
}

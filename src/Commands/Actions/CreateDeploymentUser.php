<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Aws\Iam\IamClient;
use Illuminate\Support\Arr;
use Throwable;

class CreateDeploymentUser extends BaseAction
{
    /**
     * @var IamClient
     */
    protected $client;

    /**
     * @return array
     */
    public function invoke()
    {
        $this->client = $this->command->client(IamClient::class);

        $this->createUser();

        $this->attachPolicy();

        return $this->issueCredentials();
    }

    protected function createUser()
    {
        try {
            $this->client->getUser([
                'UserName' => 'sidecar-deployment-user'
            ]);

            $this->progress('Deployment user already exists');
        } catch (Throwable $e) {
            $this->progress('Creating deployment user...');

            $this->client->createUser([
                'UserName' => 'sidecar-deployment-user'
            ]);
        }
    }

    protected function attachPolicy()
    {
        $this->progress('Attaching policy to deployment user...');

        $this->client->putUserPolicy([
            'PolicyName' => 'sidecar-deployment-policy',
            'UserName' => 'sidecar-deployment-user',
            'PolicyDocument' => json_encode($this->policy()),
        ]);
    }

    protected function policy()
    {
        return [
            'Version' => '2012-10-17',
            'Statement' => [[
                'Effect' => 'Allow',
                'Action' => 's3:*',
                'Resource' => [
                    // We only need access to sidecar buckets.
                    'arn:aws:s3:::sidecar-*',
                    'arn:aws:s3:::sidecar-*/*',
                ]
            ], [
                'Effect' => 'Allow',
                'Action' => [
                    'lambda:*',
                    'states:*',
                ],
                'Resource' => '*',
            ], [
                'Effect' => 'Allow',
                'Action' => 'iam:PassRole',
                'Resource' => '*',
                'Condition' => [
                    'StringEquals' => [
                        'iam:PassedToService' => 'lambda.amazonaws.com',
                    ],
                ],
            ], [
                'Effect' => 'Allow',
                'Action' => [
                    'logs:DescribeLogStreams',
                    'logs:GetLogEvents',
                    'logs:FilterLogEvents',
                ],
                'Resource' => 'arn:aws:logs:*:*:log-group:/aws/lambda/*',
            ], [
                'Effect' => 'Allow',
                'Action' => [
                    'ecr:GetRepositoryPolicy',
                    'ecr:SetRepositoryPolicy',
                ],
                'Resource' => '*',
            ]],
        ];
    }

    protected function issueCredentials()
    {
        $keysPresentInConfiguration = config('sidecar.aws_key') && config('sidecar.aws_secret');

        if ($keysPresentInConfiguration) {
            $this->progress('Sidecar keys already exist in your configuration, not issuing new ones.');

            return [
                'key' => config('sidecar.aws_key'),
                'secret' => config('sidecar.aws_secret'),
            ];
        }

        $keys = $this->client->listAccessKeys([
            'UserName' => 'sidecar-deployment-user',
        ]);

        if (!count($keys)) {
            return $this->createAccessKey();
        }

        $question = '' .
            "Sidecar AWS key and secret are not present in the .env file. \n" .
            " Generating a new set of credentials, which will invalidate any outstanding credentials. \n" .
            ' Continue?';

        if (!$this->command->confirm($question, $default = true)) {
            $this->command->error('Not creating new keys, make sure you populate your keys.');

            return [
                'key' => 'UNABLE_TO_DETERMINE',
                'secret' => 'UNABLE_TO_DETERMINE',
            ];
        }

        if ($accessKeyId = Arr::get($keys, 'AccessKeyMetadata.0.AccessKeyId')) {
            $this->progress('Deleting old keys...');

            $this->client->deleteAccessKey([
                'AccessKeyId' => $accessKeyId,
                'UserName' => 'sidecar-deployment-user',
            ]);
        }

        return $this->createAccessKey();
    }

    protected function createAccessKey()
    {
        $this->progress('Creating new keys...');

        $result = $this->client->createAccessKey([
            'UserName' => 'sidecar-deployment-user',
        ]);

        return [
            'key' => Arr::get($result, 'AccessKey.AccessKeyId'),
            'secret' => Arr::get($result, 'AccessKey.SecretAccessKey'),
        ];
    }
}

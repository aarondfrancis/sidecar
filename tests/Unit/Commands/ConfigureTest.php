<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Aws\Command;
use Aws\Iam\Exception\IamException;
use Aws\Iam\IamClient;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Support\Carbon;
use Mockery;

class ConfigureTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2021-05-24 00:00:00');
    }

    protected function mockS3($callable)
    {
        $mock = Mockery::mock(S3Client::class, $callable);

        $this->app->singleton(S3Client::class, function () use ($mock) {
            return $mock;
        });
    }

    protected function mockIam($callable)
    {
        $mock = Mockery::mock(IamClient::class, $callable);

        $this->app->singleton(IamClient::class, function () use ($mock) {
            return $mock;
        });
    }

    protected function mockHeadBucketNotFound($mock)
    {
        $mock->shouldReceive('headBucket')
            ->once()
            ->with([
                'Bucket' => 'sidecar-us-east-1-1621814400',
            ])
            ->andThrow($this->bucketNotFoundException());
    }

    protected function bucketNotFoundException()
    {
        return new S3Exception('403 Forbidden', new Command('headBucket'));
    }

    protected function mockCreateBucket($mock)
    {
        $mock->shouldReceive('createBucket')
            ->once()
            ->with([
                'ACL' => 'private',
                'Bucket' => 'sidecar-us-east-1-1621814400',
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => 'us-east-1',
                ],
            ]);
    }

    protected function mockRoleNotFound($mock)
    {
        $mock->shouldReceive('getRole')
            ->once()
            ->with([
                'RoleName' => 'sidecar-execution-role'
            ])
            ->andThrow(
                new IamException('Not found', new Command('GetRole'))
            );
    }

    protected function mockCreateRole($mock)
    {
        $mock->shouldReceive('createRole')
            ->once()
            ->with([
                'RoleName' => 'sidecar-execution-role',
                'AssumeRolePolicyDocument' => json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [[
                        'Effect' => 'Allow',
                        'Principal' => [
                            'Service' => 'lambda.amazonaws.com'
                        ],
                        'Action' => 'sts:AssumeRole'
                    ]]
                ])
            ])
            ->andReturn($this->roleExistsResponse());
    }

    protected function mockPuttingPolicy($mock)
    {
        $mock->shouldReceive('putRolePolicy')
            ->once()
            ->with([
                'PolicyName' => 'sidecar-execution-policy',
                'RoleName' => 'sidecar-execution-role',
                'PolicyDocument' => json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [[
                        'Effect' => 'Allow',
                        'Resource' => '*',
                        'Action' => [
                            'logs:CreateLogGroup',
                            'logs:CreateLogStream',
                            'logs:FilterLogEvents',
                            'logs:PutLogEvents',
                            'lambda:invokeFunction',
                            's3:*',
                        ],
                    ]]
                ]),
            ]);
    }

    protected function roleExistsResponse()
    {
        return [
            'Role' => [
                'Path' => '/',
                'RoleName' => 'sidecar-execution-role',
                'RoleId' => 'XXX',
                'Arn' => 'arn:aws:iam::XXX:role/sidecar-execution-role',
                'AssumeRolePolicyDocument' => '%7B%22Version%22%3A%222012-10-17%22%2C%22Statement%22%3A%5B%7B%22Effect%22%3A%22Allow%22%2C%22Principal%22%3A%7B%22Service%22%3A%22lambda.amazonaws.com%22%7D%2C%22Action%22%3A%22sts%3AAssumeRole%22%7D%5D%7D',
                'MaxSessionDuration' => 3600,
            ],
        ];
    }

    protected function createRolePayload()
    {
        return [
            'RoleName' => 'sidecar-execution-role',
            'AssumeRolePolicyDocument' => json_encode([
                'Version' => '2012-10-17',
                'Statement' => [[
                    'Effect' => 'Allow',
                    'Principal' => [
                        'Service' => 'lambda.amazonaws.com'
                    ],
                    'Action' => 'sts:AssumeRole'
                ]]
            ]),
        ];
    }

    /** @test */
    public function basic_happy_path()
    {
        $this->mockS3(function ($mock) {
            $this->mockHeadBucketNotFound($mock);
            $this->mockCreateBucket($mock);
        });

        $this->mockIam(function ($mock) {
            $this->mockRoleNotFound($mock);
            $this->mockCreateRole($mock);
            $this->mockPuttingPolicy($mock);
            $this->mockDeploymentUserNotFound($mock);
            $this->mockCreateDeploymentUser($mock);
            $this->mockPutDeploymentUserPolicy($mock);
            $this->mockListAccessKeys($mock);
            $this->mockCreateAccessKeys($mock);
            $this->mockWhoAmI($mock);
        });

        $artisan = $this->artisan('sidecar:configure');

        $artisan->expectsQuestion('Enter the Access key ID', 'id');
        $artisan->expectsQuestion('Enter the Secret access key', 'secret');
        $artisan->expectsQuestion('What AWS region would you like your functions to be deployed in?', 'us-east-1');

        $artisan->expectsOutput('==> Bucket doesn\'t exist.');
        $artisan->expectsOutput('==> Trying to create bucket...');
        $artisan->expectsOutput('==> Bucket created');
        $artisan->expectsOutput('==> Creating an execution role for your functions...');
        $artisan->expectsOutput('==> Attaching policy to execution role...');
        $artisan->expectsOutput('==> Creating deployment user...');
        $artisan->expectsOutput('==> Creating new keys...');

        $artisan->expectsQuestion(
            'Now that everything is setup, would you like to remove the admin access keys for user `sidecar-cli-helper` from AWS?' .
            " \n Sidecar no longer needs them.",
            false
        );

        $artisan->expectsOutput('SIDECAR_ACCESS_KEY_ID=AK-XXXX');
        $artisan->expectsOutput('SIDECAR_SECRET_ACCESS_KEY=SK-XXXX');
        $artisan->expectsOutput('SIDECAR_REGION=us-east-1');
        $artisan->expectsOutput('SIDECAR_ARTIFACT_BUCKET_NAME=sidecar-us-east-1-1621814400');
        $artisan->expectsOutput('SIDECAR_EXECUTION_ROLE=arn:aws:iam::XXX:role/sidecar-execution-role');
    }

    protected function mockDeploymentUserNotFound($mock): void
    {
        $mock->shouldReceive('getUser')
            ->once()
            ->with([
                'UserName' => 'sidecar-deployment-user'
            ])
            ->andThrow(
                new IamException('Not found', new Command('getUser'))
            );
    }

    protected function mockCreateDeploymentUser($mock): void
    {
        $mock->shouldReceive('createUser')
            ->once()
            ->with([
                'UserName' => 'sidecar-deployment-user'
            ]);
    }

    protected function mockPutDeploymentUserPolicy($mock): void
    {
        $mock->shouldReceive('putUserPolicy')
            ->once()
            ->with([
                'PolicyName' => 'sidecar-deployment-policy',
                'UserName' => 'sidecar-deployment-user',
                'PolicyDocument' => json_encode([
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
                ]),
            ]);
    }

    protected function mockListAccessKeys($mock): void
    {
        $mock->shouldReceive('listAccessKeys')
            ->once()
            ->with([
                'UserName' => 'sidecar-deployment-user',
            ])
            ->andReturn([]);
    }

    protected function mockCreateAccessKeys($mock): void
    {
        $mock->shouldReceive('createAccessKey')
            ->once()
            ->with([
                'UserName' => 'sidecar-deployment-user',
            ])
            ->andReturn([
                'AccessKey' => [
                    'AccessKeyId' => 'AK-XXXX',
                    'SecretAccessKey' => 'SK-XXXX'
                ]
            ]);
    }

    protected function mockWhoAmI($mock): void
    {
        $mock->shouldReceive('getUser')
            ->withNoArgs()
            ->andReturn([
                'User' => [
                    'UserName' => 'sidecar-cli-helper'
                ]
            ]);
    }
}

<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Aws\Iam\IamClient;
use Illuminate\Support\Arr;
use Throwable;

class CreateExecutionRole extends BaseAction
{
    /**
     * @var IamClient
     */
    protected $client;

    public function invoke()
    {
        $this->progress('Creating an execution role for your functions...');

        $this->client = $this->command->client(IamClient::class);

        $role = Arr::get($this->findOrCreateRole(), 'Role.Arn');

        $this->attachPolicy();

        return $role;
    }

    public function roleName()
    {
        return 'sidecar-execution-role';
    }

    protected function findOrCreateRole()
    {
        try {
            $role = $this->client->getRole([
                'RoleName' => 'sidecar-execution-role'
            ]);

            $this->progress('Role already exists');
        } catch (Throwable $e) {
            $role = $this->createRole();
        }

        return $role;
    }

    protected function createRole()
    {
        return $this->client->createRole([
            'RoleName' => $this->roleName(),
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
        ]);
    }

    protected function attachPolicy()
    {
        $this->progress('Attaching policy to execution role...');

        $this->client->putRolePolicy([
            'PolicyName' => 'sidecar-execution-policy',
            'RoleName' => $this->roleName(),
            'PolicyDocument' => json_encode($this->policy()),
        ]);
    }

    protected function policy()
    {
        return [
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
        ];
    }
}

<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Aws\Iam\IamClient;
use Throwable;

class DestroyAdminKeys extends BaseAction
{
    public $key;

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function invoke()
    {
        $client = $this->command->client(IamClient::class);

        try {
            $user = $client->getUser();
        } catch (Throwable $e) {
            return;
        }

        $name = $user['User']['UserName'];

        $question = '' .
            "Now that everything is setup, would you like to remove the admin access keys for user `$name` from AWS? \n" .
            ' Sidecar no longer needs them.';

        if (!$this->command->confirm($question, $default = true)) {
            return;
        }

        $this->progress('Deleting admin keys...');

        try {
            $client->deleteAccessKey([
                'AccessKeyId' => $this->key,
                'UserName' => $name,
            ]);
        } catch (Throwable $e) {
            $this->command->error('Unable to delete keys! You may do it manually.');
        }

        $this->progress('Admin keys deleted');
    }
}

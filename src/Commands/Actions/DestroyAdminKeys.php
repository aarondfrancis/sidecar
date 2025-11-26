<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Aws\Iam\IamClient;
use Throwable;

class DestroyAdminKeys extends BaseAction
{
    public ?string $key = null;

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function invoke(): mixed
    {
        $client = $this->command->client(IamClient::class);

        try {
            $user = $client->getUser();
        } catch (Throwable $e) {
            return null;
        }

        $name = $user['User']['UserName'];

        $isSidecar = $name === 'sidecar-cli-helper';

        if (!$isSidecar) {
            $this->command->info('');
            $this->command->error(' ********************************************************************************* ');
            $this->command->error(' *                                                                               * ');
            $this->command->error(' *  The admin keys you provided are not Sidecar specific. Be cautious deleting.  * ');
            $this->command->error(' *                                                                               * ');
            $this->command->error(' ********************************************************************************* ');
        }

        $question = '' .
            "Now that everything is setup, would you like to remove the admin access keys for user `$name` from AWS? \n" .
            ' Sidecar no longer needs them.';

        // If the keys were created specifically for us, we can default to `true`,
        // because that is most safe. If not, defaulting to `false` is most safe!
        if (!$this->command->confirm($question, $default = $isSidecar)) {
            $this->progress('Not deleting keys');

            return null;
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

        return null;
    }
}

<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Exception;
use Hammerstone\Sidecar\Commands\Actions\CreateBucket;
use Hammerstone\Sidecar\Commands\Actions\CreateDeploymentUser;
use Hammerstone\Sidecar\Commands\Actions\CreateExecutionRole;
use Hammerstone\Sidecar\Commands\Actions\DestroyAdminKeys;
use Hammerstone\Sidecar\Commands\Actions\DetermineRegion;
use Illuminate\Console\Command;
use Throwable;

class Configure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidecar:configure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively configure your Sidecar AWS environment variables.';

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $region;

    /**
     * @var int
     */
    protected $width = 75;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle()
    {
        $this->askForAdminCredentials();

        $this->region = $this->action(DetermineRegion::class)->invoke();

        $bucket = $this->action(CreateBucket::class)->invoke();

        $role = $this->action(CreateExecutionRole::class)->invoke();

        $credentials = $this->action(CreateDeploymentUser::class)->invoke();

        $this->action(DestroyAdminKeys::class)->setKey($this->key)->invoke();

        $this->line(' ');
        $this->info('Done! Here are your environment variables:');
        $this->line('SIDECAR_ACCESS_KEY_ID=' . $credentials['key']);
        $this->line('SIDECAR_SECRET_ACCESS_KEY=' . $credentials['secret']);
        $this->line('SIDECAR_REGION=' . $this->region);
        $this->line('SIDECAR_ARTIFACT_BUCKET_NAME=' . $bucket);
        $this->line('SIDECAR_EXECUTION_ROLE=' . $role);
        $this->line(' ');
        $this->info('They will work in any environment.');
    }

    public function text($text)
    {
        $this->line(wordwrap($text, $this->width));
    }

    public function client($class)
    {
        return app()->make($class, [
            'args' => [
                'region' => $this->region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $this->key,
                    'secret' => $this->secret
                ]
            ]
        ]);
    }

    protected function action($class)
    {
        return app()->make($class, [
            'region' => $this->region,
            'command' => $this,
        ]);
    }

    protected function askForAdminCredentials()
    {
        $this->line(str_repeat('-', $this->width));
        $this->text('This interactive command will help you set up your Sidecar credentials for all your environments.');
        $this->line('');
        $this->text('To start, Sidecar needs a set of AWS Credentials with Administrator Access.');
        $this->line('');
        $this->text('We will only use these for this session, then they will be forgotten.');
        $this->line('');
        $this->text('Visit this link: https://console.aws.amazon.com/iam/home#/users');
        $this->text(' --> Click "Add User."');
        $this->text(' ');
        $this->text(' --> Enter "sidecar-cli-helper" as the name.');
        $this->text(' --> Choose "Programmatic access."');
        $this->text(' --> Press "Next: Permissions."');
        $this->text(' ');
        $this->text(' --> Choose "Attach existing policies directly."');
        $this->text(' --> Select "AdministratorAccess."');
        $this->text(' ');
        $this->text(' --> Click "Next: Tags."');
        $this->text(' --> Click "Next: Review."');
        $this->text(' --> Click "Create user."');
        $this->line(str_repeat('-', $this->width));

        $this->key = $this->ask('Enter the Access key ID');
        $this->secret = $this->secret('Enter the Secret access key');

        if ($this->key && $this->secret) {
            $this->text('Got it! We will start creating resources now...');
        } else {
            throw new Exception('Key or secret not entered.');
        }
    }
}

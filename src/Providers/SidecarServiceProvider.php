<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Providers;

use Hammerstone\Sidecar\Clients\CloudWatchLogsClient;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Commands\Activate;
use Hammerstone\Sidecar\Commands\Configure;
use Hammerstone\Sidecar\Commands\Deploy;
use Hammerstone\Sidecar\Commands\Install;
use Hammerstone\Sidecar\Commands\Warm;
use Hammerstone\Sidecar\Manager;
use Hammerstone\Sidecar\Vercel\Client as VercelClient;
use Illuminate\Support\ServiceProvider;

class SidecarServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Manager::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/sidecar.php', 'sidecar');

        $this->app->singleton(LambdaClient::class, function () {
            return new LambdaClient($this->getAwsClientConfiguration());
        });

        $this->app->singleton(CloudWatchLogsClient::class, function () {
            return new CloudWatchLogsClient($this->getAwsClientConfiguration());
        });

        $this->app->singleton(VercelClient::class, function () {
            return new VercelClient($this->getVercelConfiguration());
        });
    }

    protected function getVercelConfiguration()
    {
        return [
            'base_uri' => 'https://api.vercel.com',
            'allow_redirects' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . config('sidecar.vercel_token'),
            ]
        ];
    }

    protected function getAwsClientConfiguration()
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

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Activate::class,
                Configure::class,
                Warm::class,
                Deploy::class,
                Install::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../../config/sidecar.php' => config_path('sidecar.php')
        ], 'config');
    }
}

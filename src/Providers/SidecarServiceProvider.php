<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Providers;

use Hammerstone\Sidecar\Clients\CloudWatchLogsClient;
use Hammerstone\Sidecar\Clients\Configurations\AwsClientConfiguration;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Clients\S3Client;
use Hammerstone\Sidecar\Commands\Activate;
use Hammerstone\Sidecar\Commands\Configure;
use Hammerstone\Sidecar\Commands\Deploy;
use Hammerstone\Sidecar\Commands\Install;
use Hammerstone\Sidecar\Commands\Warm;
use Hammerstone\Sidecar\Contracts\AwsClientConfiguration as AwsClientConfigurationContract;
use Hammerstone\Sidecar\Manager;
use Illuminate\Support\ServiceProvider;

class SidecarServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Manager::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/sidecar.php', 'sidecar');

        $this->app->bind(AwsClientConfigurationContract::class, AwsClientConfiguration::class);

        $this->app->singleton(LambdaClient::class, function () {
            return new LambdaClient($this->getAwsClientConfiguration());
        });

        $this->app->singleton(CloudWatchLogsClient::class, function () {
            return new CloudWatchLogsClient($this->getAwsClientConfiguration());
        });

        $this->app->singleton(S3Client::class, function () {
            return new S3Client($this->getAwsClientConfiguration());
        });
    }

    protected function getAwsClientConfiguration()
    {
        return $this->app->make(AwsClientConfigurationContract::class)->getConfiguration();
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

<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Providers;

use Hammerstone\Sidecar\Commands\Deploy;
use Hammerstone\Sidecar\Commands\Install;
use Hammerstone\Sidecar\LambdaClient;
use Hammerstone\Sidecar\Manager;
use Illuminate\Support\ServiceProvider;

class SidecarServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Manager::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/sidecar.php', 'sidecar');

        $this->app->singleton(LambdaClient::class, function () {
            return new LambdaClient([
                'version' => 'latest',
                'region' => config('sidecar.aws_region'),
                'credentials' => [
                    'key' => config('sidecar.aws_key'),
                    'secret' => config('sidecar.aws_secret'),
                ]
            ]);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
                Deploy::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../../config/sidecar.php' => config_path('sidecar.php')
        ], 'config');
    }

}
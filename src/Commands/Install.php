<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Providers\SidecarServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidecar:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Sidecar config file into your app.';

    public function __construct()
    {
        parent::__construct();

        if (file_exists(config_path('sidecar.php'))) {
            $this->setHidden(true);
        }
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        Artisan::call('vendor:publish', [
            '--provider' => SidecarServiceProvider::class
        ]);

        $this->info('Config file published!');
    }
}

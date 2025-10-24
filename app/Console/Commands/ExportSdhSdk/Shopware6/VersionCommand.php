<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\VersionController;

class VersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Get Shopware 6 version';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $versionController = new VersionController;
        $response = $versionController->get();
        echo $response->version.PHP_EOL;

        return self::SUCCESS;
    }
}

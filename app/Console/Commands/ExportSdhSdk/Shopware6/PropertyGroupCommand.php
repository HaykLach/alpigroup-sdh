<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimProductExporter;
use App\Controllers\Export\PimPropertyGroupExporter;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\PropertyGroupController;

class PropertyGroupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:property-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export PropertyGroups to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('sync propertyGroups');
        $propertyGroupController = new PropertyGroupController;

        $propertyGroupExporter = new PimPropertyGroupExporter($propertyGroupController);
        $propertyGroupExporter->sync();

        // $this->testCRUD($exporter);

        return self::SUCCESS;
    }

    protected function testCRUD(PimProductExporter $exporter): void {}

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

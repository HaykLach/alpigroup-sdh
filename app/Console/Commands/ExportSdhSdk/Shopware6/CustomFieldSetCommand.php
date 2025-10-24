<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimCustomFieldSetExporter;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\CustomFieldController;
use SmartDato\SdhShopwareSdk\Controllers\CustomFieldSetController;

class CustomFieldSetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:custom-field-set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export CustomFieldSets to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('sync customFieldSets');

        $customFieldSetController = new CustomFieldSetController;
        $customFieldController = new CustomFieldController;
        // $this->testCRUD($customFieldSetController);

        $customFieldSetExporter = new PimCustomFieldSetExporter($customFieldSetController, $customFieldController);
        $customFieldSetExporter->sync();

        return self::SUCCESS;
    }

    protected function testCRUD(CustomFieldSetController $customFieldSetController): void {}

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

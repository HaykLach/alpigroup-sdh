<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimCurrencyExporter;
use App\Controllers\Export\PimCustomFieldSetExporter;
use App\Controllers\Export\PimProductExporter;
use App\Controllers\Export\PimProductManufacturerExporter;
use App\Controllers\Export\PimPropertyGroupExporter;
use App\Controllers\Export\PimTaxExporter;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\CurrencyController;
use SmartDato\SdhShopwareSdk\Controllers\CustomFieldController;
use SmartDato\SdhShopwareSdk\Controllers\CustomFieldSetController;
use SmartDato\SdhShopwareSdk\Controllers\ProductManufacturerController;
use SmartDato\SdhShopwareSdk\Controllers\PropertyGroupController;
use SmartDato\SdhShopwareSdk\Controllers\TaxController;

class PeriodicUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:periodic-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export periodic update';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('sync taxes');
        (new PimTaxExporter(new TaxController))->sync();

        $this->info('sync currencies');
        (new PimCurrencyExporter(new CurrencyController))->sync();

        $this->info('sync manufacturers');
        (new PimProductManufacturerExporter(new ProductManufacturerController))->sync();

        $this->info('sync propertyGroups');
        (new PimPropertyGroupExporter(new PropertyGroupController))->sync();

        $this->info('sync customFieldSets');
        (new PimCustomFieldSetExporter(new CustomFieldSetController, new CustomFieldController))->sync();

        $this->info('sync products');
        (new PimProductExporter)->sync();

        return self::SUCCESS;
    }

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

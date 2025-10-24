<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimProductManufacturerExporter;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\ProductManufacturerController;

class ProductManufacturerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:manufacturer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export Manufacturers to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $productManufacturerController = new ProductManufacturerController;

        $this->info('sync manufacturers');
        $exporter = new PimProductManufacturerExporter($productManufacturerController);
        $exporter->sync();

        // $this->testCRUD($productManufacturerController);

        return self::SUCCESS;
    }

    protected function testCRUD(ProductManufacturerController $sdk): void
    {
        $this->info('list manufacturers');
        $list = $sdk->list();
        $this->print($list->count());

        $this->info('create manufacturers testManufacturer');
        $create = $sdk->create('testManufacturer');
        $this->print($create);

        $this->info('get testManufacturer');
        $item = $sdk->get($create->id);
        $this->print($item);

        $this->info('update testManufacturer -> testManufacturer2');
        $update = $sdk->update($item->id, ['name' => 'testManufacturer2']);
        $this->print($update);

        $this->info('list manufacturers');
        $list = $sdk->list();
        $this->print($list->count());

        $this->info('get testManufacturer2');
        $item = $sdk->get($update->id);
        $this->print($item);

        $this->info('delete testManufacturer2');
        $delete = $sdk->delete($item->id);
        $this->print($delete);

        $this->info('list manufacturers');
        $list = $sdk->list();
        $this->print($list->count());
    }

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

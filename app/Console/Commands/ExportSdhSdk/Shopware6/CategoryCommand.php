<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\CategoryController;

class CategoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export Categories to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sdk = new CategoryController;
        /*
        $exporter = new PimTaxExporter($sdk);
        $exporter->sync();
        */

        $this->testCRUD($sdk);

        return self::SUCCESS;
    }

    protected function testCRUD(CategoryController $sdk): void
    {
        $this->info('list categories');
        $list = $sdk->list();
        $this->info('listcount = '.$list->count());

        $this->info('create Category testCategory');
        $create = $sdk->create('testCategory');
        $this->print($create);

        $this->info('get Category testCategory');
        $item = $sdk->get($create->id);
        $this->print($item);

        $this->info('update Category testCategory -> testCategory2');
        $update = $sdk->update($item->id, 'testCategory2');
        $this->print($update);

        $this->info('get Category testCategory2');
        $item = $sdk->get($update->id);
        $this->print($item);

        $this->info('delete Category testCategory2');
        $delete = $sdk->delete($item->id);
        $this->print($delete);

        $this->info('list categories');
        $list = $sdk->list();
        $this->info('listcount = '.$list->count());
    }

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

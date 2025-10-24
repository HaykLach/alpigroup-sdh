<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimTaxExporter;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\TaxController;

class TaxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:tax';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export Taxes to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /**
         * list shopware taxes
         * detect if tax value exists in pim
         * assign taxes in sw6_sdk_tax_extension
         */
        $sdk = new TaxController;

        $this->info('sync taxes');
        $exporter = new PimTaxExporter($sdk);
        $exporter->sync();

        // $this->testCRUD($sdk);

        return self::SUCCESS;
    }

    protected function testCRUD(TaxController $sdk): void
    {
        $this->info('list taxes');
        $list = $sdk->list();
        $this->print($list);

        $this->info('create Tax 30%');
        $create = $sdk->create(30, '30%', 10);
        $this->print($create);

        $this->info('get Tax 30%');
        $item = $sdk->get($create->id);
        $this->print($item);

        $this->info('update Tax 30% -> 31.4%');
        $update = $sdk->update($item->id, 31.4, '31.4%', 10);
        $this->print($update);

        $this->info('get Tax 31.4%');
        $item = $sdk->get($update->id);
        $this->print($item);

        $this->info('delete Tax 31.4%');
        $delete = $sdk->delete($item->id);
        $this->print($delete);

        $this->info('list taxes');
        $list = $sdk->list();
        $this->print($list);
    }

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

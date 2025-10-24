<?php

namespace App\Console\Commands\ExportSdhSdk\Shopware6;

use App\Controllers\Export\PimCurrencyExporter;
use Illuminate\Console\Command;
use SmartDato\SdhShopwareSdk\Controllers\CurrencyController;

class CurrencyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:export:currency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SmartDato Hub Export Currency to Shopware 6';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $currencyController = new CurrencyController;

        $this->info('sync currencies');
        $exporter = new PimCurrencyExporter($currencyController);
        $exporter->sync();

        // $this->testCRUD($currencyController);

        return self::SUCCESS;
    }

    protected function testCRUD(CurrencyController $currencyController): void
    {
        $this->info('list currencies');
        $list = $currencyController->list();
        $this->print($list->count());

        $currency = [
            'iso_code' => 'xyz',
            'short_name' => 'short',
            'name' => 'test',
        ];
        $this->info('create Currency testCurrency');
        $create = $currencyController->create($currency['iso_code'], $currency['iso_code'], $currency['short_name'], $currency['name']);
        $this->print($create);

        $this->info('list currencies');
        $list = $currencyController->list();
        $this->print($list->count());

        $this->info('get Currency testCurrency');
        $item = $currencyController->get($create->id);
        $this->print($item);

        $currency = [
            'iso_code' => 'xyy',
            'short_name' => 'short2',
            'name' => 'test2',
        ];
        $this->info('update testCurrency -> testCurrency2');
        $update = $currencyController->update($item->id, $currency['iso_code'], $currency['iso_code'], $currency['short_name'], $currency['name']);
        $this->print($update);

        $this->info('get Currency testCurrency2');
        $item = $currencyController->get($update->id);
        $this->print($item);

        $this->info('delete Currency testCurrency2');
        $delete = $currencyController->delete($item->id);
        $this->print($delete);

        $this->info('list currencies');
        $list = $currencyController->list();
        $this->print($list->count());
    }

    protected function print($object): void
    {
        echo json_encode($object, JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL;
    }
}

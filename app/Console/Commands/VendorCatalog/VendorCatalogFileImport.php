<?php

namespace App\Console\Commands\VendorCatalog;

use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Models\VendorCatalog\VendorCatalogVendor;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Illuminate\Console\Command;

class VendorCatalogFileImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vc:file-import {vendor}{definition}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import vendor catalog file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $vendor = VendorCatalogVendor::firstWhere('name', $this->argument('vendor'));
        if ($vendor === null) {
            $this->output->error('Vendor "'.$this->argument('vendor').'" not found');

            return;
        }

        $definition = VendorCatalogImportDefinition::where('vendor_catalog_vendor_id', $vendor->id)->where('name', $this->argument('definition'))->first();
        if ($definition === null) {
            $this->output->error('VendorCatalogImportDefinition "'.$this->argument('definition').'" not found');

            return;
        }

        $app = app(VendorCatalogFileImportService::class);
        // $app->truncateRecords($definition);
        $app->importFile($definition);
    }
}

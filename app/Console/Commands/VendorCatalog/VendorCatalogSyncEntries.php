<?php

namespace App\Console\Commands\VendorCatalog;

use App\Models\VendorCatalog\VendorCatalogImport;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Illuminate\Console\Command;

class VendorCatalogSyncEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vc:sync-entries {fileId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert records from vendor catalog file into entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileId = $this->argument('fileId');
        $file = VendorCatalogImport::findOrFail($fileId);

        app(VendorCatalogFileImportService::class)
            ->importEntries(import: $file);

    }
}

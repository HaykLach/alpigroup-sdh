<?php

namespace App\Console\Commands\VendorCatalog;

use App\Models\VendorCatalog\VendorCatalogImport;
use App\Services\VendorCatalog\VendorCatalogFileImportService;
use Illuminate\Console\Command;

class VendorCatalogFileParse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vc:file-parse {fileId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates from vc file vc record';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $fileId = $this->argument('fileId');
        $file = VendorCatalogImport::findOrFail($fileId);

        app(VendorCatalogFileImportService::class)
            ->importRecords(import: $file);
    }
}

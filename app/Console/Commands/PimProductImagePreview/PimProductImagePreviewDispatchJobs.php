<?php

namespace App\Console\Commands\PimProductImagePreview;

use App\Models\Pim\Product\PimProductImage;
use App\Services\Pim\Import\PimProductImageService;
use Illuminate\Console\Command;

class PimProductImagePreviewDispatchJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:product-preview-images {--addJobs} {--truncateTables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch preview images jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $productImageService = new PimProductImageService;

        $truncateTables = $this->option('truncateTables');
        $addJobs = $this->option('addJobs');

        if ($truncateTables) {
            PimProductImage::query()->forceDelete();
        }

        if ($addJobs) {
            $productImageService->addUniqueProductUrls();
        }

        $productImageService->dispatchJobs();

        return self::SUCCESS;
    }
}

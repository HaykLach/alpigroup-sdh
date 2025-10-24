<?php

namespace App\Console\Commands\PimProductImageColor;

use App\Services\MediaLibrary\OpenAiImageGetColorService;
use App\Services\Pim\PimProductService;
use App\Settings\GeneralSettings;
use Illuminate\Console\Command;

class PimProductImageColorDetermine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sdh:image-color-determine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OpenAi Image Color - get color from image, store in PimProduct custom fields';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $verbose = $this->option('verbose');

        if (! (new GeneralSettings)->openai_enabled) {
            if ($verbose) {
                $this->output->error('OpenAI is not enabled');
            }

            return self::FAILURE;
        }

        $getColorService = new OpenAiImageGetColorService;
        $propertyGroupFilterId = $getColorService->getPropertyGroupColorFilterId();
        if ($propertyGroupFilterId === null) {
            $this->output->error('property group filter not found');

            return self::FAILURE;
        }

        $productIds = PimProductService::getProductVariationIdsMissingPropertyByGroupId($propertyGroupFilterId)->pluck('id');
        // $productIds = PimProductService::getProductsHavingImagesProductIds()->pluck('id');

        $processed = [];
        $remaining = count($productIds);

        $productIds->each(function ($productId) use ($propertyGroupFilterId, $getColorService, &$remaining, &$processed, $verbose) {

            $remaining--;
            if (isset($processed[$productId])) {
                return;
            }

            if ($verbose) {
                echo 'remaining: '.$remaining.' :: ';
            }

            $processedInRequest = $getColorService->request($productId, $propertyGroupFilterId);
            $processed = array_merge($processed, $processedInRequest);
        });

        return self::SUCCESS;
    }
}

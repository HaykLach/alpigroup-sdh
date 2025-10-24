<?php

namespace App\Console\Commands\VendorCatalog;

use App\Console\Commands\VendorCatalog\PoolCoverPriceList\DataProcessor;
use App\Console\Commands\VendorCatalog\PoolCoverPriceList\FileHandler;
use Illuminate\Console\Command;

class VendorCatalogPoolCoverPriceList extends Command
{
    /**
     * Default directory path for the pool cover price list CSV files.
     */
    protected const string DEFAULT_DIRECTORY_PATH = 'pool-cover-pricelists';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vc:pool-cover-pricelist
                            {--directory='.self::DEFAULT_DIRECTORY_PATH.' : Directory containing CSV files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all pool cover price list CSV files in a directory and generate structured array';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing pool cover price lists...');

        $directoryPath = $this->option('directory');

        if (! FileHandler::validateInputDirectory($directoryPath)) {
            $this->error("Directory not found: $directoryPath");

            return self::FAILURE;
        }

        $data = DataProcessor::getPoolCoverPriceListData($directoryPath, self::DEFAULT_DIRECTORY_PATH);

        if (empty($data->getProducts())) {
            $this->error('Failed to process the pool cover price lists.');

            return self::FAILURE;
        }

        $this->info('Successfully processed pool cover price lists.');

        // Insert or update products to PimProducts
        $this->info('Inserting or updating products to PimProducts...');
        $data->insertOrUpdateToPimProducts();
        $this->info('Finished inserting or updating products to PimProducts.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands\VendorCatalog\PoolCoverPriceList\DTO;

use App\Enums\Pim\PimProductPriceListTypes;
use App\Models\Pim\Product\PimProduct;
use Illuminate\Support\Facades\Log;

class PoolCoverPriceListDTO
{
    /**
     * @var PoolCoverProductDTO[]
     */
    private array $products = [];

    public function addProduct(PoolCoverProductDTO $product): void
    {
        $this->products[$product->getProductNumber()] = $product;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public static function fromArray(array $data): self
    {
        $dto = new self;
        foreach ($data as $productNumber => $productData) {
            $dto->addProduct(PoolCoverProductDTO::fromArray($productNumber, $productData));
        }

        return $dto;
    }

    public function insertOrUpdateToPimProducts(): void
    {
        foreach ($this->products as $product) {
            $productNumber = $product->getProductNumber();

            // Check if product exists
            $pimProduct = PimProduct::where('product_number', $productNumber)->first();

            if ($pimProduct) {
                $pimProduct->update([
                    'prices->'.PimProductPriceListTypes::POOL_COVER->value => $product->toPricesArray(),
                ]);
            } else {
                $errorMessage = "Product with number {$productNumber} does not exist in PimProducts";
                Log::error($errorMessage);
                echo $errorMessage.PHP_EOL;
            }
        }
    }
}

<?php

namespace App\Services\Export;

use App\Models\Pim\Product\PimProduct;
use SmartDato\SdhShopwareSdk\DataTransferObjects\Product;
use SmartDato\Shopware6\App\Models\Shopware6Product\Shopware6ProductExtension;

class PimProductExportDataHashService
{
    public function checkIfRequestNeeded(PimProduct $pimProduct, Product $product): bool
    {
        $requestNeeded = false;

        $id = GenerateIdService::getProductId($pimProduct);

        $exportProduct = $this->getRecord($id);
        if ($exportProduct === null) {
            $requestNeeded = true;
        }

        $hash = $this->getHash($product);
        if ($requestNeeded) {
            $this->upsertRecord($id, $hash, $pimProduct, $product);

        } elseif (! $this->compareData($hash, $exportProduct)) {
            $requestNeeded = true;
        }

        return $requestNeeded;
    }

    protected function getHash(Product $product): string
    {
        return hash('sha256', json_encode($product));
    }

    protected function getRecord(string $id): ?Shopware6ProductExtension
    {
        return Shopware6ProductExtension::find($id);
    }

    protected function upsertRecord(string $id, string $hash, PimProduct $pimProduct, Product $product)
    {
        Shopware6ProductExtension::upsert([
            'id' => $id,
            'pim_product_id' => $pimProduct->id,
            'shopware_product_id' => $product->id,
            'product_body_hash' => $hash,
        ], ['id']);
    }

    protected function compareData(string $hash, Shopware6ProductExtension $product): bool
    {
        return $hash === $product->product_body_hash;
    }
}

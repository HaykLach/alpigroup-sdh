<?php

namespace App\Console\Commands\VendorCatalog\PoolCoverPriceList\DTO;

class PoolCoverProductDTO
{
    private string $productNumber;

    private string $title;

    private array $prices = [];

    public function __construct(string $productNumber, string $title)
    {
        $this->productNumber = $productNumber;
        $this->title = $title;
    }

    public function getProductNumber(): string
    {
        return $this->productNumber;
    }

    public function setPrice(int $height, int $width, int $length, float $price): void
    {
        if (! isset($this->prices[$height])) {
            $this->prices[$height] = [];
        }
        if (! isset($this->prices[$height][$width])) {
            $this->prices[$height][$width] = [];
        }
        $this->prices[$height][$width][$length] = $price;
    }

    public function toArray(): array
    {
        $result = [
            'product_number' => $this->productNumber,
            'title' => $this->title,
        ];

        // Add prices
        foreach ($this->prices as $width => $heightPrices) {
            $result[$width] = $heightPrices;
        }

        return $result;
    }

    public function toPricesArray(): array
    {
        $result = [];

        // Add prices
        foreach ($this->prices as $height => $heightPrices) {
            foreach ($heightPrices as $width => $widths) {
                foreach ($widths as $length => $price) {
                    if (! isset($result[$height])) {
                        $result[$height] = [];
                    }
                    if (! isset($result[$height][$width])) {
                        $result[$height][$width] = [];
                    }
                    $result[$height][$width][$length] = $price;
                }
            }
        }

        return $result;
    }

    /**
     * Create a DTO from an array
     */
    public static function fromArray(string $productNumber, array $data): self
    {
        $title = $data['title'] ?? '';
        $dto = new self($productNumber, $title);

        // Add prices
        foreach ($data as $key => $value) {
            if ($key === 'product_number' || $key === 'title') {
                continue; // Skip metadata
            }

            $height = $key;
            foreach ($value as $width => $widths) {
                foreach ($widths as $length => $price) {
                    $dto->setPrice($height, $width, $length, $price);
                }
            }
        }

        return $dto;
    }
}

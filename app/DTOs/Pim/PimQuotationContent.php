<?php

namespace App\DTOs\Pim;

use Illuminate\Contracts\Support\Arrayable;

class PimQuotationContent implements Arrayable
{
    public string $introductionText;

    public string $deliveryTime;

    public string $transport;

    public string $installation;

    public string $warranty;

    public string $paymentTerms;

    public string $generalInformation;

    public string $additionalText;

    public function __construct(?array $data = null)
    {
        $this->introductionText = $data['introductionText'] ?? '';
        $this->deliveryTime = $data['deliveryTime'] ?? '';
        $this->transport = $data['transport'] ?? '';
        $this->installation = $data['installation'] ?? '';
        $this->warranty = $data['warranty'] ?? '';
        $this->paymentTerms = $data['paymentTerms'] ?? '';
        $this->generalInformation = $data['generalInformation'] ?? '';
        $this->additionalText = $data['additionalText'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'introductionText' => $this->introductionText,
            'deliveryTime' => $this->deliveryTime,
            'transport' => $this->transport,
            'installation' => $this->installation,
            'warranty' => $this->warranty,
            'paymentTerms' => $this->paymentTerms,
            'generalInformation' => $this->generalInformation,
        ];
    }
}

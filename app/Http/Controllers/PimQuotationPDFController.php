<?php

namespace App\Http\Controllers;

use App\Filament\Services\PimQuotationResourceService;
use App\Models\Pim\PimQuotation;
use App\Services\Pdf\PimQuotationPdfService;

class PimQuotationPDFController extends Controller
{
    public function show(string $quotationId)
    {
        /** @var PimQuotation $quotation */
        $quotation = PimQuotationResourceService::getQuotationById($quotationId);

        return view('pdf.quotation', [
            'quotation' => $quotation,
            'imageSet' => PimQuotationPdfService::getImageSectionFromProducts($quotation),
            'includeDatasheets' => false,
        ]);
    }
}

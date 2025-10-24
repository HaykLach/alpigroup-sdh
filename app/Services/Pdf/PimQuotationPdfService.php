<?php

namespace App\Services\Pdf;

use App\Enums\Pim\PimQuatationListingItemVisibility;
use App\Filament\Services\PimQuotationResourceService;
use App\Models\Pim\PimQuotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PimQuotationPdfService
{
    public static function generateQuotationPdf(string $quotationId, array $data = []): string
    {
        /** @var PimQuotation $quotation */
        $quotation = PimQuotationResourceService::getQuotationById($quotationId);

        $datasheetsMedia = PimQuotationPdfService::evalCheckboxDataDatasheets($quotation, $data);
        $includeDatasheets = $datasheetsMedia->count() > 0;

        $imageSet = PimQuotationPdfService::evalCheckboxDataProductDetail($quotation, $data);

        // Render HTML using a Blade view
        // $html = view('pdf.quotation', ['quotation' => $quotation])->render();
        $filename = PimQuotationPdfService::getFilenameQuotationPdf($quotation, $includeDatasheets);
        $pdfPath = PimQuotationPdfService::getPathFilenameQuotationPdf($filename);

        Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'imageSet' => $imageSet,
            'includeDatasheets' => $includeDatasheets,
        ])
            ->save($pdfPath);

        if ($includeDatasheets) {
            $oMerger = PDFMerger::init();
            $oMerger->addPDF($pdfPath);

            $datasheetsMedia->each(fn ($media) => $oMerger->addPDF($media->getPath()));

            try {
                $oMerger->merge();
            } catch (CrossReferenceException $e) {
                $message = __('Ein Datasheet scheint nicht kompatibel zu sein. Das Angebot wurde ohne Datasheets erstellt.')."\r\n".$e->getMessage();
                Notification::make()
                    ->danger()
                    ->title(__('Inkompatibles Datasheet'))
                    ->body($message)
                    ->persistent()
                    ->send();
            }

            $oMerger->save($pdfPath);
        }

        return $pdfPath;
    }

    protected static function evalCheckboxDataDatasheets(PimQuotation $quotation, array $data = []): Collection
    {
        $datasheetsMedia = collect();
        if (isset($data['datasheets']) && $data['datasheets']) {
            $mediaIds = array_keys(array_filter($data['datasheets'], fn ($value) => $value === true));
            $datasheetsMedia = $quotation->products->map(function ($quotation_product) use ($mediaIds) {
                return $quotation_product->product->media->filter(function ($media) use ($mediaIds) {
                    return in_array($media->id, $mediaIds);
                });
            })->flatten();
        }

        return $datasheetsMedia;
    }

    protected static function evalCheckboxDataProductDetail(PimQuotation $quotation, array $data = []): array
    {
        $imageSet = [];
        if (isset($data['include_images']) && count($data['include_images']) > 0) {
            $products = array_keys(array_filter($data['include_images'], fn ($value) => $value === true));
            $imageSet = PimQuotationPdfService::getImageSectionFromProducts($quotation, $products);

            if (isset($data['image_preview'])) {

                foreach ($imageSet as $productId => &$set) {
                    // reset order of images
                    $dataImages = array_reverse($data['image_preview'][$productId]);

                    // get filenames
                    $filenames = collect($dataImages)->map(function ($url) {
                        return basename($url);
                    });

                    $resultingImages = [];
                    $filenames->each(function ($filename) use (&$resultingImages, $set) {
                        $media = collect($set['images'])->first(fn ($media) => $media->file_name === $filename);
                        if ($media) {
                            $resultingImages[] = $media;
                        }
                    });

                    $set['images'] = $resultingImages;
                }
            }

        }

        return $imageSet;
    }

    protected static function getFilenameQuotationPdf(PimQuotation $quotation, bool $includeDatasheets = false): string
    {
        $sanitizedTitle = Str::slug($quotation->customers->first()->summarized_title);
        $date = Carbon::parse($quotation->date)->format('d-m-Y');
        $filename = config('quotationExport.filename.prefix').
            '_'.
            __('Angebot').
            '_'.$quotation->formatted_quotation_number.
            '_'.$date.
            '_'.$sanitizedTitle;
        if ($includeDatasheets) {
            $filename .= '_D';
        }
        $filename .= '.pdf';

        return $filename;
    }

    protected static function getPathFilenameQuotationPdf(string $filename): string
    {
        return storage_path('app/public/PimQuotation/pdf/'.$filename);
    }

    public static function getImageSectionFromProducts(PimQuotation $quotation, ?array $productFilter = null): array
    {
        $imageSection = [];

        $products = $quotation->products->filter(fn ($quotation_product) => $quotation_product->visibility === PimQuatationListingItemVisibility::PUBLIC->value);
        if ($productFilter !== null) {
            $products = $products->filter(fn ($quotation_product) => in_array($quotation_product->product->id, $productFilter));
        }

        $products->each(function ($quotation_product) use (&$imageSection) {
            $images = [];

            $quotation_product->product->media
                ->filter(fn ($media) => $media->collection_name === 'preview-image')
                ->each(function (Media $media) use (&$images) {
                    $images[] = $media;
                });

            if (count($images)) {
                $imageSection[$quotation_product->product->id] = [
                    'images' => $images,
                    'product' => $quotation_product->product,
                ];
            }
        });

        return $imageSection;
    }
}

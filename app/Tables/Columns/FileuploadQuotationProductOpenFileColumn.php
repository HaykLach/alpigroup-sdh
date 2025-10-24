<?php

namespace App\Tables\Columns;

use App\Models\Pim\PimQuotationCustomProduct;
use App\Models\Pim\QuotationProduct;
use Filament\Tables\Columns\Column;
use Illuminate\Contracts\View\View;

class FileuploadQuotationProductOpenFileColumn extends Column
{
    protected string $view = 'tables.columns.fileupload-open-file-column';

    protected string $collectionName;

    public function render(): View
    {
        /** @var QuotationProduct $record */
        $record = $this->getRecord();
        if ($record->product_type === PimQuotationCustomProduct::class) {
            $media = null;
        } else {
            $media = $record->product && method_exists($record->product, 'getMedia') ? $record->product->getMedia($this->collectionName) : null;
        }

        return view($this->view, compact('media'));
    }

    public function setCollection(string $collectionName)
    {
        $this->collectionName = $collectionName;

        return $this;
    }
}

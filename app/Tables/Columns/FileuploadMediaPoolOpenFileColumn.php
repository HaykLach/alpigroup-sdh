<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\View\View;

class FileuploadMediaPoolOpenFileColumn extends Column
{
    protected string $view = 'tables.columns.fileupload-media-pool-open-file-column';

    protected string $collectionName;

    public function render(): View
    {
        $record = $this->getRecord();
        $media = $record && method_exists($record, 'getMedia') ? $record->getMedia($this->collectionName) : null;

        return view($this->view, compact('media'));
    }

    public function setCollection(string $collectionName)
    {
        $this->collectionName = $collectionName;

        return $this;
    }
}

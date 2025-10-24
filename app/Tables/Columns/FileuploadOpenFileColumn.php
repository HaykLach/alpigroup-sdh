<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\View\View;

class FileuploadOpenFileColumn extends Column
{
    protected string $view = 'tables.columns.fileupload-open-file-column';

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

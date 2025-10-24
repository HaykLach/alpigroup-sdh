<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Contracts\Property\PropertyGroupFormInterface;
use App\Enums\Pim\PimFormFileUploadType;
use App\Tables\Columns\FileuploadOpenFileColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\Filter;

class PimFileUpload extends Form implements PropertyGroupFormInterface
{
    public function getForm(): SpatieMediaLibraryFileUpload
    {
        $form = SpatieMediaLibraryFileUpload::make('fileupload.'.$this->group->id)
            ->label($this->group->description)
            ->collection($this->group->custom_fields['collection'])
            ->multiple()
            ->reorderable()
            ->downloadable()
            ->openable();

        switch ($this->group->custom_fields['form']['validation']['acceptedFileTypes']) {

            case PimFormFileUploadType::PDF->value:
                $form = $form->acceptedFileTypes(['application/pdf']);
                break;

            case PimFormFileUploadType::IMAGE->value:
                $form = $form->image()
                    ->imageEditor();
                break;
        }

        return $form;
    }

    public function getFilter(): ?Filter
    {
        return null;
    }

    public function getTableColumn(callable $fn): FileuploadOpenFileColumn|ImageColumn
    {
        switch ($this->group->custom_fields['form']['validation']['acceptedFileTypes']) {

            case PimFormFileUploadType::IMAGE->value:
                return ImageColumn::make('fileupload.'.$this->group->id)
                    ->label($this->group->description)
                    ->getStateUsing(fn ($record) => $record->getMedia($this->group->custom_fields['collection'])
                        ->sortByDesc('order_column')
                        ->map(fn ($media) => $media->getUrl())
                        ->toArray())
                    ->limit(3)
                    ->limitedRemainingText()
                    ->visible($fn)
                    ->toggleable();

            default:
                return FileuploadOpenFileColumn::make('fileupload.'.$this->group->id)
                    ->label($this->group->description)
                    ->setCollection($this->group->custom_fields['collection'])
                    ->visible($fn)
                    ->toggleable();
        }
    }

    public function isTranslatable(): bool
    {
        return true;
    }
}

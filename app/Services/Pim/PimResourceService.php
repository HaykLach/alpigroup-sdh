<?php

namespace App\Services\Pim;

use App\Models\Pim\Product\PimProduct;
use App\Services\MediaLibrary\CustomPathGenerator;
use App\Services\Session\SessionService;
use Filament\Tables\Actions\Action;

class PimResourceService
{
    public static function stripProvidedFormData(array &$data): void
    {
        // properties are handled by PimStoreOptions
        unset($data['properties']);
        // fileupload(s) are handled by SpatieMediaLibrary
        unset($data['fileupload']);
    }

    public static function getTableHeaderActionToggleEditInline($livewire): Action
    {
        $livewire->tableEditInlineEnabled = SessionService::getConfigUserTableEditInline();

        return Action::make('edit_inline')
            ->icon($livewire->tableEditInlineEnabled ? 'heroicon-o-rectangle-stack' : 'heroicon-o-pencil')
            ->label('Switch inline Edit')
            ->hiddenLabel()
            ->action(function ($livewire): void {
                $status = SessionService::toggleConfigUserTableEditInline();
                $livewire->tableEditInlineEnabled = $status;
                $livewire->resetTable();
            });
    }

    public static function getFallbackThumbnail(): string
    {
        return asset('images/fallback_icon.png');
    }

    public static function getProductThumbnails(PimProduct $record): array
    {
        if ($record->images === null || count($record->images) === 0) {
            return [];
        }

        $images = [];
        $mediaItems = $record->getMedia($record->getMediaCollectionPreviewName());

        if (count($mediaItems) === 0) {
            return array_map(fn ($url) => self::getFallbackThumbnail(), $record->images);
        }

        foreach ($mediaItems as $mediaItem) {
            $images[] = $mediaItem->getFullUrl('thumbnail');
        }

        return $images;
    }

    protected static function extractFilename($url, $suffix = '-thumbnail', $extension = '.png'): string
    {
        return basename($url, $suffix.$extension);
    }

    protected static function sortThumbnails(array $images, array $recordImages): array
    {
        // Create a mapping of filenames to URLs from array $images
        $mapping = [];
        foreach ($images as $url) {
            $filename = self::extractFilename($url);
            $mapping[$filename] = $url;
        }

        // Sort array $images based on the order of filenames in array $b
        $sortedA = [];
        foreach ($recordImages as $url) {
            $filename = CustomPathGenerator::convertFilename(pathinfo($url, PATHINFO_FILENAME));
            if (isset($mapping[$filename])) {
                $sortedA[] = $mapping[$filename];
            } else {
                $sortedA[] = self::getFallbackThumbnail();
            }
        }

        return $sortedA;
    }
}

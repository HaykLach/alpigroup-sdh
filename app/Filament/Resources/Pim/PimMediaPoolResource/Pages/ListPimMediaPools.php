<?php

namespace App\Filament\Resources\Pim\PimMediaPoolResource\Pages;

use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimMediaPoolResource;
use Filament\Actions;

class ListPimMediaPools extends PimListRecords
{
    protected static string $resource = PimMediaPoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('hinzufügen'))
                ->url(function (): string {
                    $parentId = request()->get('parent_id');
                    $url = PimMediaPoolResource::getUrl('create');
                    if ($parentId) {
                        $url .= '?parent_id='.$parentId;
                    }

                    return $url;
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return PimMediaPoolResource::getBreadcrumbs();
    }
}

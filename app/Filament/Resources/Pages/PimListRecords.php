<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Traits\CanResizeColumnsPerUser;
use App\Filament\Resources\Pages\Traits\CanToggleColumnsPerUser;
use Filament\Resources\Pages\ListRecords;

class PimListRecords extends ListRecords
{
    use CanResizeColumnsPerUser;
    use CanToggleColumnsPerUser;

    public function mount(): void
    {
        parent::mount();
        $this->mountCanToggleColumnsPerUser();
    }
}

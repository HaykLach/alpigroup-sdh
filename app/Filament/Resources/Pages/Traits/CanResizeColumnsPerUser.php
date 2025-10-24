<?php

namespace App\Filament\Resources\Pages\Traits;

use App\Models\UserPreference;
use Asmit\ResizedColumn\HasResizableColumn;

/**
 * This trait extends Filament's CanToggleColumns trait to store the toggleable columns state per user in the database.
 */
trait CanResizeColumnsPerUser
{
    use HasResizableColumn;

    protected function loadColumnWidthsFromDatabase(): void
    {
        $tabelColumnsWidthSettings = UserPreference::getPreference($this->getUserId(), $this->getKey());

        if (is_array($tabelColumnsWidthSettings)) {
            $this->columnWidths = $tabelColumnsWidthSettings;
            $this->persistColumnWidthsToSession();
        }
    }

    protected function persistColumnWidthsToDatabase(): void
    {
        UserPreference::updateOrCreate(
            [
                'user_id' => $this->getUserId(),
                'key' => $this->getKey(),
            ],
            ['value' => $this->columnWidths]
        );
    }

    private function getKey(): string
    {
        $table = md5($this->getResourceModelFullPath());

        return "tables.{$table}_resized_columns"; // key structure like vendor/filament/tables/src/Concerns/CanToggleColumns.php
    }
}

<?php

namespace App\Filament\Resources\Pages\Traits;

use App\Models\UserPreference;
use Filament\Tables\Concerns\CanToggleColumns;

/**
 * This trait extends Filament's CanToggleColumns trait to store the toggleable columns state per user in the database.
 */
trait CanToggleColumnsPerUser
{
    use CanToggleColumns {
        updatedToggledTableColumns as parentUpdatedToggledTableColumns;
        getTableColumnToggleFormStateSessionKey as parentGetTableColumnToggleFormStateSessionKey;
    }

    /**
     * Initialize the toggleable columns state from the database.
     */
    public function mountCanToggleColumnsPerUser(): void
    {
        if (auth()->check()) {
            $userId = auth()->id();
            $key = $this->getTableColumnToggleFormStateSessionKey();
            $state = UserPreference::getPreference($userId, $key);

            if ($state) {
                $this->toggledTableColumns = $state;
            }
        }
    }

    /**
     * Override the updatedToggledTableColumns method to store the state in the database.
     */
    public function updatedToggledTableColumns(): void
    {
        // Call the parent method to maintain compatibility
        $this->parentUpdatedToggledTableColumns();

        // Store the state in the database if the user is authenticated
        if (auth()->check()) {
            $userId = auth()->id();
            $key = $this->getTableColumnToggleFormStateSessionKey();
            UserPreference::setPreference($userId, $key, $this->toggledTableColumns);
        }
    }

    /**
     * Override the getTableColumnToggleFormStateSessionKey method to include the user ID.
     */
    public function getTableColumnToggleFormStateSessionKey(): string
    {
        // Use the parent method to get the base key
        return $this->parentGetTableColumnToggleFormStateSessionKey();
    }
}

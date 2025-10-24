<?php

namespace App\Filament\Pages;

use App\Enums\RoleType;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as SpatieBackups;

class Backups extends SpatieBackups
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(RoleType::SUPER_ADMIN->value) ?? false;
    }
}

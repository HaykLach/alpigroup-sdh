<?php

namespace App\Services\Session;

class SessionService
{
    public static function getConfigUserTableEditInline(): bool
    {
        if (! session()->has('user.table.edit.inline')) {
            session(['user.table.edit.inline' => false]);
        }

        return session('user.table.edit.inline');
    }

    public static function toggleConfigUserTableEditInline(): bool
    {
        $status = ! self::getConfigUserTableEditInline();
        self::setConfigUserTableEditInline($status);

        return $status;
    }

    public static function setConfigUserTableEditInline(bool $value): void
    {
        session(['user.table.edit.inline' => $value]);
    }
}

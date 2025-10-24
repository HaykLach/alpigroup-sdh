<?php

namespace App\Enums;

use App\Enums\Traits\ToArray;

enum RoleType: string
{
    use ToArray;

    case SUPER_ADMIN = 'super_admin';
    case EMPLOYEE = 'employee';
    case AGENT = 'agent';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::EMPLOYEE => 'Employee',
            self::AGENT => 'Agent',
        };
    }
}

<?php

namespace App\Enums\Pim;

use App\Enums\Traits\ToArray;

enum PimLeadStatus: string
{
    use ToArray;

    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => __('Offen'),
            self::IN_PROGRESS => __('In Arbeit'),
            self::COMPLETED => __('Abgschlossen'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
        };
    }

    public function getStatusColor(PimLeadStatus $status, float $opacity = 1.0): string
    {
        $colorMap = [
            self::OPEN->value => 'rgb(245, 158, 11, '.$opacity.')',
            self::IN_PROGRESS->value => 'rgb(59, 130, 246, '.$opacity.')',
            self::COMPLETED->value => 'rgb(34, 197, 94, '.$opacity.')',
        ];

        return $colorMap[$status->value];
    }
}

<?php

namespace App\Enums\Pim;

use App\Enums\Traits\ToArray;

enum PimQuotationStatus: string
{
    use ToArray;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('Entwurf'),
            self::SENT => __('Versendet'),
            self::ACCEPTED => __('Angenommen'),
            self::DECLINED => __('Abgelehnt'),
            self::EXPIRED => __('Abgelaufen'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::ACCEPTED => 'success',
            self::DECLINED => 'danger',
            self::EXPIRED => 'warning',
        };
    }

    public function getStatusColor(PimQuotationStatus $status, float $opacity = 1.0): string
    {
        $colorMap = [
            self::DRAFT->value => 'rgb(156, 163, 175, '.$opacity.')', // gray
            self::SENT->value => 'rgb(59, 130, 246, '.$opacity.')', // blue/info
            self::ACCEPTED->value => 'rgb(34, 197, 94, '.$opacity.')', // green/success
            self::DECLINED->value => 'rgb(239, 68, 68, '.$opacity.')', // red/danger
            self::EXPIRED->value => 'rgb(245, 158, 11, '.$opacity.')', // amber/warning
        ];

        return $colorMap[$status->value];
    }
}

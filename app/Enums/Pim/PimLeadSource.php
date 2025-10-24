<?php

namespace App\Enums\Pim;

use App\Enums\Traits\ToArray;

enum PimLeadSource: string
{
    use ToArray;

    case PHONE = 'phone';
    case WEBSITE = 'website';
    case EMAIL = 'email';
    case FAIR = 'fair';
    case SOCIAL = 'social';
    case REFERRAL = 'referral';
    case FLYER = 'flyer';
    case ADS = 'ads';
    case STORE = 'store';
    case PARTNER = 'partner';
    case ON_SITE = 'on_site';
    case REACTIVATION = 'reactivation';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::PHONE => __('Telefon'),
            self::WEBSITE => __('Website'),
            self::EMAIL => __('E-Mail'),
            self::FAIR => __('Messebesuch'),
            self::SOCIAL => __('Social Media'),
            self::REFERRAL => __('Empfehlung'),
            self::FLYER => __('Flyer / Printwerbung'),
            self::ADS => __('Online-Werbung (Google/Facebook Ads)'),
            self::STORE => __('Vor-Ort-Besuch'),
            self::PARTNER => __('Partner / Architekt / Planer'),
            self::ON_SITE => __('Mündliche Anfrage (z. B. Baustelle)'),
            self::REACTIVATION => __('Bestandskunde / Rückgewinnung'),
            self::OTHER => __('Sonstiges'),
        };
    }
}

<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public bool $translationService_enabled;

    public bool $openai_enabled;

    public bool $autoTranslateByRemoteService;

    public bool $autoAssignColorByRemoteService;

    public bool $autoDetermineProductColor;

    public static function group(): string
    {
        return 'general';
    }
}

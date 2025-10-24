<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.autoTranslateByRemoteService', false);
        $this->migrator->add('general.autoAssignColorByRemoteService', false);
        $this->migrator->add('general.autoDetermineProductColor', false);
        $this->migrator->add('general.translationService_enabled', false);
        $this->migrator->add('general.openai_enabled', false);
    }
};

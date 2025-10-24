<?php

namespace App\Filament\Pages;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Settings\GeneralSettings as Settings;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\Gate;

class GeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::SETTINGS->value;

    protected static ?int $navigationSort = 20;

    protected static string $settings = Settings::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Gate::allows('view', static::class);
    }

    public function mount(): void
    {
        if (! Gate::allows('view', $this)) {
            abort(403);
        }
        parent::mount();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('External Services')
                    ->schema([
                        Toggle::make('translationService_enabled')
                            ->label('Translation service enabled')
                            ->hint('Use external services to translate content'),

                        Toggle::make('openai_enabled')
                            ->label('OpenAI enabled')
                            ->hint('Enable OpenAi API to translate content'),
                    ])
                    ->columns(1),

                Fieldset::make('Auto Services')
                    ->schema([
                        Toggle::make('autoTranslateByRemoteService')
                            ->label('Auto translate')
                            ->hint('Automatically translate all content by remote service'),

                        Toggle::make('autoDetermineProductColor')
                            ->label('Auto determine color of product')
                            ->hint('Automatically determine the color of products'),

                        Toggle::make('autoAssignColorByRemoteService')
                            ->label('Auto assign property group color')
                            ->hint('Automatically assign hex color for color property group'),
                    ])
                    ->columns(1),
            ]);
    }
}

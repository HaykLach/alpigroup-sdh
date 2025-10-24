<?php

namespace App\Filament\Resources\Main;

use App\Models\Pim\Customer\PimAgent;
use App\Models\User;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_agent')
                    ->label(__('Vertrieb Benutzer'))
                    ->helperText(__('Aktivieren, wenn der Benutzer eine Vertriebsperson ist.'))
                    ->live()
                    ->dehydrated(false)
                    ->hidden(fn ($record) => $record !== null)
                    ->default(false),

                Forms\Components\Select::make('agent')
                    ->label(__('Vertrieb'))
                    ->visible(fn ($get) => $get('is_agent'))
                    ->required()
                    ->live()
                    ->disabled(fn ($record) => $record !== null)
                    ->options(PimResourceCustomerService::getSelectUserCreateOptions())
                    ->afterStateUpdated(function ($state, Set $set) {
                        $agent = PimAgent::find($state);
                        if ($agent) {
                            $set('email', $agent->email);
                            $set('name', $agent->full_name);
                        } else {
                            $set('email', null);
                            $set('name', null);
                        }
                    })
                    ->disabled(fn ($record) => $record !== null)
                    ->preload()
                    ->searchable()
                    ->placeholder(__('Vertrieb auswählen')),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->readOnly(fn ($get) => $get('is_agent'))
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->readOnly(fn ($get) => $get('is_agent'))
                    ->maxLength(255),

                Forms\Components\DateTimePicker::make('email_verified_at'),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->maxLength(255),

                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->required()
                    ->minItems(1)
                    ->relationship('roles', 'name')->preload(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //    ->dateTime(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'create' => UserResource\Pages\CreateUser::route('/create'),
            'edit' => UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Users & Roles';
    }
}

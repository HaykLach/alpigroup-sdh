<?php

namespace App\Filament\Resources\VendorCatalog;

use App\Enums\Pim\PimNavigationGroupTypes;
use App\Enums\VendorCatalog\VendorCatalogCompression;
use App\Enums\VendorCatalog\VendorCatalogEncoding;
use App\Enums\VendorCatalog\VendorCatalogHttpAuthenticationType;
use App\Enums\VendorCatalog\VendorCatalogImportDefinitionProtocolType;
use App\Enums\VendorCatalog\VendorCatalogImportSource;
use App\Filament\Resources\VendorCatalog;
use App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource\Pages;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class VendorCatalogImportDefinitionResource extends Resource
{
    protected static ?string $model = VendorCatalogImportDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::VENDOR_CATALOG->value;

    protected static ?string $navigationLabel = 'Import Definitions';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Source')
                    ->columnSpan('full')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Settings')
                            ->schema([
                                Forms\Components\TextInput::make('name')->required(),

                                Forms\Components\Select::make('vendor_catalog_vendor_id')
                                    ->relationship('vendor', 'name')->required(),
                                Forms\Components\Select::make('protocol')
                                    ->reactive()
                                    ->options(VendorCatalogImportDefinitionProtocolType::class),

                                Forms\Components\Select::make('source')
                                    ->reactive()
                                    ->options(VendorCatalogImportSource::class),

                                Forms\Components\Section::make('configuration')
                                    ->statePath('file')
                                    ->schema([
                                        Forms\Components\TextInput::make('delimiter')->placeholder(',')->default(';')
                                            ->hidden(fn (Get $get) => ! in_array($get('../source'), ['file', 'csv', 'txt'])),
                                        Forms\Components\TextInput::make('enclosure')->placeholder('\\')->default("'"),
                                        Forms\Components\TextInput::make('escape')->placeholder('\\\\')->default('\\'),
                                        Forms\Components\Select::make('encoding')->options(VendorCatalogEncoding::class),
                                        Forms\Components\Select::make('header_row')->options([
                                            0 => 'No header row',
                                            1 => '1. row',
                                            2 => '2. row',
                                            3 => '3. row',
                                            4 => '4. row',
                                            5 => '5. row',
                                        ])->default(null),
                                        Forms\Components\TextInput::make('start_row')->integer()->placeholder('1')->default('1'),
                                    ]),

                                Forms\Components\Section::make('compression')
                                    ->statePath('compression')
                                    ->schema([
                                        Forms\Components\Toggle::make('active'),
                                        Forms\Components\Select::make('type')->options(VendorCatalogCompression::class),
                                    ]),

                                Forms\Components\Section::make('columns for mappings')
                                    ->schema([
                                        Forms\Components\Repeater::make('columns')
                                            ->schema([
                                                Forms\Components\TextInput::make('name'),
                                                Forms\Components\TextInput::make('field'),
                                            ])
                                            ->itemLabel(fn (array $state): string => $state['name'].' -> '.$state['field'])
                                            ->collapsible()
                                            ->collapsed()
                                            ->columns(2),
                                    ]),

                                Forms\Components\Section::make('setup')
                                    ->statePath('setup')
                                    ->schema([
                                        Forms\Components\TextInput::make('depot_id'),
                                        Forms\Components\TextInput::make('article_column'),
                                        Forms\Components\TextInput::make('stock_column'),
                                    ]),

                                Forms\Components\Section::make('mail notification')
                                    ->statePath('notification')
                                    ->schema([
                                        Forms\Components\Toggle::make('mail.notification'),
                                        Forms\Components\TextInput::make('mail.address')->email()->placeholder('info@example.com'),
                                    ]),

                                Forms\Components\Section::make('local')
                                    ->statePath('configuration')
                                    ->schema([
                                        Forms\Components\TextInput::make('local.filename')->placeholder('/example/path/file.csv'),
                                    ])
                                    ->hidden(fn (Get $get) => ($get('protocol')) !== 'local'),
                                Forms\Components\Section::make('http')
                                    ->statePath('configuration')
                                    ->schema([
                                        Forms\Components\TextInput::make('http.url'),
                                        Forms\Components\Select::make('http.type')
                                            ->options(VendorCatalogHttpAuthenticationType::class)
                                            ->default(VendorCatalogHttpAuthenticationType::NONE),
                                        Forms\Components\TextInput::make('http.username'),
                                        Forms\Components\TextInput::make('http.password'),
                                    ])
                                    ->hidden(fn (Get $get) => ($get('protocol')) !== 'http'),
                                Forms\Components\Section::make('ftp')
                                    ->statePath('configuration')
                                    ->schema([
                                        Forms\Components\TextInput::make('ftp.path'),

                                        Forms\Components\TextInput::make('ftp.host'),
                                        Forms\Components\TextInput::make('ftp.username'),
                                        Forms\Components\TextInput::make('ftp.password'),

                                        Forms\Components\TextInput::make('ftp.port')->integer()->placeholder('21'),
                                        Forms\Components\TextInput::make('ftp.root'),
                                        Forms\Components\Toggle::make('ftp.passive')->inline()->default(true),
                                        Forms\Components\Toggle::make('ftp.ssl')->inline()->default(true),
                                        Forms\Components\TextInput::make('ftp.timeout')->integer()->placeholder('30'),

                                        Actions::make([
                                            Action::make('ftpTestConnection')
                                                ->icon('heroicon-o-check-circle')
                                                ->action(function (Get $get) {
                                                    self::testFtpConnection($get('ftp'));
                                                }),
                                        ]),
                                    ])
                                    ->hidden(fn (Get $get) => ! in_array($get('protocol'), ['ftp', 'sftp'])),
                            ]),
                        Forms\Components\Tabs\Tab::make('Mappings')
                            ->hiddenOn(Pages\CreateVendorCatalogImportDefinition::class)
                            ->schema([
                                Forms\Components\Repeater::make('mappings')
                                    ->schema([
                                        Forms\Components\Select::make('to')
                                            ->label('Database field')
                                            ->options([
                                                'gtin' => 'GTIN',
                                                'number' => 'Article Number',
                                                'name' => 'Name',
                                                'stock' => 'Stock',
                                            ])
                                            ->required(),
                                        Forms\Components\Select::make('from')
                                            ->label('File field')
                                            ->options(function ($record) {
                                                $columns = $record['columns'] ?? [];
                                                $options = [];
                                                foreach ($columns as $column) {
                                                    $options[$column['field']] = $column['name'];
                                                }

                                                return $options;
                                            })
                                            ->required(),
                                    ])
                                    ->itemLabel(fn (array $state): string => $state['to'].' -> '.$state['from'])
                                    ->collapsible()
                                    ->collapsed()
                                    ->columns(2),

                            ]),
                        Forms\Components\Tabs\Tab::make('Rules')
                            ->hiddenOn(Pages\CreateVendorCatalogImportDefinition::class)
                            ->schema([
                                // ...
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('vendor.name'),

                Tables\Columns\TextColumn::make('updated_at')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => VendorCatalog\VendorCatalogImportDefinitionResource\Pages\ListVendorCatalogImportDefinitions::route('/'),
            'create' => VendorCatalog\VendorCatalogImportDefinitionResource\Pages\CreateVendorCatalogImportDefinition::route('/create'),
            'view' => VendorCatalog\VendorCatalogImportDefinitionResource\Pages\ViewVendorCatalogImportDefinition::route('/{record}'),
            'edit' => VendorCatalog\VendorCatalogImportDefinitionResource\Pages\EditVendorCatalogImportDefinition::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? static::getModel()::count()
            : null;
    }

    private static function testFtpConnection(array $ftpData): Notification
    {
        try {
            $ftpData['path'] = $ftpData['path'] ?? '/';
            $ftpData['port'] = $ftpData['port'] ? (int) $ftpData['port'] : null;

            $ftp = Storage::createFtpDriver([
                'driver' => 'ftp',
                'host' => $ftpData['host'],
                'username' => $ftpData['username'],
                'password' => $ftpData['password'],
                'port' => $ftpData['port'],
                'passive' => (bool) $ftpData['passive'],
            ]);
            $ftp->get($ftpData['path']);

            return Notification::make()
                ->title('FTP connected successfully')
                ->success()
                ->send();
        } catch (\Exception $exeption) {
            return Notification::make()
                ->title($exeption->getMessage())
                ->warning()
                ->send();
        }
    }
}

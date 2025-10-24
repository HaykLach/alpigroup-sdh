<?php

namespace App\Filament\Resources\Pim;

use App\Enums\Pim\PimMediaPoolType;
use App\Enums\Pim\PimNavigationGroupTypes;
use App\Filament\Resources\Pim\PimMediaPoolResource\Pages;
use App\Models\Pim\PimMediaPool;
use App\Tables\Columns\FileuploadMediaPoolOpenFileColumn;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;

class PimMediaPoolResource extends Resource
{
    protected static ?string $model = PimMediaPool::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = PimNavigationGroupTypes::PIM->value;

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Media Pool';

    protected static ?string $modelLabel = 'Media Pool';

    public static function getNavigationBadge(): ?string
    {
        return (string) PimMediaPoolResource::countFilesInFolder();
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parent_id')
                    ->label(__('in Ordner'))
                    ->options(self::getNestedCategories())
                    ->default(request()->get('parent_id'))
                    ->nullable(),
                Forms\Components\Select::make('type')
                    ->options([
                        PimMediaPoolType::FOLDER->value => __('Ordnder'),
                        PimMediaPoolType::FILE->value => __('Datei'),
                    ])
                    ->default(PimMediaPoolType::FILE->value)
                    ->reactive()
                    ->required(),
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Textarea::make('description')->nullable(),
                SpatieMediaLibraryFileUpload::make('file')
                    ->collection(PimMediaPool::getMediaCollectionName())
                    ->visible(fn ($get) => $get('type') === PimMediaPoolType::FILE->value)
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->reorderable()
                    ->downloadable()
                    ->required()
                    ->openable(),
            ])->columns(1);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                function (): Builder {
                    $query = parent::getEloquentQuery();

                    $parentId = request()->get('parent_id');

                    if ($parentId) {
                        $query->where('parent_id', $parentId);
                    } else {
                        $query->whereNull('parent_id');
                    }

                    $query->orderBy('type');
                    $query->orderBy('name');

                    return $query;
                }
            )
            ->columns([
                IconColumn::make('type')
                    ->label(__('Typ'))
                    ->icon(fn (PimMediaPool $record): string => $record->type === PimMediaPoolType::FOLDER ? 'heroicon-o-folder' : 'heroicon-o-document'),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->url(function (PimMediaPool $record): string {
                        if ($record->type === PimMediaPoolType::FOLDER) {
                            return PimMediaPoolResource::getUrl('index', ['parent_id' => $record->id]);
                        } else {
                            return PimMediaPoolResource::getUrl('edit', ['record' => $record, 'parent_id' => $record->id]);
                        }
                    })
                    ->label('Name')->grow(),

                FileuploadMediaPoolOpenFileColumn::make('fileupload')
                    ->label(__('Datei'))
                    ->setCollection(PimMediaPool::getMediaCollectionName())
                    ->grow(),
                Tables\Columns\TextColumn::make('description')
                    ->grow(),

                Tables\Columns\TextColumn::make('filecount')
                    ->label(__('Anzahl Dateien'))
                    ->getStateUsing(function (PimMediaPool $record) {
                        if ($record->type === PimMediaPoolType::FILE) {
                            return '-';
                        }

                        return PimMEdiaPoolResource::countFilesInFolderRecursively($record->id);
                    }),

                // Tables\Columns\TextColumn::make('parent.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        PimMediaPoolType::FOLDER->value => 'Folder',
                        PimMediaPoolType::FILE->value => 'File',
                    ]),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label('Parent Folder'),
            ])
            ->actions([
                EditAction::make()
                    ->url(fn (PimMediaPool $record): string => PimMediaPoolResource::getUrl('edit', ['record' => $record, 'parent_id' => $record->id]))
                    ->icon('heroicon-o-pencil')
                    ->label(__('bearbeiten')),

                Action::make('open')
                    ->url(fn (PimMediaPool $record): string => PimMediaPoolResource::getUrl('index', ['parent_id' => $record->id]))
                    ->visible(fn (PimMediaPool $record): bool => $record->type === PimMediaPoolType::FOLDER)
                    ->icon('heroicon-o-folder-open')
                    ->label(__('öffnen')),
            ])
            ->defaultSort('name', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNestedCategories($parentId = null, $prefix = '')
    {
        $categories = PimMediaPool::where('parent_id', $parentId)->get();

        $result = [];
        foreach ($categories as $category) {
            $result[$category->id] = $prefix.$category->name;
            $result += self::getNestedCategories($category->id, $prefix.'-- ');
        }

        return $result;
    }

    public static function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            PimMediaPoolResource::getUrl() => 'Media Pool',
        ];

        $parentId = request()->get('parent_id');

        if ($parentId) {
            $parent = PimMediaPool::find($parentId);

            if ($parent) {
                $ancestors = [];
                $current = $parent;

                while ($current) {
                    $ancestors[$current->id] = $current->name;
                    $current = $current->parent;
                }

                $ancestors = array_reverse($ancestors, true);

                foreach ($ancestors as $id => $name) {
                    $breadcrumbs[PimMediaPoolResource::getUrl('index', ['parent_id' => $id])] = $name;
                }
            }
        }

        return $breadcrumbs;
    }

    public static function countFilesInFolderRecursively(?string $parentId = null): int
    {
        $query = PimMediaPool::query()
            ->where('type', '=', PimMediaPoolType::FILE);

        if ($parentId !== null) {
            $query->where('parent_id', '=', $parentId);
        }

        $fileCount = $query->count();

        $subfolders = PimMediaPool::where('parent_id', $parentId)
            ->where('type', '=', PimMediaPoolType::FOLDER)
            ->get();

        foreach ($subfolders as $subfolder) {
            $fileCount += self::countFilesInFolderRecursively($subfolder->id);
        }

        return $fileCount;
    }

    public static function countFilesInFolder(?string $parentId = null): int
    {
        $query = PimMediaPool::query()
            ->where('type', '=', PimMediaPoolType::FILE);

        if ($parentId !== null) {
            $query->where('parent_id', '=', $parentId);
        }

        return $query->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPimMediaPools::route('/'),
            'create' => Pages\CreatePimMediaPool::route('/create'),
            'edit' => Pages\EditPimMediaPool::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('media');
    }
}

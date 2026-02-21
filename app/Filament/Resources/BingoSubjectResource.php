<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BingoSubjectResource\Pages\CreateBingoSubject;
use App\Filament\Resources\BingoSubjectResource\Pages\EditBingoSubject;
use App\Filament\Resources\BingoSubjectResource\Pages\ListBingoSubjects;
use App\Filament\Resources\BingoSubjectResource\RelationManagers\BingoCellValuesRelationManager;
use App\Models\BingoSubject;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BingoSubjectResource extends Resource
{
    protected static ?string $model = BingoSubject::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Subjects';

    protected static ?string $modelLabel = 'Bingo Subject';

    protected static ?string $pluralModelLabel = 'Bingo Subjects';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set): void {
                        $set('slug', Str::slug($state ?? ''));
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bingo_cell_values_count')
                    ->label('Cell count')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->defaultSort('name')
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BingoCellValuesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('bingoCellValues');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBingoSubjects::route('/'),
            'create' => CreateBingoSubject::route('/create'),
            'edit' => EditBingoSubject::route('/{record}/edit'),
        ];
    }
}

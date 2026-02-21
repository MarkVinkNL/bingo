<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BingoCardResource\Pages\ListBingoCards;
use App\Filament\Resources\BingoCardResource\Pages\ViewBingoCard;
use App\Models\BingoCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Panel;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BingoCardResource extends Resource
{
    protected static ?string $model = BingoCard::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Bingo cards';

    protected static ?string $modelLabel = 'Bingo card';

    protected static ?string $pluralModelLabel = 'Bingo cards';

    protected static ?string $recordTitleAttribute = 'uuid';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'bingo-cards';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->url(fn (BingoCard $record): string => static::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bingoSubject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grid_size')
                    ->label('Grid')
                    ->formatStateUsing(fn (int $state): string => "{$state}×{$state}")
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('generated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('bingo_subject_id')
                    ->relationship('bingoSubject', 'name')
                    ->label('Subject'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('User'),
                Filter::make('generated_at')
                    ->label('Generated date')
                    ->form([
                        DatePicker::make('generated_from')->label('From'),
                        DatePicker::make('generated_until')->label('Until'),
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data): void {
                        $query->when(
                            $data['generated_from'] ?? null,
                            fn (Builder $q, $date): Builder => $q->whereDate('generated_at', '>=', $date)
                        )->when(
                            $data['generated_until'] ?? null,
                            fn (Builder $q, $date): Builder => $q->whereDate('generated_at', '<=', $date)
                        );
                    }),
            ])
            ->defaultPaginationPageOption(50)
            ->defaultSort('generated_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('uuid')->label('UUID'),
                TextEntry::make('bingoSubject.name')->label('Subject'),
                TextEntry::make('grid_size')->label('Grid size')->formatStateUsing(fn (int $state): string => "{$state}×{$state}"),
                TextEntry::make('user.name')->label('Owner'),
                TextEntry::make('generated_at')->dateTime()->label('Generated at'),
                Section::make('Card grid')
                    ->schema([
                        Html::make(fn (BingoCard $record): string => view('filament.components.bingo-card-grid', [
                            'card' => $record->loadMissing(['bingoCardCells.bingoCellValue']),
                        ])->render()),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBingoCards::route('/'),
            'view' => ViewBingoCard::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['bingoSubject', 'user']);
    }
}

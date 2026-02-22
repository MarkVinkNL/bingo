<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BingoCellValueSuggestionResource\Pages\EditBingoCellValueSuggestion;
use App\Filament\Resources\BingoCellValueSuggestionResource\Pages\ListBingoCellValueSuggestions;
use App\Filament\Resources\BingoCellValueSuggestionResource\Pages\ViewBingoCellValueSuggestion;
use App\Filament\Resources\BingoCellValueSuggestionResource\RelationManagers\SuggestionValuesRelationManager;
use App\Models\BingoCellValue;
use App\Models\BingoCellValueSuggestion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Panel;

class BingoCellValueSuggestionResource extends Resource
{
    protected static ?string $model = BingoCellValueSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Cell value suggestions';

    protected static ?string $modelLabel = 'Cell value suggestion';

    protected static ?string $pluralModelLabel = 'Cell value suggestions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cell-value-suggestions';
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        if ($record === null) {
            return null;
        }

        return "Suggestions for {$record->bingoSubject->name}";
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bingoSubject.name')
                    ->label('Subject')
                    ->url(fn (BingoCellValueSuggestion $record): string => BingoSubjectResource::getUrl('edit', ['record' => $record->bingoSubject]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BingoCellValueSuggestion::STATUS_PENDING => 'warning',
                        BingoCellValueSuggestion::STATUS_APPROVED => 'success',
                        BingoCellValueSuggestion::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('suggestion_values_count')
                    ->label('Values')
                    ->counts('suggestionValues')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Suggested by')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        BingoCellValueSuggestion::STATUS_PENDING => 'Pending',
                        BingoCellValueSuggestion::STATUS_APPROVED => 'Approved',
                        BingoCellValueSuggestion::STATUS_REJECTED => 'Rejected',
                    ]),
                SelectFilter::make('bingo_subject_id')
                    ->relationship('bingoSubject', 'name')
                    ->label('Subject'),
            ])
            ->defaultPaginationPageOption(25)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve cell value suggestions')
                    ->modalDescription(fn (BingoCellValueSuggestion $record): string => 'Add ' . $record->suggestion_values_count . ' new cell value(s) to "' . $record->bingoSubject->name . '"?')
                    ->visible(fn (BingoCellValueSuggestion $record): bool => $record->isPending())
                    ->action(function (BingoCellValueSuggestion $record): void {
                        $subject = $record->bingoSubject;
                        $existing = $subject->bingoCellValues()->pluck('value')->map(fn ($v) => strtolower($v))->flip();
                        $baseOrder = (int) ($subject->bingoCellValues()->max('sort_order') ?? -1);

                        foreach ($record->suggestionValues()->orderBy('sort_order')->orderBy('id')->get() as $index => $sv) {
                            if ($existing->has(strtolower($sv->value))) {
                                continue;
                            }
                            BingoCellValue::create([
                                'bingo_subject_id' => $subject->id,
                                'value' => $sv->value,
                                'sort_order' => $baseOrder + 1 + $index,
                            ]);
                            $existing->put(strtolower($sv->value), true);
                        }

                        $record->update(['status' => BingoCellValueSuggestion::STATUS_APPROVED]);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BingoCellValueSuggestion $record): bool => $record->isPending())
                    ->action(fn (BingoCellValueSuggestion $record): mixed => $record->update(['status' => BingoCellValueSuggestion::STATUS_REJECTED])),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('bingoSubject.name')
                    ->label('Subject')
                    ->url(fn (BingoCellValueSuggestion $record): string => BingoSubjectResource::getUrl('edit', ['record' => $record->bingoSubject])),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BingoCellValueSuggestion::STATUS_PENDING => 'warning',
                        BingoCellValueSuggestion::STATUS_APPROVED => 'success',
                        BingoCellValueSuggestion::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('user.name')->label('Suggested by'),
                TextEntry::make('created_at')->label('Suggested at')->dateTime(),
                Section::make('Suggested values')
                    ->schema([
                        TextEntry::make('suggestionValues.value')
                            ->label('')
                            ->bulleted()
                            ->listWithLineBreaks(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SuggestionValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBingoCellValueSuggestions::route('/'),
            'view' => ViewBingoCellValueSuggestion::route('/{record}'),
            'edit' => EditBingoCellValueSuggestion::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withCount('suggestionValues')
            ->with(['bingoSubject', 'user', 'suggestionValues']);
    }
}

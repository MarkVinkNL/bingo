<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BingoCellValueSuggestionResource;
use App\Filament\Resources\BingoSubjectResource;
use App\Models\BingoCellValue;
use App\Models\BingoCellValueSuggestion;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;

class CellValueSuggestionsTableWidget extends BaseTableWidget
{
    protected static ?string $heading = 'New cell value suggestions';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bingoSubject.name')
                    ->label('Subject')
                    ->url(fn (BingoCellValueSuggestion $record): string => BingoSubjectResource::getUrl('edit', ['record' => $record->bingoSubject]))
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
                    ])
                    ->default(BingoCellValueSuggestion::STATUS_PENDING),
                SelectFilter::make('bingo_subject_id')
                    ->relationship('bingoSubject', 'name')
                    ->label('Subject'),
            ])
            ->defaultPaginationPageOption(10)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn (BingoCellValueSuggestion $record): string => BingoCellValueSuggestionResource::getUrl('view', ['record' => $record])),
                \Filament\Actions\EditAction::make()
                    ->url(fn (BingoCellValueSuggestion $record): string => BingoCellValueSuggestionResource::getUrl('edit', ['record' => $record])),
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

    protected function getTableQuery(): ?Builder
    {
        return BingoCellValueSuggestion::query()
            ->withCount('suggestionValues')
            ->with(['bingoSubject', 'user']);
    }
}

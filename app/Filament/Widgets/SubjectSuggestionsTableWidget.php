<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BingoSubjectResource;
use App\Filament\Resources\BingoSubjectSuggestionResource;
use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use App\Models\BingoSubjectSuggestion;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SubjectSuggestionsTableWidget extends BaseTableWidget
{
    protected static ?string $heading = 'New subject suggestions';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('suggested_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BingoSubjectSuggestion::STATUS_PENDING => 'warning',
                        BingoSubjectSuggestion::STATUS_APPROVED => 'success',
                        BingoSubjectSuggestion::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('suggestion_values_count')
                    ->label('Cell values')
                    ->counts('suggestionValues')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Suggested by')
                    ->sortable(),
                TextColumn::make('approvedBingoSubject.name')
                    ->label('Approved as')
                    ->url(fn (BingoSubjectSuggestion $record): ?string => $record->approved_bingo_subject_id
                        ? BingoSubjectResource::getUrl('edit', ['record' => $record->approvedBingoSubject])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        BingoSubjectSuggestion::STATUS_PENDING => 'Pending',
                        BingoSubjectSuggestion::STATUS_APPROVED => 'Approved',
                        BingoSubjectSuggestion::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(BingoSubjectSuggestion::STATUS_PENDING),
            ])
            ->defaultPaginationPageOption(10)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn (BingoSubjectSuggestion $record): string => BingoSubjectSuggestionResource::getUrl('view', ['record' => $record])),
                \Filament\Actions\EditAction::make()
                    ->url(fn (BingoSubjectSuggestion $record): string => BingoSubjectSuggestionResource::getUrl('edit', ['record' => $record])),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve suggestion')
                    ->modalDescription(fn (BingoSubjectSuggestion $record): string => "Create a new bingo subject \"{$record->suggested_name}\" with " . $record->suggestion_values_count . ' cell values?')
                    ->visible(fn (BingoSubjectSuggestion $record): bool => $record->isPending())
                    ->action(function (BingoSubjectSuggestion $record): void {
                        $slug = Str::slug($record->suggested_name);
                        $base = $slug;
                        $i = 0;
                        while (BingoSubject::where('slug', $slug)->exists()) {
                            $slug = $base . '-' . (++$i);
                        }

                        $subject = BingoSubject::create([
                            'name' => $record->suggested_name,
                            'slug' => $slug,
                            'is_active' => true,
                        ]);

                        foreach ($record->suggestionValues()->orderBy('sort_order')->orderBy('id')->get() as $index => $sv) {
                            BingoCellValue::create([
                                'bingo_subject_id' => $subject->id,
                                'value' => $sv->value,
                                'sort_order' => $index,
                            ]);
                        }

                        $record->update([
                            'status' => BingoSubjectSuggestion::STATUS_APPROVED,
                            'approved_bingo_subject_id' => $subject->id,
                        ]);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BingoSubjectSuggestion $record): bool => $record->isPending())
                    ->action(fn (BingoSubjectSuggestion $record): mixed => $record->update(['status' => BingoSubjectSuggestion::STATUS_REJECTED])),
            ]);
    }

    protected function getTableQuery(): ?Builder
    {
        return BingoSubjectSuggestion::query()
            ->withCount('suggestionValues')
            ->with(['user', 'approvedBingoSubject']);
    }
}

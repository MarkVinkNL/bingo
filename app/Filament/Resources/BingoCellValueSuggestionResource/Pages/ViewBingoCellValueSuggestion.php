<?php

namespace App\Filament\Resources\BingoCellValueSuggestionResource\Pages;

use App\Filament\Resources\BingoCellValueSuggestionResource;
use App\Models\BingoCellValue;
use App\Models\BingoCellValueSuggestion;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBingoCellValueSuggestion extends ViewRecord
{
    protected static string $resource = BingoCellValueSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            EditAction::make(),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve cell value suggestions')
                ->modalDescription(fn (): string => 'Add ' . $record->suggestionValues()->count() . ' new cell value(s) to "' . $record->bingoSubject->name . '"?')
                ->visible(fn (): bool => $record->isPending())
                ->action(function (): void {
                    $record = $this->getRecord();
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
                    $this->redirect(BingoCellValueSuggestionResource::getUrl('view', ['record' => $record]));
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->isPending())
                ->action(function (): void {
                    $this->getRecord()->update(['status' => BingoCellValueSuggestion::STATUS_REJECTED]);
                    $this->redirect(BingoCellValueSuggestionResource::getUrl('view', ['record' => $this->getRecord()]));
                }),
        ];
    }
}

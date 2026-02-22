<?php

namespace App\Filament\Resources\BingoSubjectSuggestionResource\Pages;

use App\Filament\Resources\BingoSubjectSuggestionResource;
use App\Models\BingoSubjectSuggestion;
use App\Models\BingoSubject;
use App\Models\BingoCellValue;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditBingoSubjectSuggestion extends EditRecord
{
    protected static string $resource = BingoSubjectSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve suggestion')
                ->modalDescription(fn (): string => "Create a new bingo subject \"{$record->suggested_name}\" with " . $record->suggestionValues()->count() . ' cell values?')
                ->visible(fn (): bool => $record->isPending())
                ->action(function (): void {
                    $record = $this->getRecord();
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

                    $this->redirect(BingoSubjectSuggestionResource::getUrl('view', ['record' => $record]));
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->isPending())
                ->action(function (): void {
                    $this->getRecord()->update(['status' => BingoSubjectSuggestion::STATUS_REJECTED]);
                    $this->redirect(BingoSubjectSuggestionResource::getUrl('view', ['record' => $this->getRecord()]));
                }),
            DeleteAction::make(),
        ];
    }
}

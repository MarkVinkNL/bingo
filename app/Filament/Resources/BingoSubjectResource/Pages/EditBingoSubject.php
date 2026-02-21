<?php

namespace App\Filament\Resources\BingoSubjectResource\Pages;

use App\Filament\Resources\BingoSubjectResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditBingoSubject extends EditRecord
{
    protected static string $resource = BingoSubjectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->bingoCellValues()->count() < 1) {
            throw ValidationException::withMessages([
                'name' => ['A subject must have at least one cell value. Add cell values in the tab below.'],
            ]);
        }

        return $data;
    }
}

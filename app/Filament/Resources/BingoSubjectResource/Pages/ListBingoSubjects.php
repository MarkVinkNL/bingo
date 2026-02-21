<?php

namespace App\Filament\Resources\BingoSubjectResource\Pages;

use App\Filament\Resources\BingoSubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBingoSubjects extends ListRecords
{
    protected static string $resource = BingoSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

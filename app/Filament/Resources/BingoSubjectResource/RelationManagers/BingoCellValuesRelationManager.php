<?php

namespace App\Filament\Resources\BingoSubjectResource\RelationManagers;

use App\Models\BingoCellValue;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BingoCellValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'bingoCellValues';

    protected static ?string $title = 'Cell values';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('value')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        BingoCellValue::class,
                        'value',
                        ignoreRecord: true,
                        modifyRuleUsing: function ($rule, $component) {
                            $owner = $component->getLivewire()->getOwnerRecord();
                            return $rule->where('bingo_subject_id', $owner->getKey());
                        }
                    ),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('value')
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('bulkAdd')
                    ->label('Bulk add')
                    ->icon('heroicon-o-document-plus')
                    ->form([
                        Textarea::make('values')
                            ->label('Values (one per line)')
                            ->placeholder("Lion\nElephant\nGiraffe")
                            ->required()
                            ->rows(10),
                    ])
                    ->action(function (array $data): void {
                        $owner = $this->getOwnerRecord();
                        $lines = array_values(array_filter(array_map('trim', explode("\n", $data['values'] ?? ''))));
                        $existing = $owner->bingoCellValues()->pluck('value')->map(fn ($v) => strtolower($v))->flip();
                        foreach ($lines as $line) {
                            if ($line === '' || $existing->has(strtolower($line))) {
                                continue;
                            }
                            $owner->bingoCellValues()->create([
                                'value' => $line,
                            ]);
                            $existing->put(strtolower($line), true);
                        }
                    }),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn (BingoCellValue $record): bool => $record->bingoSubject->bingoCellValues()->count() > 1),
            ]);
    }
}

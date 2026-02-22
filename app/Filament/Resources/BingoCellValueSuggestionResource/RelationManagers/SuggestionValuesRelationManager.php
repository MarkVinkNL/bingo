<?php

namespace App\Filament\Resources\BingoCellValueSuggestionResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuggestionValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'suggestionValues';

    protected static ?string $title = 'Suggested values';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('value')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->defaultPaginationPageOption(50)
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('value')
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }
}

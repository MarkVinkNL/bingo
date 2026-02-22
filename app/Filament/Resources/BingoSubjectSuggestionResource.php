<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BingoSubjectSuggestionResource\Pages\EditBingoSubjectSuggestion;
use App\Filament\Resources\BingoSubjectSuggestionResource\Pages\ListBingoSubjectSuggestions;
use App\Filament\Resources\BingoSubjectSuggestionResource\Pages\ViewBingoSubjectSuggestion;
use App\Filament\Resources\BingoSubjectSuggestionResource\RelationManagers\SuggestionValuesRelationManager;
use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use App\Models\BingoSubjectSuggestion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Panel;
use Illuminate\Support\Str;

class BingoSubjectSuggestionResource extends Resource
{
    protected static ?string $model = BingoSubjectSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'Subject suggestions';

    protected static ?string $modelLabel = 'Subject suggestion';

    protected static ?string $pluralModelLabel = 'Subject suggestions';

    protected static ?string $recordTitleAttribute = 'suggested_name';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'subject-suggestions';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('suggested_name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set): void {
                        $set('suggested_slug', Str::slug($state ?? ''));
                    }),
                TextInput::make('suggested_slug')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('suggested_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('suggested_slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('approvedBingoSubject.name')
                    ->label('Approved as')
                    ->url(fn (BingoSubjectSuggestion $record): ?string => $record->approved_bingo_subject_id
                        ? BingoSubjectResource::getUrl('edit', ['record' => $record->approvedBingoSubject])
                        : null)
                    ->placeholder('—')
                    ->sortable(),
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
                    ]),
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('suggested_name')->label('Subject name'),
                TextEntry::make('suggested_slug')->label('Slug'),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BingoSubjectSuggestion::STATUS_PENDING => 'warning',
                        BingoSubjectSuggestion::STATUS_APPROVED => 'success',
                        BingoSubjectSuggestion::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('user.name')->label('Suggested by'),
                TextEntry::make('approvedBingoSubject.name')
                    ->label('Approved as')
                    ->url(fn (BingoSubjectSuggestion $record): ?string => $record->approved_bingo_subject_id
                        ? BingoSubjectResource::getUrl('edit', ['record' => $record->approvedBingoSubject])
                        : null)
                    ->placeholder('—'),
                TextEntry::make('created_at')->label('Suggested at')->dateTime(),
                Section::make('Cell values')
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
            'index' => ListBingoSubjectSuggestions::route('/'),
            'view' => ViewBingoSubjectSuggestion::route('/{record}'),
            'edit' => EditBingoSubjectSuggestion::route('/{record}/edit'),
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
            ->with(['user', 'approvedBingoSubject', 'suggestionValues']);
    }
}

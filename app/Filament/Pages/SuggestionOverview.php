<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CellValueSuggestionsTableWidget;
use App\Filament\Widgets\SubjectSuggestionsTableWidget;
use Filament\Pages\Page;

class SuggestionOverview extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'Suggestions';

    protected static ?string $title = 'Suggestion overview';

    protected static ?string $slug = 'suggestions';

    protected static ?int $navigationSort = 5;

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    protected function getFooterWidgets(): array
    {
        return [
            SubjectSuggestionsTableWidget::class,
            CellValueSuggestionsTableWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Panel;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'users';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->enum(UserRole::class)
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $case) => [$case->value => $case->name])->all())
                    ->required()
                    ->visible(fn (): bool => Auth::user()?->isSuperadmin() ?? false),
                Toggle::make('blocked')
                    ->label('Blocked')
                    ->helperText('Blocked users cannot sign in or access the app.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->url(fn (User $record): string => static::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof UserRole ? $state->name : (string) $state)
                    ->sortable(),
                IconColumn::make('blocked')
                    ->boolean()
                    ->trueIcon(\Filament\Support\Icons\Heroicon::OutlinedXMark)
                    ->trueColor('danger')
                    ->falseIcon(\Filament\Support\Icons\Heroicon::OutlinedCheck)
                    ->falseColor('success')
                    ->label('Blocked')
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(50)
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof UserRole ? $state->name : (string) $state),
                TextEntry::make('blocked')
                    ->label('Blocked')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                TextEntry::make('oauth_provider')
                    ->label('Login provider')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Local'),
                TextEntry::make('email_verified_at')
                    ->label('Email verified')
                    ->dateTime()
                    ->placeholder('Not verified'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

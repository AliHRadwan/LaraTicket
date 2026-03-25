<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->minLength(8)
                        ->maxLength(255),
                ])
                ->columns(2),
            Forms\Components\Section::make('Permissions')
                ->schema([
                    Forms\Components\Toggle::make('is_admin')
                        ->label('Administrator')
                        ->helperText('Grants full access to the admin dashboard and all management features.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->email_verified_at !== null)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Admin Status'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleAdmin')
                    ->label(fn (User $record) => ($record->is_admin ?? false) ? 'Remove Admin' : 'Make Admin')
                    ->icon(fn (User $record) => ($record->is_admin ?? false) ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn (User $record) => ($record->is_admin ?? false) ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->hidden(fn (User $record) => $record->id === auth()->id())
                    ->action(function (User $record) {
                        $record->update(['is_admin' => ! ($record->is_admin ?? false)]);

                        \Filament\Notifications\Notification::make()
                            ->title($record->is_admin ? 'Admin access granted' : 'Admin access revoked')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers\OrdersRelationManager;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Event Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('start_datetime', '>', now())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Event Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, Forms\Set $set, ?Event $record) {
                            if (! $record) {
                                $set('slug', Str::slug($state) . '-' . Str::random(6));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Event $record) => $record !== null)
                        ->dehydrated(),
                    Forms\Components\RichEditor::make('description')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('location')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\FileUpload::make('image_url')
                        ->label('Event Image')
                        ->image()
                        ->disk('public')
                        ->directory('event-images')
                        ->maxSize(5120)
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('1080')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Tickets & Pricing')
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('EGP')
                        ->minValue(0)
                        ->step(0.01),
                    Forms\Components\TextInput::make('total_tickets')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->disabled(fn (?Event $record) => $record !== null)
                        ->dehydrated(),
                    Forms\Components\Placeholder::make('available_tickets_display')
                        ->label('Available Tickets')
                        ->content(fn (?Event $record) => $record?->available_tickets ?? '—')
                        ->visibleOn('edit'),
                    Forms\Components\Placeholder::make('tickets_sold')
                        ->label('Tickets Sold')
                        ->content(fn (?Event $record) => $record ? ($record->total_tickets - $record->available_tickets) : '—')
                        ->visibleOn('edit'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Schedule')
                ->schema([
                    Forms\Components\DateTimePicker::make('start_datetime')
                        ->required()
                        ->native(false)
                        ->displayFormat('M d, Y H:i')
                        ->closeOnDateSelection(),
                    Forms\Components\DateTimePicker::make('end_datetime')
                        ->native(false)
                        ->displayFormat('M d, Y H:i')
                        ->closeOnDateSelection()
                        ->after('start_datetime'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=E&background=6366f1&color=fff'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Event $record) => $record->title),
                Tables\Columns\TextColumn::make('price')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tickets')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('available_tickets')
                    ->label('Available')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn (Event $record) => $record->available_tickets === 0 ? 'danger' : ($record->available_tickets < 10 ? 'warning' : 'success'))
                    ->badge(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Starts')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_datetime', 'desc')
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Only')
                    ->query(fn ($query) => $query->where('start_datetime', '>', now()))
                    ->toggle(),
                Tables\Filters\Filter::make('sold_out')
                    ->label('Sold Out')
                    ->query(fn ($query) => $query->where('available_tickets', 0))
                    ->toggle(),
                Tables\Filters\Filter::make('has_availability')
                    ->label('Has Tickets')
                    ->query(fn ($query) => $query->where('available_tickets', '>', 0))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}

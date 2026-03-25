<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable()
                    ->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record->id])),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Event')
                    ->limit(30),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatusEnum $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

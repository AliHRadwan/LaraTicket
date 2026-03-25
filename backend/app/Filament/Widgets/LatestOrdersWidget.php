<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['user:id,name,email', 'event:id,title', 'payments:id,order_id,status'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record->id])),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Event')
                    ->limit(30),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Qty')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatusEnum $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}

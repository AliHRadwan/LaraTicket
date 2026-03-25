<?php

namespace App\Filament\Resources;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\NotificationDTO;
use App\Enums\NotificationType;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Mail\OrderCancelledEmail;
use App\Mail\OrderRefundedEmail;
use App\Models\Order;
use App\Notifications\NotificationSystem;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Orders & Payments';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', OrderStatusEnum::PENDING)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Order Details')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('Order #'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (OrderStatusEnum $state) => $state->color()),
                    Infolists\Components\TextEntry::make('tickets_count')->label('Tickets'),
                    Infolists\Components\TextEntry::make('total_price')->money('EGP'),
                    Infolists\Components\TextEntry::make('created_at')->dateTime('M d, Y H:i'),
                ])
                ->columns(3),
            Infolists\Components\Section::make('Customer')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('Name'),
                    Infolists\Components\TextEntry::make('user.email')->label('Email'),
                ])
                ->columns(2),
            Infolists\Components\Section::make('Event')
                ->schema([
                    Infolists\Components\TextEntry::make('event.title')->label('Event'),
                    Infolists\Components\TextEntry::make('event.start_datetime')
                        ->label('Event Date')
                        ->dateTime('M d, Y H:i'),
                    Infolists\Components\TextEntry::make('event.location')->label('Location'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Event')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Order $record) => $record->event->title),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatusEnum $state) => $state->color()),
                Tables\Columns\TextColumn::make('payments_count')
                    ->label('Payments')
                    ->counts('payments')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(OrderStatusEnum::class),
                Tables\Filters\SelectFilter::make('event')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Order')
                    ->modalDescription('This will cancel the order and return tickets to availability. Are you sure?')
                    ->visible(fn (Order $record) => $record->status === OrderStatusEnum::PENDING)
                    ->action(function (Order $record) {
                        DB::transaction(function () use ($record) {
                            $record->update(['status' => OrderStatusEnum::CANCELLED->value]);
                            $record->event->increment('available_tickets', $record->tickets_count);
                        });

                        Log::info('Order cancelled by admin', [
                            'order_id' => $record->id,
                            'admin_id' => auth()->id(),
                        ]);

                        $record->loadMissing(['user', 'event']);
                        $record->user->notify(new NotificationSystem(new NotificationDTO(
                            type: NotificationType::ORDER_CANCELLED,
                            title: "Order #{$record->id} Cancelled",
                            body: "Your order for {$record->event->title} has been cancelled by the administrator.",
                            mailable: new OrderCancelledEmail($record),
                            meta: ['order_id' => $record->id, 'reason' => 'admin_cancelled'],
                        )));
                    })
                    ->after(fn () => \Filament\Notifications\Notification::make()->title('Order cancelled')->success()->send()),
                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Refund Order')
                    ->modalDescription('This will initiate a full refund via Stripe. Are you sure?')
                    ->visible(fn (Order $record) => $record->status === OrderStatusEnum::COMPLETED)
                    ->action(function (Order $record) {
                        $payment = $record->payments()
                            ->where('status', PaymentStatusEnum::PAID)
                            ->latest()
                            ->first();

                        if (! $payment) {
                            \Filament\Notifications\Notification::make()
                                ->title('No paid payment found for this order')
                                ->danger()
                                ->send();
                            return;
                        }

                        $gateway = app(PaymentGatewayInterface::class);
                        $result = $gateway->refundPayment($payment->provider_transaction_id);

                        if (isset($result->id)) {
                            DB::transaction(function () use ($record, $payment) {
                                $record->update(['status' => OrderStatusEnum::REFUNDED->value]);
                                $record->event->increment('available_tickets', $record->tickets_count);
                                $record->payments()->create([
                                    'amount' => $payment->amount,
                                    'provider' => 'stripe',
                                    'provider_transaction_id' => $payment->provider_transaction_id . '_refund',
                                    'payment_method' => $payment->payment_method,
                                    'status' => PaymentStatusEnum::REFUNDED->value,
                                    'notes' => 'Admin-initiated refund',
                                ]);
                            });

                            Log::info('Order refunded by admin', [
                                'order_id' => $record->id,
                                'admin_id' => auth()->id(),
                                'amount' => $payment->amount,
                            ]);

                            $record->loadMissing(['user', 'event']);
                            $record->user->notify(new NotificationSystem(new NotificationDTO(
                                type: NotificationType::ORDER_REFUNDED,
                                title: "Order #{$record->id} Refunded",
                                body: "Your order for {$record->event->title} has been refunded.",
                                mailable: new OrderRefundedEmail($record),
                                meta: ['order_id' => $record->id, 'refund_amount' => $payment->amount],
                            )));

                            \Filament\Notifications\Notification::make()->title('Refund initiated successfully')->success()->send();
                        } else {
                            Log::error('Stripe refund failed', [
                                'order_id' => $record->id,
                                'error' => $result->message ?? 'Unknown error',
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Refund failed: ' . ($result->message ?? 'Unknown error'))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

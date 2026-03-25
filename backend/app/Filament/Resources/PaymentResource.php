<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatusEnum;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Orders & Payments';

    protected static ?int $navigationSort = 2;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Payment Details')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('Payment #'),
                    Infolists\Components\TextEntry::make('amount')->money('EGP'),
                    Infolists\Components\TextEntry::make('provider')->badge(),
                    Infolists\Components\TextEntry::make('payment_method')->label('Method'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (PaymentStatusEnum $state) => $state->color()),
                    Infolists\Components\TextEntry::make('created_at')->dateTime('M d, Y H:i'),
                ])
                ->columns(3),
            Infolists\Components\Section::make('Transaction')
                ->schema([
                    Infolists\Components\TextEntry::make('provider_transaction_id')->label('Transaction ID'),
                    Infolists\Components\TextEntry::make('idempotency_key'),
                    Infolists\Components\TextEntry::make('notes'),
                ])
                ->columns(3),
            Infolists\Components\Section::make('Linked Order')
                ->schema([
                    Infolists\Components\TextEntry::make('order.id')->label('Order #'),
                    Infolists\Components\TextEntry::make('order.user.name')->label('Customer'),
                    Infolists\Components\TextEntry::make('order.user.email')->label('Email'),
                    Infolists\Components\TextEntry::make('order.event.title')->label('Event'),
                    Infolists\Components\TextEntry::make('order.total_price')->label('Order Total')->money('EGP'),
                    Infolists\Components\TextEntry::make('order.status')
                        ->label('Order Status')
                        ->badge()
                        ->color(fn ($state) => $state->color()),
                ])
                ->columns(3),
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
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order #')
                    ->sortable()
                    ->url(fn (Payment $record) => OrderResource::getUrl('view', ['record' => $record->order_id])),
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.event.title')
                    ->label('Event')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('amount')
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PaymentStatusEnum $state) => $state->color()),
                Tables\Columns\TextColumn::make('provider_transaction_id')
                    ->label('Transaction ID')
                    ->limit(18)
                    ->tooltip(fn ($record) => $record->provider_transaction_id)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PaymentStatusEnum::class),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'stripe' => 'Stripe',
                    ]),
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
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

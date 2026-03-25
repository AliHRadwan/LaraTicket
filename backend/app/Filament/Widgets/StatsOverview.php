<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRevenue = Payment::where('status', PaymentStatusEnum::PAID)->sum('amount');
        $todayRevenue = Payment::where('status', PaymentStatusEnum::PAID)
            ->whereDate('created_at', today())
            ->sum('amount');

        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        $pendingOrders = Order::where('status', OrderStatusEnum::PENDING)->count();

        $activeEvents = Event::where('start_datetime', '>', now())->count();
        $totalUsers = User::count();

        return [
            Stat::make('Total Revenue', 'EGP ' . number_format($totalRevenue, 2))
                ->description('EGP ' . number_format($todayRevenue, 2) . ' today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getRevenueChartData()),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description($todayOrders . ' today · ' . $pendingOrders . ' pending')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning')
                ->chart($this->getOrdersChartData()),

            Stat::make('Active Events', number_format($activeEvents))
                ->description(Event::count() . ' total events')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Registered Users', number_format($totalUsers))
                ->description(User::whereDate('created_at', today())->count() . ' joined today')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }

    private function getRevenueChartData(): array
    {
        return Payment::where('status', PaymentStatusEnum::PAID)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }

    private function getOrdersChartData(): array
    {
        return Order::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }
}

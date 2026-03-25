<?php

namespace App\Enums;

enum NotificationType: string
{
    case ORDER_PLACED = 'order_placed';
    case ORDER_COMPLETED = 'order_completed';
    case ORDER_CANCELLED = 'order_cancelled';
    case ORDER_REFUNDED = 'order_refunded';
    case VERIFY_EMAIL = 'verify_email';

    public function title(): string
    {
        return match ($this) {
            self::ORDER_PLACED => 'Order Placed',
            self::ORDER_COMPLETED => 'Order Completed',
            self::ORDER_CANCELLED => 'Order Cancelled',
            self::ORDER_REFUNDED => 'Order Refunded',
            self::VERIFY_EMAIL => 'Verify Email',
        };
    }
}

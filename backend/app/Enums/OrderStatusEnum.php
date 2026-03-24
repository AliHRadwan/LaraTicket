<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
            self::FAILED => 'danger',
            self::REFUNDED => 'danger',
            self::COMPLETED => 'success',
        };
    }
}
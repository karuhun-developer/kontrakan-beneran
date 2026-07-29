<?php

namespace App\Enums;

enum LaundryStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Diterima',
            self::Processing => 'Diproses',
            self::Ready => 'Siap Diambil',
            self::Delivered => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Received => 'blue',
            self::Processing => 'yellow',
            self::Ready => 'blue',
            self::Delivered => 'green',
            self::Cancelled => 'gray',
        };
    }

    /**
     * Whether the order is still being worked on (not delivered or cancelled).
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /**
     * The next forward status in the fulfilment flow, or null when the order
     * has reached a terminal state.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Received => self::Processing,
            self::Processing => self::Ready,
            self::Ready => self::Delivered,
            self::Delivered, self::Cancelled => null,
        };
    }
}

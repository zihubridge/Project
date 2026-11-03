<?php

namespace App\Enums;

enum SwapStatus: string
{
    case DEPOSIT_PENDING = 'DEPOSIT_PENDING';
    case DEPOSIT_SUBMITTED = 'DEPOSIT_SUBMITTED';
    case DEPOSIT_DETECTED = 'DEPOSIT_DETECTED';
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case REFUNDED = 'REFUNDED';
    case FAILED = 'FAILED';
    case EXPIRED = 'EXPIRED';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::REFUNDED,
            self::EXPIRED,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT_PENDING => 'Awaiting Deposit',
            self::DEPOSIT_SUBMITTED => 'Deposit Transaction Submitted',
            self::DEPOSIT_DETECTED => 'Deposit Detected on Origin Chain',
            self::PROCESSING => 'Executing Cross-Chain Swap',
            self::COMPLETED => 'Swap Completed',
            self::REFUNDED => 'Refunded to Origin Address',
            self::FAILED => 'Swap Failed',
            self::EXPIRED => 'Quote Window Expired',
        };
    }

    public function activeStepNumber(): int
    {
        return match ($this) {
            self::DEPOSIT_PENDING => 1,
            self::DEPOSIT_SUBMITTED, self::DEPOSIT_DETECTED => 2,
            self::PROCESSING => 3,
            self::COMPLETED, self::REFUNDED, self::FAILED, self::EXPIRED => 4,
        };
    }
}


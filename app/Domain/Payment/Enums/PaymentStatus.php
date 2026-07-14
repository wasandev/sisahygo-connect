<?php

namespace App\Domain\Payment\Enums;

enum PaymentStatus: int
{
    case Outstanding = 0;
    case Paid = 1;

    public function translationKey(): string
    {
        return match ($this) {
            self::Outstanding => 'payment.status.outstanding',
            self::Paid => 'payment.status.paid',
        };
    }
}
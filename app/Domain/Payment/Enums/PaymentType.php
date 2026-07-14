<?php

namespace App\Domain\Payment\Enums;

enum PaymentType: string
{
    case SenderCashAtOrigin = 'H';
    case SenderTransferAtOrigin = 'T';
    case ReceiverCollectAfterDelivery = 'E';
    case OriginBilling = 'F';
    case DestinationBilling = 'L';

    public function translationKey(): string
    {
        return "payment.type.{$this->value}";
    }

    public function isSenderVisible(): bool
    {
        return in_array($this, [self::SenderCashAtOrigin, self::SenderTransferAtOrigin, self::OriginBilling], true);
    }

    public function isReceiverVisible(): bool
    {
        return in_array($this, [self::ReceiverCollectAfterDelivery, self::DestinationBilling], true);
    }

    public function isImmediate(): bool
    {
        return in_array($this, [self::SenderCashAtOrigin, self::SenderTransferAtOrigin, self::ReceiverCollectAfterDelivery], true);
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::OriginBilling, self::DestinationBilling], true);
    }

    /**
     * @return array<int, string>
     */
    public static function senderVisibleValues(): array
    {
        return self::valuesWhere(fn (self $type) => $type->isSenderVisible());
    }

    /**
     * @return array<int, string>
     */
    public static function receiverVisibleValues(): array
    {
        return self::valuesWhere(fn (self $type) => $type->isReceiverVisible());
    }

    /**
     * @return array<int, string>
     */
    public static function immediateValues(): array
    {
        return self::valuesWhere(fn (self $type) => $type->isImmediate());
    }

    /**
     * @return array<int, string>
     */
    public static function creditValues(): array
    {
        return self::valuesWhere(fn (self $type) => $type->isCredit());
    }

    /**
     * @param callable(self): bool $predicate
     * @return array<int, string>
     */
    private static function valuesWhere(callable $predicate): array
    {
        return array_values(array_map(
            fn (self $type) => $type->value,
            array_filter(self::cases(), $predicate),
        ));
    }
}

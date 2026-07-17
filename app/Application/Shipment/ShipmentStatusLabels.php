<?php

namespace App\Application\Shipment;

final class ShipmentStatusLabels
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return __('shipments.statuses');
    }

    public static function label(?string $status): string
    {
        if (! $status) {
            return __('shipments.statuses.unknown');
        }

        return self::options()[$status] ?? $status;
    }

    public static function variant(?string $status): string
    {
        return match ($status) {
            'completed', 'delivered' => 'success',
            'problem', 'cancel' => 'danger',
            'checking', 'new', 'created' => 'warning',
            'confirmed', 'loaded', 'in transit', 'arrival', 'branch warehouse', 'delivery', 'picked_up' => 'blue',
            default => 'neutral',
        };
    }
}

<?php

namespace App\Application\Payment;

use Carbon\CarbonImmutable;

class PaymentPresenter
{
    public const SUPPORTED_TYPES = ['F', 'L', 'E'];

    public const SUPPORTED_STATUSES = ['outstanding', 'paid'];

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            'F' => __('payment.type.F'),
            'L' => __('payment.type.L'),
            'E' => __('payment.type.E'),
            default => __('payment.fallback.unknown'),
        };
    }

    public static function payerRoleLabel(?string $role, ?string $type = null): string
    {
        $role = $role ?: match ($type) {
            'F' => 'sender',
            'L', 'E' => 'receiver',
            default => null,
        };

        return match ($role) {
            'sender' => __('payment.payer_role.sender'),
            'receiver' => __('payment.payer_role.receiver'),
            default => __('payment.fallback.unknown'),
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => __('payment.status.paid'),
            'outstanding', 'unpaid' => __('payment.status.outstanding'),
            default => __('payment.fallback.unknown'),
        };
    }

    public static function statusVariant(?string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'outstanding', 'unpaid' => 'warning',
            default => 'neutral',
        };
    }

    public static function money(?string $value): string
    {
        if ($value === null || $value === '') {
            return __('payment.fallback.empty');
        }

        $value = trim($value);

        if (! preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            return __('payment.fallback.empty');
        }

        $negative = str_starts_with($value, '-');
        $normalized = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $whole = ltrim(preg_replace('/\D/', '', $whole) ?: '0', '0') ?: '0';
        $fraction = substr(str_pad(preg_replace('/\D/', '', $fraction) ?: '', 2, '0'), 0, 2);
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);

        return ($negative ? '-' : '').$grouped.'.'.$fraction;
    }

    public static function date(?CarbonImmutable $value): string
    {
        return $value?->format('Y-m-d') ?? __('payment.fallback.empty');
    }
}

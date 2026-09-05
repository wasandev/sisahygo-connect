<?php

namespace App\Application\Support;

use Carbon\CarbonImmutable;

class CoreDateTimeFormatter
{
    public function display(mixed $value, string $format = 'd/m/Y H:i'): ?string
    {
        $date = $this->parse($value);

        return $date?->format($format);
    }

    public function date(mixed $value): ?string
    {
        return $this->display($value, 'd/m/Y');
    }

    public function time(mixed $value): ?string
    {
        return $this->display($value, 'H:i');
    }

    public function parse(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->setTimezone(config('app.timezone', 'Asia/Bangkok'));
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $timezone = config('app.timezone', 'Asia/Bangkok');

        if ($this->hasTimezoneOffset($value)) {
            return CarbonImmutable::parse($value)->setTimezone($timezone);
        }

        return CarbonImmutable::parse($value, $timezone);
    }

    private function hasTimezoneOffset(string $value): bool
    {
        return (bool) preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', $value);
    }
}

<?php

namespace App\Application\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class ReportCriteria
{
    public function __construct(public array $filters) {}

    public static function from(array $input, string $report, bool $export = false): self
    {
        $today = now(config('app.timezone'));
        $input = array_filter($input, fn ($value) => $value !== null && $value !== '');
        $input = array_merge([
            'date_from' => $today->copy()->startOfMonth()->toDateString(),
            'date_to' => $today->toDateString(),
            'relationship' => 'all',
            'page' => 1,
            'per_page' => 25,
        ], $input);

        $rules = [
            'date_from' => ['required', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'relationship' => ['nullable', Rule::in(['all', 'sender', 'receiver'])],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50])],
        ];
        if ($report === 'order-checkings') {
            $rules += ['type' => ['nullable', Rule::in(['all', 'single', 'bulk'])], 'client_reference' => ['nullable', 'string', 'max:100'], 'batch_reference' => ['nullable', 'string', 'max:100'], 'pricing_status' => ['nullable', Rule::in(['resolved', 'unresolved'])]];
        }
        if ($report === 'payments') {
            $rules += ['payment_status' => ['nullable', Rule::in(['paid', 'unpaid'])], 'payment_type' => ['nullable', Rule::in(['H', 'T', 'F', 'E', 'L'])], 'client_reference' => ['nullable', 'string', 'max:100']];
        }

        $data = Validator::make($input, $rules)->validate();
        $maxDays = $export ? 366 : 93;
        if (Carbon::parse($data['date_from'])->diffInDays(Carbon::parse($data['date_to'])) > $maxDays) {
            Validator::make([], [])->after(fn ($v) => $v->errors()->add('date_to', __('reports.validation.date_range', ['days' => $maxDays])))->validate();
        }
        if (($data['type'] ?? null) === 'all') unset($data['type']);

        return new self(array_filter($data + ['export' => $export], fn ($value) => $value !== null && $value !== ''));
    }
}

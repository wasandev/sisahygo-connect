<?php

namespace App\Application\Reports;

use App\Application\Support\CoreDateTimeFormatter;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\Endpoints\ReportsEndpoint;
use App\Models\User;

class ReportQueryService
{
    private const DATE_TIME_COLUMNS = [
        'latest_status_time',
        'last_update',
        'submitted_at',
        'payment_date',
    ];

    public function __construct(
        private readonly SisahygoIntegrationContextBuilder $contextBuilder,
        private readonly ReportsEndpoint $reports,
        private readonly CoreDateTimeFormatter $dateTime,
    ) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function fetch(User $user, ClientAccount $account, string $report, array $filters, bool $export = false): array
    {
        $criteria = ReportCriteria::from($filters, $report, $export);
        $context = $this->contextBuilder->build($user, $account, $export ? ClientCapability::ReportExport : ClientCapability::ReportView);
        return $this->formatResponse($this->reports->report($context, $report, $criteria->filters));
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    private function formatResponse(array $response): array
    {
        foreach (($response['data']['rows'] ?? []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $response['data']['rows'][$index] = $this->formatRow($row);
        }

        return $response;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatRow(array $row): array
    {
        foreach (self::DATE_TIME_COLUMNS as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $this->formatDateTimeValue($row[$column]);
            }
        }

        if (is_array($row['timeline'] ?? null)) {
            $row['timeline'] = array_map(fn (array $event): array => $this->formatTimelineEvent($event), $row['timeline']);
        }

        return $row;
    }

    private function formatDateTimeValue(mixed $value): mixed
    {
        if (is_string($value) && ! preg_match('/[T ]\d{2}:\d{2}/', $value)) {
            return $value;
        }

        return $this->dateTime->display($value) ?? $value;
    }

    /** @param array<string, mixed> $event @return array<string, mixed> */
    private function formatTimelineEvent(array $event): array
    {
        $timestamp = $event['occurred_at'] ?? $event['changed_at'] ?? $event['created_at'] ?? null;

        if (! $timestamp && is_string($event['date'] ?? null) && preg_match('/[T ]\d{2}:\d{2}/', $event['date'])) {
            $timestamp = $event['date'];
        }

        if (! $timestamp && is_string($event['date'] ?? null) && is_string($event['time'] ?? null)) {
            $timestamp = trim($event['date'].' '.$event['time']);
        }

        if ($timestamp) {
            $event['date'] = $this->dateTime->date($timestamp) ?? ($event['date'] ?? null);
            $event['time'] = $this->dateTime->time($timestamp) ?? ($event['time'] ?? null);
        }

        return $event;
    }
}

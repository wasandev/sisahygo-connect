<?php

namespace App\Application\Reports;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\Endpoints\ReportsEndpoint;
use App\Models\User;

class ReportQueryService
{
    public function __construct(
        private readonly SisahygoIntegrationContextBuilder $contextBuilder,
        private readonly ReportsEndpoint $reports,
    ) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function fetch(User $user, ClientAccount $account, string $report, array $filters, bool $export = false): array
    {
        $criteria = ReportCriteria::from($filters, $report, $export);
        $context = $this->contextBuilder->build($user, $account, $export ? ClientCapability::ReportExport : ClientCapability::ReportView);
        return $this->reports->report($context, $report, $criteria->filters);
    }
}

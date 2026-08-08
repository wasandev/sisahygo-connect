<?php

namespace Tests\Feature\Reports;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Application\Reports\ReportQueryService;
use App\Livewire\Reports\ReportCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ReportPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_view_controls_center_and_report_pages(): void
    {
        [$user, $account] = $this->account(reportView: true, reportExport: false);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReport('shipments');

        $this->get(route('reports'))->assertOk()->assertSee('รายงานสรุปการจัดส่งสินค้า')->assertSee('รายงานรายการที่สร้างผ่าน Sisahygo Connect')->assertSee('รายงานค่าขนส่งและสถานะการชำระเงิน')->assertDontSee('Timeline');
        $this->get(route('reports.shipments'))->assertOk()->assertSee('Shipment No. 1')->assertDontSee(__('reports.actions.export'));

        [$blockedUser, $blocked] = $this->account(reportView: false, reportExport: false);
        $this->actingAs($blockedUser)->withSession([CurrentClientAccountResolver::SESSION_KEY => $blocked->id]);
        $this->get(route('reports'))->assertForbidden();
        Livewire::test(ReportCenter::class)->assertForbidden();
    }

    public function test_report_export_capability_controls_button_and_direct_download(): void
    {
        [$viewer, $viewOnly] = $this->account(reportView: true, reportExport: false);
        $this->actingAs($viewer)->withSession([CurrentClientAccountResolver::SESSION_KEY => $viewOnly->id]);
        $this->fakeReport('shipments');
        $this->get(route('reports.shipments'))->assertOk()->assertDontSee(__('reports.actions.export'));
        $this->get(route('reports.export', ['report' => 'shipments']))->assertForbidden();

        [$exporter, $exportAccount] = $this->account(reportView: true, reportExport: true);
        $this->actingAs($exporter)->withSession([CurrentClientAccountResolver::SESSION_KEY => $exportAccount->id]);
        $this->fakeReport('shipments', rows: [['order_date' => '2026-07-01', 'order_number' => 'A'], ['order_date' => '2026-07-02', 'order_number' => 'B']]);
        $this->get(route('reports.shipments'))->assertOk()->assertSee(__('reports.actions.export'));
        $this->get(route('reports.export', ['report' => 'shipments', 'date_from' => '2026-07-01', 'date_to' => '2026-07-31']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/shipments') && str_contains($request->url(), 'export=1'));
    }

    public function test_filters_summary_rows_pagination_and_safe_errors(): void
    {
        [$user, $account] = $this->account();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReportSequence('payments', [
            [$this->reportPayload('payments', summary: ['total_freight_amount' => '150.00', 'total_paid_amount' => '50.00', 'total_balance_amount' => '100.00', 'paid_count' => 1, 'unpaid_count' => 2], rows: [['transaction_date' => '2026-07-01', 'order_number' => 'PAY-001', 'relationship' => 'sender', 'payment_type_label' => 'เงินสดต้นทาง', 'payer' => 'Sender', 'freight_amount' => '150.00', 'paid_amount' => '50.00', 'balance_amount' => '100.00', 'payment_status_label' => 'ค้างชำระ', 'payment_date' => null]]), 200],
            [['error' => ['code' => 'RATE_LIMITED', 'message' => 'raw core message', 'status' => 429]], 429],
            [['error' => ['code' => 'RATE_LIMITED', 'message' => 'raw core message', 'status' => 429]], 429],
            [['error' => ['code' => 'RATE_LIMITED', 'message' => 'raw core message', 'status' => 429]], 429],
            [['broken' => true], 200],
            [$this->reportPayload('payments'), 200],
        ]);

        $this->get(route('reports.payments', ['date_from' => '2026-07-01', 'date_to' => '2026-07-31', 'relationship' => 'sender', 'payment_status' => 'unpaid', 'payment_type' => 'H']))
            ->assertOk()
            ->assertSee('150.00')
            ->assertSee('PAY-001')
            ->assertSee('เงินสดต้นทาง')
            ->assertSee('หน้า 1');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'relationship=sender') && str_contains($request->url(), 'payment_status=unpaid') && str_contains($request->url(), 'payment_type=H'));

        $this->get(route('reports.payments'))->assertOk()->assertSee('มีคำขอมากเกินไป')->assertDontSee('raw core message');

        $this->get(route('reports.payments'))->assertOk()->assertSee('รูปแบบข้อมูลรายงานไม่ถูกต้อง');

        app(ReportQueryService::class)->fetch($user, $account, 'payments', ['relationship' => 'receiver', 'payment_status' => 'paid']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'relationship=receiver') && str_contains($request->url(), 'payment_status=paid'));
    }

    public function test_order_checking_and_shipment_labels_render(): void
    {
        [$user, $account] = $this->account();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReport('order-checkings', summary: ['total_orders' => 2, 'single_orders' => 1, 'bulk_orders' => 1, 'checking' => 1, 'confirmed_or_new' => 1, 'rejected_or_cancelled' => 0, 'unresolved_price_orders' => 1], rows: [['submitted_at' => '2026-07-01T10:00:00+07:00', 'submission_type' => 'bulk', 'client_reference' => 'REF-BULK', 'batch_reference' => 'BATCH-1', 'order_number' => 'OH-1', 'receiver' => 'Receiver', 'item_count' => 1, 'order_status' => 'checking', 'freight_amount' => '0.00', 'pricing_status' => 'unresolved', 'submitted_by' => 'Safe User']]);
        $this->get(route('reports.order-checkings'))->assertOk()->assertSee('REF-BULK')->assertSee('BATCH-1')->assertSee('รอระบุราคา')->assertDontSeeText('unresolved');

        $this->fakeReport('shipments', rows: [['order_date' => '2026-07-01', 'order_number' => 'OH-SHIP', 'tracking_identifier' => 'TRK-1', 'relationship' => 'receiver', 'sender_name' => 'Sender', 'receiver_name' => 'Receiver', 'current_status' => 'completed', 'item_count' => 1, 'freight_amount' => '10.00', 'latest_status_time' => '2026-07-01T10:00:00+07:00']]);
        $this->get(route('reports.shipments'))->assertOk()->assertSee('OH-SHIP')->assertSee('ผู้รับ')->assertDontSeeText('receiver')->assertSee('10.00');
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function account(bool $reportView = true, bool $reportExport = true): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['name' => 'Report Account']);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001, 'can_view_payment' => true]);
        foreach ([ClientCapability::ShipmentView, ClientCapability::PaymentView, ClientCapability::OrderCreate] as $capability) {
            ClientAccountCapability::factory()->for($account)->capability($capability)->create();
        }
        if ($reportView) ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ReportView)->create();
        if ($reportExport) ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ReportExport)->create();
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');
        app()->instance(ClientAccount::class, $account);

        return [$user, $account];
    }

    private function fakeReport(string $report, array $summary = [], array $rows = []): void
    {
        $summary = $summary ?: match ($report) {
            'order-checkings' => ['total_orders' => 1, 'single_orders' => 1, 'bulk_orders' => 0, 'checking' => 1, 'confirmed_or_new' => 0, 'rejected_or_cancelled' => 0, 'unresolved_price_orders' => 0],
            'payments' => ['total_freight_amount' => '10.00', 'total_paid_amount' => '0.00', 'total_balance_amount' => '10.00', 'paid_count' => 0, 'unpaid_count' => 1],
            default => ['total_shipments' => 1, 'in_progress' => 1, 'delivered' => 0, 'pending_or_problem' => 0, 'total_freight_amount' => '10.00'],
        };
        $rows = $rows ?: [['order_date' => '2026-07-01', 'order_number' => 'Shipment No. 1', 'tracking_identifier' => 'TRK-1', 'relationship' => 'sender', 'sender_name' => 'Sender', 'receiver_name' => 'Receiver', 'current_status' => 'confirmed', 'item_count' => 1, 'freight_amount' => '10.00', 'latest_status_time' => null]];

        Http::fake(["https://sandbox-api.sisahygo.online/api/v1/client/reports/{$report}*" => Http::response($this->reportPayload($report, $summary, $rows))]);
    }

    /** @param array<int, array{0: array<string, mixed>, 1: int}> $responses */
    private function fakeReportSequence(string $report, array $responses): void
    {
        $sequence = Http::sequence();
        foreach ($responses as [$body, $status]) {
            $sequence->push($body, $status);
        }

        Http::fake(["https://sandbox-api.sisahygo.online/api/v1/client/reports/{$report}*" => $sequence]);
    }

    private function reportPayload(string $report, array $summary = [], array $rows = []): array
    {
        $summary = $summary ?: match ($report) {
            'order-checkings' => ['total_orders' => 1, 'single_orders' => 1, 'bulk_orders' => 0, 'checking' => 1, 'confirmed_or_new' => 0, 'rejected_or_cancelled' => 0, 'unresolved_price_orders' => 0],
            'payments' => ['total_freight_amount' => '10.00', 'total_paid_amount' => '0.00', 'total_balance_amount' => '10.00', 'paid_count' => 0, 'unpaid_count' => 1],
            default => ['total_shipments' => 1, 'in_progress' => 1, 'delivered' => 0, 'pending_or_problem' => 0, 'total_freight_amount' => '10.00'],
        };
        $rows = $rows ?: [['order_date' => '2026-07-01', 'order_number' => 'Shipment No. 1', 'tracking_identifier' => 'TRK-1', 'relationship' => 'sender', 'sender_name' => 'Sender', 'receiver_name' => 'Receiver', 'current_status' => 'confirmed', 'item_count' => 1, 'freight_amount' => '10.00', 'latest_status_time' => null]];

        return ['data' => ['summary' => $summary, 'rows' => $rows, 'pagination' => ['current_page' => 1, 'per_page' => 25, 'total' => count($rows), 'last_page' => 1]], 'meta' => ['report' => $report, 'filters' => ['date_from' => '2026-07-01', 'date_to' => '2026-07-31'], 'generated_at' => '2026-07-31T10:00:00+07:00', 'export_limit' => 5000]];
    }
}

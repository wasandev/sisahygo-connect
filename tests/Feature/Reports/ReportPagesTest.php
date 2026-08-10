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

        $this->get(route('reports'))->assertOk()->assertSee('รายงานสรุปการจัดส่งสินค้า')->assertSee('รายงานสถานะและระยะเวลาการขนส่ง')->assertSee('รายงานผู้รับและพื้นที่จัดส่ง')->assertSee('รายงานสินค้าและปริมาณการขนส่ง')->assertSee('รายงานรายการที่สร้างผ่าน Sisahygo Connect')->assertSee('รายงานค่าขนส่งและสถานะการชำระเงิน')->assertDontSee('STATUS-HIDDEN');
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


    public function test_shipment_status_report_renders_timeline_filters_and_export(): void
    {
        [$user, $account] = $this->account(reportView: true, reportExport: true);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReport('shipment-status', summary: [
            'total_shipments' => 1,
            'waiting' => 0,
            'in_transit' => 1,
            'arrival' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'problem' => 0,
            'average_processing_time' => '2d 1h',
            'oldest_pending_shipment' => 'ST-001 / 2d 1h',
        ], rows: [[
            'shipment_date' => '2026-07-01',
            'tracking_number' => 'TRK-ST-001',
            'order_number' => 'ST-001',
            'sender' => 'Sender',
            'receiver' => 'Receiver',
            'relationship' => 'both',
            'current_status' => 'loaded',
            'current_branch' => 'Bangkok Hub',
            'last_update' => '2026-07-02T10:00:00+07:00',
            'processing_time' => '2d 1h',
            'delayed' => true,
            'timeline' => [
                ['status' => 'checking', 'date' => '2026-07-01', 'time' => '08:00:00', 'user' => 'Checker User', 'remark' => null],
                ['status' => 'loaded', 'date' => '2026-07-02', 'time' => '10:00:00', 'user' => 'Loader User', 'remark' => 'Loaded to truck'],
            ],
        ]]);

        $this->get(route('reports.shipment-status', ['only_delayed' => 1, 'only_in_progress' => 1]))
            ->assertOk()
            ->assertSee('รายงานสถานะและระยะเวลาการขนส่ง')
            ->assertSee('เฉพาะงานล่าช้า')
            ->assertSee('เฉพาะงานที่กำลังดำเนินการ')
            ->assertSee('ST-001')
            ->assertSee('ผู้ส่งและผู้รับ')
            ->assertSee('ขึ้นรถแล้ว')
            ->assertSee('ใช่')
            ->assertSee('ดูลำดับเหตุการณ์การขนส่ง')
            ->assertSee('Checker User')
            ->assertSee('Loaded to truck');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/shipment-status') && str_contains($request->url(), 'only_delayed=1') && str_contains($request->url(), 'only_in_progress=1'));

        $download = $this->get(route('reports.export', ['report' => 'shipment-status', 'date_from' => '2026-07-01', 'date_to' => '2026-07-31']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('sisahygo-shipment-status-report-20260701-20260731.xlsx', $download->headers->get('content-disposition'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/shipment-status') && str_contains($request->url(), 'export=1'));
    }


    public function test_receiver_report_renders_filters_rows_pagination_and_export(): void
    {
        [$user, $account] = $this->account(reportView: true, reportExport: true);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReport('receivers', summary: [
            'total_shipments' => 3,
            'unique_receivers' => 2,
            'total_quantity' => '12.0000',
            'total_freight_amount' => '250.00',
            'top_receiver' => 'ผู้รับหลัก / 2',
            'top_destination_province' => 'กรุงเทพมหานคร / 2',
        ], rows: [[
            'receiver' => 'ผู้รับหลัก',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'บางรัก',
            'sub_district' => 'สีลม',
            'shipment_count' => 2,
            'total_quantity' => '10.0000',
            'freight_amount' => '200.00',
            'average_freight_per_shipment' => '100.00',
            'last_shipment_date' => '2026-07-03',
        ]]);

        $this->get(route('reports.receivers', ['province' => 'กรุงเทพมหานคร', 'district' => 'บางรัก', 'sub_district' => 'สีลม', 'search' => 'ผู้รับ']))
            ->assertOk()
            ->assertSee('รายงานผู้รับและพื้นที่จัดส่ง')
            ->assertSee('จังหวัด')
            ->assertSee('อำเภอ/เขต')
            ->assertSee('ตำบล/แขวง')
            ->assertSee('ผู้รับหลัก')
            ->assertSee('200.00')
            ->assertSee('หน้า 1');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/receivers') && str_contains($request->url(), 'province=') && str_contains($request->url(), 'district=') && str_contains($request->url(), 'sub_district='));

        $download = $this->get(route('reports.export', ['report' => 'receivers', 'date_from' => '2026-07-01', 'date_to' => '2026-07-31']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('sisahygo-receiver-area-report-20260701-20260731.xlsx', $download->headers->get('content-disposition'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/receivers') && str_contains($request->url(), 'export=1'));
    }

    public function test_product_report_renders_filters_rows_pagination_and_export(): void
    {
        [$user, $account] = $this->account(reportView: true, reportExport: true);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReport('products', summary: [
            'total_shipments' => 2,
            'total_product_lines' => 3,
            'total_quantity' => '9.0000',
            'unique_products' => 1,
            'total_freight_amount' => '90.00',
            'top_product_by_quantity' => 'สินค้า A / 9.0000',
        ], rows: [[
            'product' => 'สินค้า A',
            'unit' => 'ลัง',
            'shipment_count' => 2,
            'quantity' => '9.0000',
            'receiver_count' => 2,
            'freight_amount' => '90.00',
            'average_quantity_per_shipment' => '4.5000',
            'last_shipment_date' => '2026-07-02',
        ]]);

        $this->get(route('reports.products', ['product' => 'สินค้า A', 'unit' => 'ลัง', 'search' => 'สินค้า']))
            ->assertOk()
            ->assertSee('รายงานสินค้าและปริมาณการขนส่ง')
            ->assertSee('สินค้า')
            ->assertSee('หน่วย')
            ->assertSee('สินค้า A')
            ->assertSee('ลัง')
            ->assertSee('90.00')
            ->assertSee('หน้า 1');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/products') && str_contains($request->url(), 'product=') && str_contains($request->url(), 'unit='));

        $download = $this->get(route('reports.export', ['report' => 'products', 'date_from' => '2026-07-01', 'date_to' => '2026-07-31']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('sisahygo-product-volume-report-20260701-20260731.xlsx', $download->headers->get('content-disposition'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reports/products') && str_contains($request->url(), 'export=1'));
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
            'shipment-status' => ['total_shipments' => 1, 'waiting' => 0, 'in_transit' => 1, 'arrival' => 0, 'delivered' => 0, 'cancelled' => 0, 'problem' => 0, 'average_processing_time' => '1h', 'oldest_pending_shipment' => 'Shipment No. 1 / 1h'],
            'receivers' => ['total_shipments' => 1, 'unique_receivers' => 1, 'total_quantity' => '1.0000', 'total_freight_amount' => '10.00', 'top_receiver' => 'Receiver / 1', 'top_destination_province' => 'กรุงเทพมหานคร / 1'],
            'products' => ['total_shipments' => 1, 'total_product_lines' => 1, 'total_quantity' => '1.0000', 'unique_products' => 1, 'total_freight_amount' => '10.00', 'top_product_by_quantity' => 'Product / 1.0000'],
            'payments' => ['total_freight_amount' => '10.00', 'total_paid_amount' => '0.00', 'total_balance_amount' => '10.00', 'paid_count' => 0, 'unpaid_count' => 1],
            default => ['total_shipments' => 1, 'in_progress' => 1, 'delivered' => 0, 'pending_or_problem' => 0, 'total_freight_amount' => '10.00'],
        };
        $rows = $rows ?: match ($report) {
            'shipment-status' => [['shipment_date' => '2026-07-01', 'tracking_number' => 'TRK-1', 'order_number' => 'Shipment No. 1', 'sender' => 'Sender', 'receiver' => 'Receiver', 'relationship' => 'sender', 'current_status' => 'loaded', 'current_branch' => 'Bangkok Hub', 'last_update' => null, 'processing_time' => '1h', 'delayed' => false, 'timeline' => []]],
            'receivers' => [['receiver' => 'Receiver', 'province' => 'กรุงเทพมหานคร', 'district' => 'บางรัก', 'sub_district' => 'สีลม', 'shipment_count' => 1, 'total_quantity' => '1.0000', 'freight_amount' => '10.00', 'average_freight_per_shipment' => '10.00', 'last_shipment_date' => '2026-07-01']],
            'products' => [['product' => 'Product', 'unit' => 'ลัง', 'shipment_count' => 1, 'quantity' => '1.0000', 'receiver_count' => 1, 'freight_amount' => '10.00', 'average_quantity_per_shipment' => '1.0000', 'last_shipment_date' => '2026-07-01']],
            default => [['order_date' => '2026-07-01', 'order_number' => 'Shipment No. 1', 'tracking_identifier' => 'TRK-1', 'relationship' => 'sender', 'sender_name' => 'Sender', 'receiver_name' => 'Receiver', 'current_status' => 'confirmed', 'item_count' => 1, 'freight_amount' => '10.00', 'latest_status_time' => null]],
        };

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
            'shipment-status' => ['total_shipments' => 1, 'waiting' => 0, 'in_transit' => 1, 'arrival' => 0, 'delivered' => 0, 'cancelled' => 0, 'problem' => 0, 'average_processing_time' => '1h', 'oldest_pending_shipment' => 'Shipment No. 1 / 1h'],
            'receivers' => ['total_shipments' => 1, 'unique_receivers' => 1, 'total_quantity' => '1.0000', 'total_freight_amount' => '10.00', 'top_receiver' => 'Receiver / 1', 'top_destination_province' => 'กรุงเทพมหานคร / 1'],
            'products' => ['total_shipments' => 1, 'total_product_lines' => 1, 'total_quantity' => '1.0000', 'unique_products' => 1, 'total_freight_amount' => '10.00', 'top_product_by_quantity' => 'Product / 1.0000'],
            'payments' => ['total_freight_amount' => '10.00', 'total_paid_amount' => '0.00', 'total_balance_amount' => '10.00', 'paid_count' => 0, 'unpaid_count' => 1],
            default => ['total_shipments' => 1, 'in_progress' => 1, 'delivered' => 0, 'pending_or_problem' => 0, 'total_freight_amount' => '10.00'],
        };
        $rows = $rows ?: match ($report) {
            'shipment-status' => [['shipment_date' => '2026-07-01', 'tracking_number' => 'TRK-1', 'order_number' => 'Shipment No. 1', 'sender' => 'Sender', 'receiver' => 'Receiver', 'relationship' => 'sender', 'current_status' => 'loaded', 'current_branch' => 'Bangkok Hub', 'last_update' => null, 'processing_time' => '1h', 'delayed' => false, 'timeline' => []]],
            'receivers' => [['receiver' => 'Receiver', 'province' => 'กรุงเทพมหานคร', 'district' => 'บางรัก', 'sub_district' => 'สีลม', 'shipment_count' => 1, 'total_quantity' => '1.0000', 'freight_amount' => '10.00', 'average_freight_per_shipment' => '10.00', 'last_shipment_date' => '2026-07-01']],
            'products' => [['product' => 'Product', 'unit' => 'ลัง', 'shipment_count' => 1, 'quantity' => '1.0000', 'receiver_count' => 1, 'freight_amount' => '10.00', 'average_quantity_per_shipment' => '1.0000', 'last_shipment_date' => '2026-07-01']],
            default => [['order_date' => '2026-07-01', 'order_number' => 'Shipment No. 1', 'tracking_identifier' => 'TRK-1', 'relationship' => 'sender', 'sender_name' => 'Sender', 'receiver_name' => 'Receiver', 'current_status' => 'confirmed', 'item_count' => 1, 'freight_amount' => '10.00', 'latest_status_time' => null]],
        };

        $data = ['summary' => $summary, 'rows' => $rows, 'pagination' => ['current_page' => 1, 'per_page' => 25, 'total' => count($rows), 'last_page' => 1]];
        if ($report === 'receivers') $data['area_rows'] = [['province' => 'กรุงเทพมหานคร', 'district' => 'บางรัก', 'sub_district' => 'สีลม', 'shipment_count' => 1, 'unique_receivers' => 1, 'total_quantity' => '1.0000', 'freight_amount' => '10.00']];
        if ($report === 'products') $data['product_details'] = [['shipment_date' => '2026-07-01', 'order_number' => 'PD-1', 'receiver' => 'Receiver', 'product' => 'Product', 'unit' => 'ลัง', 'quantity' => '1.0000', 'unit_price' => '10.00', 'freight_amount' => '10.00', 'client_item_no' => 'I-1']];

        return ['data' => $data, 'meta' => ['report' => $report, 'filters' => ['date_from' => '2026-07-01', 'date_to' => '2026-07-31'], 'generated_at' => '2026-07-31T10:00:00+07:00', 'export_limit' => 5000]];
    }
}

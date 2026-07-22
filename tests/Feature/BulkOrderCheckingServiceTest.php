<?php

namespace Tests\Feature;

use App\Application\OrderChecking\SubmitBulkOrderChecking;
use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BulkOrderCheckingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_classifies_all_success_and_posts_once(): void
    {
        [$user, $account] = $this->account();
        Http::fake(['*' => Http::response($this->fixture('order-checking-bulk-success.json'), 201)]);

        $result = app(SubmitBulkOrderChecking::class)->submit($user, $account, 10001, $this->payload());

        $this->assertSame('all_succeeded', $result->outcome());
        Http::assertSentCount(1);
    }

    public function test_submit_classifies_partial_success(): void
    {
        [$user, $account] = $this->account();
        Http::fake(['*' => Http::response($this->fixture('order-checking-bulk-partial.json'), 207)]);

        $result = app(SubmitBulkOrderChecking::class)->submit($user, $account, 10001, $this->payload());

        $this->assertSame('partial_success', $result->outcome());
        $this->assertSame(1, $result->summary->failed);
    }

    public function test_failed_retry_payload_excludes_successful_rows(): void
    {
        $service = app(SubmitBulkOrderChecking::class);
        $orders = $this->payload()['orders'];
        $result = json_decode($this->fixture('order-checking-bulk-partial.json'), true)['data'];
        $result['outcome'] = 'partial_success';

        $retry = $service->failedRetryOrders($orders, $result);

        $this->assertCount(1, $retry);
        $this->assertSame('BC-20260721-002', $retry[0]['client_reference_no']);
    }

    public function test_duplicate_client_reference_is_rejected_locally_on_both_rows(): void
    {
        [$user, $account] = $this->account();
        $payload = $this->payload();
        $payload['orders'][1]['client_reference_no'] = 'BC-20260721-001';
        Http::fake(['*' => Http::response($this->fixture('order-checking-bulk-success.json'), 201)]);

        try {
            app(SubmitBulkOrderChecking::class)->submit($user, $account, 10001, $payload);
            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('orders.0.client_reference_no', $exception->errors());
            $this->assertArrayHasKey('orders.1.client_reference_no', $exception->errors());
            Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        }
    }

    public function test_network_uncertainty_surfaces_connection_exception_without_retry(): void
    {
        [$user, $account] = $this->account();
        Http::fake(fn () => throw new ConnectionException('timed out'));

        try {
            app(SubmitBulkOrderChecking::class)->submit($user, $account, 10001, $this->payload());
            $this->fail('Expected connection exception.');
        } catch (SisahygoConnectionException) {
            $this->assertTrue(true);
        }
    }

    private function payload(): array
    {
        return [
            'batch_reference_no' => 'BATCH-20260721',
            'batch_date' => '2026-07-21',
            'orders' => [
                $this->order('BC-20260721-001'),
                $this->order('BC-20260721-002'),
            ],
        ];
    }

    private function order(string $reference): array
    {
        return [
            'client_reference_no' => $reference,
            'customer_rec_id' => 20001,
            'remark' => '',
            'items' => [[
                'product_id' => 6639,
                'unit_id' => 1,
                'amount' => '2.5000',
                'remark' => '',
                'client_line_id' => 'L1',
                'client_item_no' => 'ITEM001',
                'client_product_code' => 'FG001',
            ]],
        ];
    }

    private function account(): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderBulk)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}

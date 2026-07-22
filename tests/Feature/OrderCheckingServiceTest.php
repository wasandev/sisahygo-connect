<?php

namespace Tests\Feature;

use App\Application\OrderChecking\SubmitSingleOrderChecking;
use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderCheckingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_enforces_active_sender_relationship(): void
    {
        [$user, $account] = $this->account(withSender: false);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $this->expectException(ValidationException::class);

        app(SubmitSingleOrderChecking::class)->submit(
            user: $user,
            clientAccount: $account,
            selectedSenderCustomerId: null,
            receiverCustomerId: 20001,
            clientReferenceNo: 'SC-20260716-ABC123',
            remark: null,
            items: [$this->item()],
        );
    }

    public function test_submit_revalidates_receiver_product_unit_and_posts_once(): void
    {
        [$user, $account] = $this->account();
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/receivers*' => Http::response($this->fixture('receivers-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/products*' => Http::response($this->fixture('products-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings' => Http::response($this->fixture('order-checking-success.json'), 201),
        ]);

        $result = app(SubmitSingleOrderChecking::class)->submit(
            user: $user,
            clientAccount: $account,
            selectedSenderCustomerId: 10001,
            receiverCustomerId: 20001,
            clientReferenceNo: 'SC-20260716-ABC123',
            remark: 'ส่งก่อนบ่าย',
            items: [$this->item()],
        );

        $this->assertSame('checking', $result->orderStatus);
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings');
    }

    public function test_unauthorized_product_manipulation_is_rejected_before_post(): void
    {
        [$user, $account] = $this->account();
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/receivers*' => Http::response($this->fixture('receivers-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/products*' => Http::response(['data' => []]),
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings' => Http::response($this->fixture('order-checking-success.json'), 201),
        ]);

        try {
            app(SubmitSingleOrderChecking::class)->submit($user, $account, 10001, 20001, 'SC-20260716-ABC123', null, [$this->item()]);
            $this->fail('Expected validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.product_id', $exception->errors());
            Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        }
    }

    private function account(bool $withSender = true): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();

        if ($withSender) {
            ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        }

        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }

    private function item(): array
    {
        return [
            'product_id' => 6639,
            'unit_id' => 1,
            'amount' => 2,
            'remark' => 'line test',
            'client_line_id' => 'L1',
            'client_item_no' => 'ITEM001',
            'client_product_code' => 'FG001',
        ];
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}

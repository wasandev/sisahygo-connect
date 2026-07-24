<?php

namespace Tests\Feature\Onboarding;

use App\Application\Onboarding\SubmitAccessRequest;
use App\Domain\Onboarding\Models\AccessRequest as LocalAccessRequest;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use App\Livewire\Onboarding\RequestAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AccessRequestCoreSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sisahygo.api.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');
        app()->forgetInstance(SisahygoApiConfiguration::class);
    }

    public function test_successful_access_request_submission_posts_to_core_and_does_not_store_local_request(): void
    {
        Http::fake(['*' => Http::response($this->successResponse(), 201)]);

        $result = app(SubmitAccessRequest::class)->submit($this->payload(), 'CONNECT-REQ-20260724-ABC123');

        $this->assertSame('CAR-20260724-ABCDEFGH', $result->requestNo);
        $this->assertSame('pending', $result->status);
        $this->assertFalse($result->duplicate);
        $this->assertSame(0, LocalAccessRequest::query()->count());

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/access-requests'
                && ! $request->hasHeader('X-Api-Key')
                && $request->hasHeader('X-Correlation-ID')
                && $payload['connect_reference'] === 'CONNECT-REQ-20260724-ABC123'
                && $payload['company_name'] === 'Acme Logistics'
                && $payload['contact_name'] === 'Anong Contact'
                && $payload['email'] === 'contact@example.com'
                && $payload['phone'] === '0812345678'
                && $payload['province'] === 'กรุงเทพมหานคร'
                && $payload['website'] === 'https://example.com'
                && $payload['branch_count'] === 3
                && $payload['notes'] === 'Need Connect onboarding.'
                && array_key_exists('submitted_at', $payload)
                && ! array_key_exists('number_of_branches', $payload)
                && ! array_key_exists('additional_notes', $payload)
                && ! array_key_exists('business_type', $payload)
                && ! array_key_exists('expected_monthly_shipments', $payload)
                && ! array_key_exists('note', $payload);
        });
    }

    public function test_payload_mapping_uses_core_contract_field_names(): void
    {
        $dto = app(SubmitAccessRequest::class)->map($this->payload([
            'email' => ' CONTACT@EXAMPLE.COM ',
            'number_of_branches' => 7,
            'additional_notes' => '  Please call first.  ',
        ]), 'CONNECT-REQ-20260724-MAPPED');

        $this->assertSame([
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'email' => 'contact@example.com',
            'phone' => '0812345678',
            'province' => 'กรุงเทพมหานคร',
            'website' => 'https://example.com',
            'branch_count' => 7,
            'notes' => 'Please call first.',
            'connect_reference' => 'CONNECT-REQ-20260724-MAPPED',
        ], array_diff_key($dto->toPayload(), ['submitted_at' => true]));
        $this->assertArrayHasKey('submitted_at', $dto->toPayload());
    }

    public function test_validation_failure_is_exposed_as_safe_validation_exception(): void
    {
        Http::fake(['*' => Http::response([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'details' => ['email' => ['อีเมลไม่ถูกต้อง']],
                'status' => 422,
            ],
        ], 422)]);

        $this->expectException(SisahygoValidationException::class);

        app(SubmitAccessRequest::class)->submit($this->payload(), 'CONNECT-REQ-20260724-INVALID');
    }

    public function test_duplicate_reference_response_maps_core_duplicate_meta(): void
    {
        Http::fake(['*' => Http::response($this->successResponse(duplicate: true), 200)]);

        $result = app(SubmitAccessRequest::class)->submit($this->payload(), 'CONNECT-REQ-20260724-DUPLICATE');

        $this->assertTrue($result->duplicate);
        $this->assertSame('CONNECT-REQ-20260724-DUPLICATE', $result->connectReference);
    }

    public function test_core_authentication_error_is_mapped_safely_without_exposing_headers(): void
    {
        Http::fake(['*' => Http::response([
            'error' => [
                'code' => 'API_KEY_INVALID',
                'message' => 'Invalid API key',
                'status' => 401,
            ],
        ], 401)]);

        try {
            app(SubmitAccessRequest::class)->submit($this->payload(), 'CONNECT-REQ-20260724-AUTH');
            $this->fail('Expected authentication exception.');
        } catch (SisahygoAuthenticationException $exception) {
            $this->assertSame(401, $exception->status);
            $this->assertArrayNotHasKey('api_key', $exception->safeContext());
            $this->assertArrayNotHasKey('X-Api-Key', $exception->safeContext());
        }
    }

    public function test_core_unavailable_timeout_is_mapped_to_connection_exception(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out.'));

        $this->expectException(SisahygoConnectionException::class);

        app(SubmitAccessRequest::class)->submit($this->payload(), 'CONNECT-REQ-20260724-TIMEOUT');
    }

    public function test_livewire_success_state_displays_core_request_number_and_email(): void
    {
        Http::fake(['*' => Http::response($this->successResponse(), 201)]);

        Livewire::test(RequestAccess::class)
            ->set('company_name', 'Acme Logistics')
            ->set('contact_name', 'Anong Contact')
            ->set('email', 'contact@example.com')
            ->set('phone', '0812345678')
            ->set('province', 'กรุงเทพมหานคร')
            ->call('submit')
            ->assertSet('state', 'success')
            ->assertSet('hasSubmitted', true)
            ->assertSee('CAR-20260724-ABCDEFGH')
            ->assertSee('contact@example.com')
            ->assertSee('รออนุมัติ');

        $this->assertSame(0, LocalAccessRequest::query()->count());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => ! $request->hasHeader('X-Api-Key'));
    }

    public function test_livewire_error_state_shows_safe_message_without_raw_core_body(): void
    {
        Http::fake(['*' => Http::response([
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => 'SQLSTATE raw internals should not appear',
                'status' => 500,
            ],
        ], 500)]);

        Livewire::test(RequestAccess::class)
            ->set('company_name', 'Acme Logistics')
            ->set('contact_name', 'Anong Contact')
            ->set('email', 'contact@example.com')
            ->set('phone', '0812345678')
            ->set('province', 'กรุงเทพมหานคร')
            ->call('submit')
            ->assertSet('state', 'editing')
            ->assertSee('Sisahygo Core ยังไม่พร้อมให้บริการชั่วคราว')
            ->assertDontSee('SQLSTATE raw internals');
    }

    public function test_submission_has_no_direct_core_database_dependency(): void
    {
        Http::fake(['*' => Http::response($this->successResponse(), 201)]);

        app(SubmitAccessRequest::class)->submit($this->payload(), 'CONNECT-REQ-20260724-NODB');

        $this->assertDatabaseCount('access_requests', 0);
        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
        Http::assertSentCount(1);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'email' => 'contact@example.com',
            'phone' => '0812345678',
            'province' => 'กรุงเทพมหานคร',
            'website' => 'https://example.com',
            'number_of_branches' => 3,
            'additional_notes' => 'Need Connect onboarding.',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function successResponse(bool $duplicate = false): array
    {
        return [
            'data' => [
                'request_no' => 'CAR-20260724-ABCDEFGH',
                'public_id' => 'CAR-20260724-ABCDEFGH',
                'connect_reference' => $duplicate ? 'CONNECT-REQ-20260724-DUPLICATE' : 'CONNECT-REQ-20260724-ABC123',
                'status' => 'pending',
                'status_label' => 'รออนุมัติ',
                'submitted_at' => '2026-07-24T10:00:00+07:00',
            ],
            'meta' => ['duplicate' => $duplicate],
        ];
    }
}

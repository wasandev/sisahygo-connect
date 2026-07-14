<?php

namespace Tests\Feature\Integrations\Sisahygo;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SisahygoCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_hidden_from_serialization(): void
    {
        $account = ClientAccount::factory()->create();
        $credential = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        $raw = DB::table('sisahygo_api_credentials')->where('id', $credential->id)->value('encrypted_api_key');

        $this->assertNotSame('secret-api-key', $raw);
        $this->assertSame('secret-api-key', $credential->fresh()->apiKey());
        $this->assertArrayNotHasKey('encrypted_api_key', $credential->fresh()->toArray());
        $this->assertSame(SisahygoApiCredential::fingerprint('secret-api-key'), $credential->key_fingerprint);
    }

    public function test_active_credential_resolution_respects_environment_and_revocation(): void
    {
        $account = ClientAccount::factory()->create();
        $service = app(SisahygoApiCredentialService::class);

        $sandbox = $service->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'sandbox-key');
        $production = $service->create($account, SisahygoApiEnvironment::Production, 'Production', 'production-key');

        $this->assertTrue($service->resolveActive($account, SisahygoApiEnvironment::Sandbox)->is($sandbox));
        $this->assertTrue($service->resolveActive($account, SisahygoApiEnvironment::Production)->is($production));

        $service->revoke($sandbox);

        $this->assertSame(SisahygoCredentialStatus::Revoked, $sandbox->fresh()->status);
        $this->assertNull($sandbox->fresh()->active_slot);
    }

    public function test_creating_new_active_credential_rotates_previous_one(): void
    {
        $account = ClientAccount::factory()->create();
        $service = app(SisahygoApiCredentialService::class);

        $old = $service->create($account, SisahygoApiEnvironment::Sandbox, 'Old', 'old-key');
        $new = $service->create($account, SisahygoApiEnvironment::Sandbox, 'New', 'new-key');

        $this->assertSame(SisahygoCredentialStatus::Revoked, $old->fresh()->status);
        $this->assertTrue($service->resolveActive($account, SisahygoApiEnvironment::Sandbox)->is($new));
        $this->assertSame($old->id, $new->rotated_from_id);
    }

    public function test_cross_account_credential_use_is_rejected(): void
    {
        $first = ClientAccount::factory()->create();
        $second = ClientAccount::factory()->create();
        $credential = app(SisahygoApiCredentialService::class)->create($first, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'first-key');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        app(SisahygoApiCredentialService::class)->credentialForAccount($second, $credential->id);
    }

    public function test_last_used_timestamp_is_updated(): void
    {
        $account = ClientAccount::factory()->create();
        $credential = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        app(SisahygoApiCredentialService::class)->markLastUsed($credential);

        $this->assertNotNull($credential->fresh()->last_used_at);
    }
}
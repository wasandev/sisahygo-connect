<?php

namespace Database\Factories\Domain\Sisahygo\Models;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SisahygoApiCredential>
 */
class SisahygoApiCredentialFactory extends Factory
{
    protected $model = SisahygoApiCredential::class;

    public function definition(): array
    {
        $apiKey = 'fake_sisahygo_'.fake()->unique()->sha256();

        return [
            'client_account_id' => ClientAccount::factory(),
            'environment' => SisahygoApiEnvironment::Sandbox,
            'name' => 'Sandbox credential',
            'encrypted_api_key' => $apiKey,
            'key_fingerprint' => SisahygoApiCredential::fingerprint($apiKey),
            'status' => SisahygoCredentialStatus::Active,
            'active_slot' => 'active',
        ];
    }

    public function production(): static
    {
        return $this->state(fn () => ['environment' => SisahygoApiEnvironment::Production]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => SisahygoCredentialStatus::Revoked,
            'active_slot' => null,
            'revoked_at' => now(),
        ]);
    }
}
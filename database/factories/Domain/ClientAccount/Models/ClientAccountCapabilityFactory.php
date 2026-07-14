<?php

namespace Database\Factories\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAccountCapability>
 */
class ClientAccountCapabilityFactory extends Factory
{
    protected $model = ClientAccountCapability::class;

    public function definition(): array
    {
        return [
            'client_account_id' => ClientAccount::factory(),
            'capability' => fake()->randomElement(ClientCapability::cases()),
            'is_enabled' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_enabled' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_enabled' => false,
        ]);
    }

    public function capability(ClientCapability $capability): static
    {
        return $this->state(fn () => [
            'capability' => $capability,
        ]);
    }
}
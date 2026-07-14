<?php

namespace Database\Factories\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAccount>
 */
class ClientAccountFactory extends Factory
{
    protected $model = ClientAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->bothify('ACCT-####')),
            'status' => ClientAccountStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ClientAccountStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => ClientAccountStatus::Suspended,
        ]);
    }
}
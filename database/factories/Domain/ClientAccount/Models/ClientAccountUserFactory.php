<?php

namespace Database\Factories\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAccountUser>
 */
class ClientAccountUserFactory extends Factory
{
    protected $model = ClientAccountUser::class;

    public function definition(): array
    {
        return [
            'client_account_id' => ClientAccount::factory(),
            'user_id' => User::factory(),
            'role' => ClientAccountRole::Viewer,
            'is_active' => true,
            'joined_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function owner(): static
    {
        return $this->role(ClientAccountRole::Owner);
    }

    public function administrator(): static
    {
        return $this->role(ClientAccountRole::Administrator);
    }

    public function operator(): static
    {
        return $this->role(ClientAccountRole::Operator);
    }

    public function viewer(): static
    {
        return $this->role(ClientAccountRole::Viewer);
    }

    public function accounting(): static
    {
        return $this->role(ClientAccountRole::Accounting);
    }

    private function role(ClientAccountRole $role): static
    {
        return $this->state(fn () => [
            'role' => $role,
        ]);
    }
}
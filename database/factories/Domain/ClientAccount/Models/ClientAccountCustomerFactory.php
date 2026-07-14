<?php

namespace Database\Factories\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAccountCustomer>
 */
class ClientAccountCustomerFactory extends Factory
{
    protected $model = ClientAccountCustomer::class;

    public function definition(): array
    {
        return [
            'client_account_id' => ClientAccount::factory(),
            'customer_id' => fake()->unique()->numberBetween(90001, 99999),
            'can_send' => false,
            'can_receive' => false,
            'can_view_payment' => false,
            'is_default_sender' => false,
            'is_default_receiver' => false,
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function sender(): static
    {
        return $this->state(fn () => [
            'can_send' => true,
            'can_receive' => false,
            'is_default_sender' => true,
            'is_default_receiver' => false,
        ]);
    }

    public function receiver(): static
    {
        return $this->state(fn () => [
            'can_send' => false,
            'can_receive' => true,
            'is_default_sender' => false,
            'is_default_receiver' => true,
        ]);
    }

    public function senderAndReceiver(): static
    {
        return $this->state(fn () => [
            'can_send' => true,
            'can_receive' => true,
            'is_default_sender' => true,
            'is_default_receiver' => true,
        ]);
    }

    public function withPaymentAccess(): static
    {
        return $this->state(fn () => [
            'can_view_payment' => true,
        ]);
    }
}
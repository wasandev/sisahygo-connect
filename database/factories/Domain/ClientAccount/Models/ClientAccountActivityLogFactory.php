<?php

namespace Database\Factories\Domain\ClientAccount\Models;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAccountActivityLog>
 */
class ClientAccountActivityLogFactory extends Factory
{
    protected $model = ClientAccountActivityLog::class;

    public function definition(): array
    {
        return [
            'client_account_id' => ClientAccount::factory(),
            'user_id' => User::factory(),
            'event' => 'demo.event',
            'metadata' => ['source' => 'factory'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Sisahygo Connect Factory',
        ];
    }
}
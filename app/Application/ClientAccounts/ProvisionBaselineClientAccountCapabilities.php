<?php

namespace App\Application\ClientAccounts;

use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;

class ProvisionBaselineClientAccountCapabilities
{
    /** @return list<ClientCapability> */
    public function baseline(): array
    {
        return [
            ClientCapability::OrderCreate,
            ClientCapability::OrderBulk,
            ClientCapability::ShipmentView,
            ClientCapability::ShipmentHistory,
            ClientCapability::PaymentView,
        ];
    }

    /** @return list<ClientCapability> */
    public function missingFor(ClientAccount $account): array
    {
        $baseline = $this->baseline();
        $existing = $account->capabilities()
            ->whereIn('capability', array_map(fn (ClientCapability $capability) => $capability->value, $baseline))
            ->pluck('capability')
            ->map(fn (ClientCapability|string $capability) => $capability instanceof ClientCapability ? $capability->value : $capability)
            ->all();

        return array_values(array_filter(
            $baseline,
            fn (ClientCapability $capability) => ! in_array($capability->value, $existing, true),
        ));
    }

    /** @return list<ClientCapability> */
    public function provision(ClientAccount $account): array
    {
        if ($account->status !== ClientAccountStatus::Active) {
            return [];
        }

        $missing = $this->missingFor($account);

        foreach ($missing as $capability) {
            ClientAccountCapability::query()->create([
                'client_account_id' => $account->id,
                'capability' => $capability->value,
                'is_enabled' => true,
            ]);
        }

        return $missing;
    }
}

<?php

namespace App\Domain\Shipment\Queries;

use App\Domain\ClientAccount\Models\ClientAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuthorizedOrderQuery
{
    public function forClientAccount(ClientAccount $clientAccount): Builder
    {
        $senderCustomerIds = $clientAccount->customerLinks()
            ->where('is_active', true)
            ->where('can_send', true)
            ->pluck('customer_id');

        $receiverCustomerIds = $clientAccount->customerLinks()
            ->where('is_active', true)
            ->where('can_receive', true)
            ->pluck('customer_id');

        return DB::table('order_headers')
            ->where(function (Builder $query) use ($senderCustomerIds, $receiverCustomerIds): void {
                $query->whereIn('customer_id', $senderCustomerIds)
                    ->orWhereIn('customer_rec_id', $receiverCustomerIds);
            });
    }

    public function findAuthorized(ClientAccount $clientAccount, int $orderId): ?object
    {
        return $this->forClientAccount($clientAccount)
            ->where('id', $orderId)
            ->first();
    }
}
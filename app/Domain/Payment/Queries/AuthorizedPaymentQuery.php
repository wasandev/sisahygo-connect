<?php

namespace App\Domain\Payment\Queries;

use App\Domain\ClientAccount\Models\ClientAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuthorizedPaymentQuery
{
    public const RECEIVER_VISIBLE_PAYMENT_TYPES = ['E', 'L'];

    public function forClientAccount(ClientAccount $clientAccount): Builder
    {
        $receiverCustomerIds = $clientAccount->customerLinks()
            ->where('is_active', true)
            ->where('can_receive', true)
            ->where('can_view_payment', true)
            ->pluck('customer_id');

        return DB::table('order_headers')
            ->whereIn('customer_rec_id', $receiverCustomerIds)
            ->whereIn('paymenttype', self::RECEIVER_VISIBLE_PAYMENT_TYPES);
    }

    public function findAuthorized(ClientAccount $clientAccount, int $orderId): ?object
    {
        return $this->forClientAccount($clientAccount)
            ->where('id', $orderId)
            ->first();
    }
}
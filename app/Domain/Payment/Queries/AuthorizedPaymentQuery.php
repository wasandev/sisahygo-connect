<?php

namespace App\Domain\Payment\Queries;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Payment\Enums\PaymentType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuthorizedPaymentQuery
{
    public function forClientAccount(ClientAccount $clientAccount): Builder
    {
        $senderCustomerIds = $clientAccount->customerLinks()
            ->where('is_active', true)
            ->where('can_send', true)
            ->where('can_view_payment', true)
            ->pluck('customer_id');

        $receiverCustomerIds = $clientAccount->customerLinks()
            ->where('is_active', true)
            ->where('can_receive', true)
            ->where('can_view_payment', true)
            ->pluck('customer_id');

        return DB::table('order_headers')
            ->where(function (Builder $query) use ($senderCustomerIds, $receiverCustomerIds): void {
                $query->where(function (Builder $senderQuery) use ($senderCustomerIds): void {
                    $senderQuery->whereIn('customer_id', $senderCustomerIds)
                        ->whereIn('paymenttype', PaymentType::senderVisibleValues());
                })->orWhere(function (Builder $receiverQuery) use ($receiverCustomerIds): void {
                    $receiverQuery->whereIn('customer_rec_id', $receiverCustomerIds)
                        ->whereIn('paymenttype', PaymentType::receiverVisibleValues());
                });
            });
    }

    public function findAuthorized(ClientAccount $clientAccount, int $orderId): ?object
    {
        return $this->forClientAccount($clientAccount)
            ->where('id', $orderId)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    public static function senderVisiblePaymentTypes(): array
    {
        return PaymentType::senderVisibleValues();
    }

    /**
     * @return array<int, string>
     */
    public static function receiverVisiblePaymentTypes(): array
    {
        return PaymentType::receiverVisibleValues();
    }
}

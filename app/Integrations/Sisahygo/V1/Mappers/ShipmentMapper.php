<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentType;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\PaginationMeta;
use App\Integrations\Sisahygo\V1\DTO\ShipmentDetail;
use App\Integrations\Sisahygo\V1\DTO\ShipmentItem;
use App\Integrations\Sisahygo\V1\DTO\ShipmentListResult;
use App\Integrations\Sisahygo\V1\DTO\ShipmentStatus;
use App\Integrations\Sisahygo\V1\DTO\ShipmentSummary;
use Carbon\CarbonImmutable;

class ShipmentMapper
{
    /** @param array<string, mixed> $data */
    public function summary(array $data): ShipmentSummary
    {
        $trackingNo = $data['tracking_no'] ?? null;

        if (! is_scalar($trackingNo) || (string) $trackingNo === '') {
            throw new SisahygoUnexpectedResponseException('Shipment response is missing tracking_no.');
        }

        return new ShipmentSummary(
            trackingNo: (string) $trackingNo,
            id: $this->nullableInt($data['id'] ?? null),
            clientReferenceNo: $this->nullableString($data['client_reference_no'] ?? null),
            orderHeaderNo: $this->nullableString($data['order_header_no'] ?? null),
            orderHeaderDate: $this->date($data['order_header_date'] ?? null),
            orderStatus: $this->nullableString($data['order_status'] ?? null),
            orderType: $this->nullableString($data['order_type'] ?? null),
            orderAmount: $this->money($data['order_amount'] ?? null),
            paymentType: is_string($data['paymenttype'] ?? null) ? PaymentType::tryFrom($data['paymenttype']) : null,
            paymentStatus: is_numeric($data['payment_status'] ?? null) ? PaymentStatus::tryFrom((int) $data['payment_status']) : null,
            senderCustomerId: $this->nullableInt($data['customer_id'] ?? data_get($data, 'sender.customer_id')),
            receiverCustomerId: $this->nullableInt($data['customer_rec_id'] ?? data_get($data, 'receiver.customer_id') ?? data_get($data, 'customer_rec.customer_id')),
            branchName: $this->nullableString($data['branch'] ?? data_get($data, 'branch.name')),
            destinationBranchName: $this->nullableString($data['branch_rec'] ?? data_get($data, 'branch_rec.name') ?? data_get($data, 'to_branch.name')),
            senderName: $this->nullableString($data['customer'] ?? data_get($data, 'sender.name')),
            receiverName: $this->nullableString($data['customer_rec'] ?? data_get($data, 'receiver.name') ?? data_get($data, 'customer_rec.name')),
        );
    }

    /** @param array<string, mixed> $data */
    public function detail(array $data): ShipmentDetail
    {
        return new ShipmentDetail(
            summary: $this->summary($data),
            items: array_map(fn (array $item) => $this->item($item), is_array($data['items'] ?? null) ? $data['items'] : []),
            statusHistory: array_map(fn (array $status) => $this->status($status), $this->statusHistory($data)),
            remark: $this->nullableString($data['remark'] ?? null),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>|null  $meta
     */
    public function listResult(array $items, ?array $meta = null): ShipmentListResult
    {
        return new ShipmentListResult($this->summaryList($items), $this->pagination($meta));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ShipmentSummary>
     */
    public function summaryList(array $items): array
    {
        $mapped = array_map(fn (array $item) => $this->summary($item), $items);
        $unique = [];

        foreach ($mapped as $shipment) {
            $unique[$shipment->trackingNo] = $shipment;
        }

        return array_values($unique);
    }

    /** @param array<string, mixed> $data */
    private function item(array $data): ShipmentItem
    {
        return new ShipmentItem(
            id: $this->nullableInt($data['id'] ?? null),
            productId: $this->nullableInt($data['product_id'] ?? null),
            productName: $this->nullableString($data['product_name'] ?? $data['name'] ?? $data['item_name'] ?? null),
            unitId: $this->nullableInt($data['unit_id'] ?? null),
            unitName: $this->nullableString($data['unit'] ?? $data['unit_name'] ?? null),
            price: $this->money($data['price'] ?? null),
            amount: $this->money($data['amount'] ?? $data['quantity'] ?? null),
            lineAmount: $this->money($data['line_amount'] ?? null),
            remark: $this->nullableString($data['remark'] ?? null),
            clientLineId: $this->nullableString($data['client_line_id'] ?? null),
            clientItemNo: $this->nullableString($data['client_item_no'] ?? null),
            clientProductCode: $this->nullableString($data['client_product_code'] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    private function status(array $data): ShipmentStatus
    {
        return new ShipmentStatus(
            status: $this->nullableString($data['status'] ?? $data['order_status'] ?? null) ?? 'unknown',
            occurredAt: $this->date($data['occurred_at'] ?? $data['created_at'] ?? $data['changed_at'] ?? null),
            description: $this->nullableString($data['description'] ?? $data['remark'] ?? null),
            branchName: $this->nullableString($data['branch'] ?? data_get($data, 'branch.name') ?? null),
            actor: $this->nullableString($data['actor'] ?? $data['source'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function statusHistory(array $data): array
    {
        if (is_array($data['status_history'] ?? null)) {
            return $data['status_history'];
        }

        if (is_array($data['history'] ?? null)) {
            return $data['history'];
        }

        return [];
    }

    /** @param array<string, mixed>|null $meta */
    private function pagination(?array $meta): ?PaginationMeta
    {
        if (! $meta) {
            return null;
        }

        return new PaginationMeta(
            currentPage: max(1, (int) ($meta['current_page'] ?? 1)),
            perPage: max(1, (int) ($meta['per_page'] ?? 50)),
            total: is_numeric($meta['total'] ?? null) ? (int) $meta['total'] : null,
            lastPage: is_numeric($meta['last_page'] ?? null) ? (int) $meta['last_page'] : null,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }

    private function money(mixed $value): ?string
    {
        return is_numeric($value) || is_string($value) ? (string) $value : null;
    }
}

<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentType;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\ShipmentDetail;
use App\Integrations\Sisahygo\V1\DTO\ShipmentItem;
use App\Integrations\Sisahygo\V1\DTO\ShipmentStatus;
use App\Integrations\Sisahygo\V1\DTO\ShipmentSummary;
use Carbon\CarbonImmutable;

class ShipmentMapper
{
    /**
     * @param array<string, mixed> $data
     */
    public function summary(array $data): ShipmentSummary
    {
        $trackingNo = $data['tracking_no'] ?? null;

        if (! is_string($trackingNo) || $trackingNo === '') {
            throw new SisahygoUnexpectedResponseException('Shipment response is missing tracking_no.');
        }

        return new ShipmentSummary(
            trackingNo: $trackingNo,
            orderHeaderNo: $this->nullableString($data['order_header_no'] ?? null),
            orderHeaderDate: $this->date($data['order_header_date'] ?? null),
            orderStatus: $this->nullableString($data['order_status'] ?? null),
            orderType: $this->nullableString($data['order_type'] ?? null),
            orderAmount: $this->money($data['order_amount'] ?? null),
            paymentType: is_string($data['paymenttype'] ?? null) ? PaymentType::tryFrom($data['paymenttype']) : null,
            paymentStatus: is_numeric($data['payment_status'] ?? null) ? PaymentStatus::tryFrom((int) $data['payment_status']) : null,
            senderCustomerId: $this->nullableInt($data['customer_id'] ?? data_get($data, 'sender.customer_id')),
            receiverCustomerId: $this->nullableInt($data['customer_rec_id'] ?? data_get($data, 'receiver.customer_id') ?? data_get($data, 'customer_rec.customer_id')),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function detail(array $data): ShipmentDetail
    {
        return new ShipmentDetail(
            summary: $this->summary($data),
            items: array_map(fn (array $item) => $this->item($item), is_array($data['items'] ?? null) ? $data['items'] : []),
            statusHistory: array_map(fn (array $status) => $this->status($status), is_array($data['status_history'] ?? null) ? $data['status_history'] : []),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
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
            name: $this->nullableString($data['name'] ?? $data['item_name'] ?? null) ?? 'Unknown item',
            quantity: max(0, (int) ($data['quantity'] ?? 0)),
            amount: $this->money($data['amount'] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    private function status(array $data): ShipmentStatus
    {
        return new ShipmentStatus(
            status: $this->nullableString($data['status'] ?? $data['order_status'] ?? null) ?? 'unknown',
            occurredAt: $this->date($data['occurred_at'] ?? $data['created_at'] ?? null),
            description: $this->nullableString($data['description'] ?? null),
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
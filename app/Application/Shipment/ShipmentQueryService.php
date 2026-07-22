<?php

namespace App\Application\Shipment;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\DTO\PaginationMeta;
use App\Integrations\Sisahygo\V1\DTO\ShipmentDetail;
use App\Integrations\Sisahygo\V1\DTO\ShipmentItem;
use App\Integrations\Sisahygo\V1\DTO\ShipmentStatus;
use App\Integrations\Sisahygo\V1\DTO\ShipmentSummary;
use App\Integrations\Sisahygo\V1\Endpoints\ShipmentsEndpoint;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ShipmentQueryService
{
    public function __construct(
        private readonly SisahygoIntegrationContextBuilder $contextBuilder,
        private readonly ShipmentsEndpoint $shipments,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>|null, filters: array<string, mixed>}
     */
    public function list(User $user, ClientAccount $clientAccount, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $result = $this->shipments->list($this->context($user, $clientAccount), $normalized);

        return [
            'items' => array_map(fn (ShipmentSummary $shipment): array => $this->summaryToArray($shipment), $result->items),
            'meta' => $this->metaToArray($result->meta),
            'filters' => $normalized,
        ];
    }

    /** @return array<string, mixed> */
    public function detail(User $user, ClientAccount $clientAccount, string $trackingNo): array
    {
        Validator::make(['tracking_no' => $trackingNo], [
            'tracking_no' => ['required', 'string', 'max:100'],
        ])->validate();

        return $this->detailToArray(
            $this->shipments->detail($this->context($user, $clientAccount), trim($trackingNo))
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $filters): array
    {
        $data = Validator::make($filters, [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:50'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'tracking_no' => ['nullable', 'string', 'max:100'],
            'order_header_no' => ['nullable', 'string', 'max:100'],
            'client_reference_no' => ['nullable', 'string', 'max:100'],
            'batch_reference_no' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'date_to.after_or_equal' => 'วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มต้น',
        ])->validate();

        $normalized = [
            'from_date' => $data['date_from'] ?? null,
            'to_date' => $data['date_to'] ?? null,
            'order_status' => $data['status'] ?? null,
            'tracking_no' => $data['tracking_no'] ?? null,
            'order_header_no' => $data['order_header_no'] ?? null,
            'client_reference_no' => $data['client_reference_no'] ?? null,
            'batch_reference_no' => $data['batch_reference_no'] ?? null,
            'page' => isset($data['page']) ? (int) $data['page'] : 1,
            'per_page' => isset($data['per_page']) ? (int) $data['per_page'] : 15,
        ];

        $keyword = trim((string) ($data['keyword'] ?? ''));

        if ($keyword !== '') {
            if (ctype_digit($keyword)) {
                $normalized['tracking_no'] = $keyword;
            } else {
                $normalized['order_header_no'] = $keyword;
            }
        }

        return array_filter($normalized, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function context(User $user, ClientAccount $clientAccount): SisahygoIntegrationContext
    {
        return $this->contextBuilder->build($user, $clientAccount, ClientCapability::ShipmentView);
    }

    /** @return array<string, mixed> */
    private function summaryToArray(ShipmentSummary $shipment): array
    {
        return [
            'tracking_no' => $shipment->trackingNo,
            'id' => $shipment->id,
            'client_reference_no' => $shipment->clientReferenceNo,
            'batch_reference_no' => $shipment->batchReferenceNo,
            'order_header_no' => $shipment->orderHeaderNo,
            'order_header_date' => $shipment->orderHeaderDate?->format('Y-m-d'),
            'order_status' => $shipment->orderStatus,
            'order_status_label' => ShipmentStatusLabels::label($shipment->orderStatus),
            'order_status_variant' => ShipmentStatusLabels::variant($shipment->orderStatus),
            'order_type' => $shipment->orderType,
            'order_amount' => $shipment->orderAmount,
            'order_amount_display' => $this->moneyDisplay($shipment->orderAmount),
            'payment_type' => $shipment->paymentType?->value,
            'payment_status' => $shipment->paymentStatus?->value,
            'sender_customer_id' => $shipment->senderCustomerId,
            'receiver_customer_id' => $shipment->receiverCustomerId,
            'branch_name' => $shipment->branchName,
            'destination_branch_name' => $shipment->destinationBranchName,
            'sender_name' => $shipment->senderName,
            'receiver_name' => $shipment->receiverName,
            'items' => array_map(fn (ShipmentItem $item): array => $this->itemToArray($item), $shipment->items),
        ];
    }

    /** @return array<string, mixed> */
    private function detailToArray(ShipmentDetail $detail): array
    {
        return [
            'summary' => $this->summaryToArray($detail->summary),
            'remark' => $detail->remark,
            'items' => array_map(fn (ShipmentItem $item): array => $this->itemToArray($item), $detail->items),
            'timeline' => array_map(fn (ShipmentStatus $status): array => [
                'status' => $status->status,
                'label' => ShipmentStatusLabels::label($status->status),
                'variant' => ShipmentStatusLabels::variant($status->status),
                'occurred_at' => $status->occurredAt?->toIso8601String(),
                'occurred_at_display' => $status->occurredAt?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'description' => $status->description,
                'branch_name' => $status->branchName,
                'actor' => $status->actor,
            ], $detail->statusHistory),
        ];
    }

    /** @return array<string, mixed> */
    private function itemToArray(ShipmentItem $item): array
    {
        return [
            'id' => $item->id,
            'product_id' => $item->productId,
            'product_name' => $item->productName,
            'unit_id' => $item->unitId,
            'unit_name' => $item->unitName,
            'price' => $item->price,
            'amount' => $item->amount,
            'line_amount' => $item->lineAmount,
            'remark' => $item->remark,
            'client_line_id' => $item->clientLineId,
            'client_item_no' => $item->clientItemNo,
            'client_product_code' => $item->clientProductCode,
        ];
    }

    private function moneyDisplay(?string $amount): string
    {
        return is_numeric($amount) ? number_format((float) $amount, 2) : '-';
    }

    /** @return array<string, mixed>|null */
    private function metaToArray(?PaginationMeta $meta): ?array
    {
        if (! $meta) {
            return null;
        }

        return [
            'current_page' => $meta->currentPage,
            'per_page' => $meta->perPage,
            'total' => $meta->total,
            'last_page' => $meta->lastPage,
        ];
    }
}

<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\OrderCheckingResult;

class OrderCheckingMapper
{
    /** @param array<string, mixed> $data */
    public function map(array $data): OrderCheckingResult
    {
        if (! array_key_exists('client_reference_no', $data) && ! array_key_exists('id', $data)) {
            throw new SisahygoUnexpectedResponseException('Order checking response is missing expected fields.');
        }

        return new OrderCheckingResult(
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : null,
            orderHeaderNo: is_string($data['order_header_no'] ?? null) ? $data['order_header_no'] : null,
            trackingNo: is_string($data['tracking_no'] ?? null) ? $data['tracking_no'] : null,
            orderStatus: is_string($data['order_status'] ?? null) ? $data['order_status'] : null,
            clientReferenceNo: is_string($data['client_reference_no'] ?? null) ? $data['client_reference_no'] : null,
            receiverCustomerId: is_numeric($data['customer_rec_id'] ?? null) ? (int) $data['customer_rec_id'] : null,
            receiverName: is_string($data['to_customer_name'] ?? null) ? $data['to_customer_name'] : null,
            itemsCount: is_numeric($data['items_count'] ?? null) ? (int) $data['items_count'] : null,
            submittedAt: is_string($data['api_submitted_at'] ?? null) ? $data['api_submitted_at'] : null,
        );
    }
}

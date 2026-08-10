<?php

namespace App\Application\Reports;

class ReportDefinitions
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'shipments' => [
                'title' => __('reports.shipments.title'),
                'description' => __('reports.shipments.description'),
                'route' => 'reports.shipments',
                'summary' => ['total_shipments', 'in_progress', 'delivered', 'pending_or_problem', 'total_freight_amount'],
                'columns' => ['order_date', 'order_number', 'tracking_identifier', 'relationship', 'sender_name', 'receiver_name', 'current_status', 'item_count', 'freight_amount', 'latest_status_time'],
                'file' => 'sisahygo-shipment-report',
            ],
            'shipment-status' => [
                'title' => __('reports.shipment_status.title'),
                'description' => __('reports.shipment_status.description'),
                'route' => 'reports.shipment-status',
                'summary' => ['total_shipments', 'waiting', 'in_transit', 'arrival', 'delivered', 'cancelled', 'problem', 'average_processing_time', 'oldest_pending_shipment'],
                'columns' => ['shipment_date', 'tracking_number', 'order_number', 'sender', 'receiver', 'relationship', 'current_status', 'current_branch', 'last_update', 'processing_time', 'delayed'],
                'timeline_columns' => ['tracking_number', 'timeline_status', 'timeline_date', 'timeline_time', 'timeline_user', 'timeline_remark'],
                'file' => 'sisahygo-shipment-status-report',
            ],
            'receivers' => [
                'title' => __('reports.receivers.title'),
                'description' => __('reports.receivers.description'),
                'route' => 'reports.receivers',
                'summary' => ['total_shipments', 'unique_receivers', 'total_quantity', 'total_freight_amount', 'top_receiver', 'top_destination_province'],
                'columns' => ['receiver', 'province', 'district', 'sub_district', 'shipment_count', 'total_quantity', 'freight_amount', 'average_freight_per_shipment', 'last_shipment_date'],
                'area_columns' => ['province', 'district', 'sub_district', 'shipment_count', 'unique_receivers', 'total_quantity', 'freight_amount'],
                'file' => 'sisahygo-receiver-area-report',
            ],
            'products' => [
                'title' => __('reports.products.title'),
                'description' => __('reports.products.description'),
                'route' => 'reports.products',
                'summary' => ['total_shipments', 'total_product_lines', 'total_quantity', 'unique_products', 'total_freight_amount', 'top_product_by_quantity'],
                'columns' => ['product', 'unit', 'shipment_count', 'quantity', 'receiver_count', 'freight_amount', 'average_quantity_per_shipment', 'last_shipment_date'],
                'detail_columns' => ['shipment_date', 'order_number', 'receiver', 'product', 'unit', 'quantity', 'unit_price', 'freight_amount', 'client_item_no'],
                'file' => 'sisahygo-product-volume-report',
            ],
            'order-checkings' => [
                'title' => __('reports.order_checkings.title'),
                'description' => __('reports.order_checkings.description'),
                'route' => 'reports.order-checkings',
                'summary' => ['total_orders', 'single_orders', 'bulk_orders', 'checking', 'confirmed_or_new', 'rejected_or_cancelled', 'unresolved_price_orders'],
                'columns' => ['submitted_at', 'submission_type', 'client_reference', 'batch_reference', 'order_number', 'receiver', 'item_count', 'order_status', 'freight_amount', 'pricing_status', 'submitted_by'],
                'file' => 'sisahygo-order-checking-report',
            ],
            'payments' => [
                'title' => __('reports.payments.title'),
                'description' => __('reports.payments.description'),
                'route' => 'reports.payments',
                'summary' => ['total_freight_amount', 'total_paid_amount', 'total_balance_amount', 'paid_count', 'unpaid_count'],
                'columns' => ['transaction_date', 'order_number', 'relationship', 'payment_type_label', 'payer', 'freight_amount', 'paid_amount', 'balance_amount', 'payment_status_label', 'payment_date'],
                'file' => 'sisahygo-payment-report',
            ],
        ];
    }
}

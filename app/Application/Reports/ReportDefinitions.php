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

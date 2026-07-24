<?php

return [
    'type' => [
        'H' => 'Cash at Origin',
        'T' => 'Transfer at Origin',
        'E' => 'Collect at Destination',
        'F' => 'Origin Billing',
        'L' => 'Destination Billing',
    ],
    'status' => [
        'outstanding' => 'Outstanding',
        'paid' => 'Paid',
    ],
    'center' => ['eyebrow' => 'Payment Center', 'title' => 'Payment Center', 'description' => 'Review origin billing, destination billing, and collect-at-destination records from Sisahygo Core.', 'subtitle' => 'Includes only F/L/E records visible to the current Client Account.', 'refreshed_at' => 'Last updated: :time'],
    'detail' => ['title' => 'Payment Detail', 'description' => 'This data comes from the Core Client Payment API.', 'order_title' => 'Record Information', 'parties_title' => 'Sender and Receiver', 'payment_title' => 'Payment Information', 'references_title' => 'References', 'invoice_title' => 'Invoice', 'receipt_title' => 'Receipt', 'not_found_title' => 'Payment not found', 'not_found_description' => 'This payment may not exist or may be outside the selected Client Account.'],
    'summary' => ['label' => 'Payment summary', 'record_count' => 'Records', 'total_amount' => 'Total Amount', 'paid_record_count' => 'Paid Records', 'outstanding_record_count' => 'Outstanding Records'],
    'filters' => ['title' => 'Payment Filters', 'description' => 'Search only with filters supported by Core.', 'payment_type' => 'Payment Type', 'payment_status' => 'Status', 'date_from' => 'Billing Date From', 'date_to' => 'To Date', 'order_header_no' => 'Order No.', 'client_reference_no' => 'Client Reference No.', 'all_types' => 'All Types', 'all_statuses' => 'All Statuses', 'active' => 'Active filters', 'clear_chip' => 'Clear this filter', 'chip_type' => 'Type: :value', 'chip_status' => 'Status: :value', 'chip_date' => 'Date: :value', 'chip_order' => 'Order: :value', 'chip_reference' => 'Client reference: :value'],
    'list' => ['results_title' => 'Payment Records'],
    'fields' => ['payment_identifier' => 'Payment ID', 'order_header_no' => 'Order No.', 'order_header_date' => 'Order Date', 'client_reference_no' => 'Client Reference No.', 'tracking_reference' => 'Tracking/Waybill', 'billing_date' => 'Billing Date', 'payment_date' => 'Payment Date', 'type' => 'Type', 'payer' => 'Payer', 'parties' => 'Sender / Receiver', 'sender' => 'Sender', 'receiver' => 'Receiver', 'status' => 'Status', 'total_amount' => 'Total Amount', 'paid_amount' => 'Paid Amount', 'outstanding_amount' => 'Outstanding Amount', 'discount_amount' => 'Discount', 'tax_amount' => 'Tax', 'invoice_number' => 'Invoice No.', 'invoice_date' => 'Invoice Date', 'receipt_number' => 'Receipt No.', 'receipt_date' => 'Receipt Date'],
    'payer_role' => ['sender' => 'Sender', 'receiver' => 'Receiver'],
    'actions' => ['search' => 'Search', 'clear' => 'Clear Filters', 'refresh' => 'Refresh Data', 'refreshing' => 'Refreshing', 'retry' => 'Retry', 'view_detail' => 'View Detail', 'back_to_list' => 'Back to Payment Center', 'open_filtered' => 'Open records', 'create_first' => 'Create first shipment'],
    'empty' => ['title' => 'No payment records yet', 'description' => 'Payment status will appear here after shipments and billing data are available. Create a first shipment or adjust the filters.'],
    'unavailable' => ['title' => 'Payment data unavailable'],
    'errors' => ['authentication' => 'Unable to authenticate with Sisahygo Core. Check API settings.', 'authorization' => 'This Client Account cannot view payment data.', 'connection' => 'Sisahygo Core is temporarily unreachable. Please retry.', 'not_found' => 'The requested payment was not found.', 'validation' => 'The payment filters are invalid.', 'rate_limited' => 'Too many requests. Please wait and retry.', 'server' => 'Sisahygo Core is temporarily unavailable. Please retry.', 'unexpected' => 'Unable to load payment data. Please retry.', 'no_credential' => 'No API credential is configured for this Client Account.'],
    'validation' => ['date_to_after_or_equal' => 'End date must be on or after start date.', 'payment_status' => 'Unsupported payment status.', 'payment_type' => 'Unsupported payment type.', 'payment_identifier' => 'Invalid payment identifier format.'],
    'pagination' => ['page' => 'Page :page', 'last_page' => 'of :last pages', 'total' => ':total total records', 'per_page' => 'Per page', 'previous' => 'Previous', 'next' => 'Next'],
    'loading' => 'Loading payment data',
    'account' => ['label' => 'Payment Center Client Account', 'current' => 'Current Client Account'],
    'fallback' => ['empty' => '—', 'unknown' => 'Unknown'],
];

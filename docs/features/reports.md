# Reports

Reports now provides four active reports:

- รายงานสรุปการจัดส่งสินค้า / Shipment Summary Report
- รายงานสถานะและระยะเวลาการขนส่ง / Shipment Status & Timeline Report
- รายงานรายการที่สร้างผ่าน Sisahygo Connect / Connect Order Checking Report
- รายงานค่าขนส่งและสถานะการชำระเงิน / Freight and Payment Report

The /reports page requires report.view. Each report page supports filters, summary values, paginated details, and Excel export when the account also has report.export.

Connect does not access the Core database. It sends the selected filters to Core report endpoints and renders the returned summary, rows, timeline data, and pagination. Excel exports reuse the same criteria with export=1; exports are limited to 5,000 rows and 366 days.

## Files

- Shipment: sisahygo-shipment-report-YYYYMMDD-YYYYMMDD.xlsx
- Shipment Status & Timeline: sisahygo-shipment-status-report-YYYYMMDD-YYYYMMDD.xlsx
- Order Checking: `sisahygo-order-checking-report-YYYYMMDD-YYYYMMDD.xlsx`
- Freight and Payment: `sisahygo-payment-report-YYYYMMDD-YYYYMMDD.xlsx`

Workbooks contain a Summary sheet and a detail sheet. Order Checking exports use an Order Items sheet as the detail dataset.

## Shipment Status & Timeline

The Shipment Status & Timeline report uses Core endpoint GET /api/v1/client/reports/shipment-status. It reuses sender, receiver, and both customer visibility, report.view for page access, and report.export for Excel downloads.

Filters include date_from, date_to, relationship, status, search, only_delayed, and only_in_progress. Delayed detection is calculated in Sisahygo from existing shipment status timestamps and the configurable delayed threshold. A shipment is delayed only when it is still active, including delivery/problem states, and elapsed processing time exceeds the configured threshold. Completed and cancelled shipments are not delayed.

Each row can expand to show timeline events from existing shipment status history: Checking, New, Loaded, In Transit, Arrival, Branch Warehouse, Delivery, Completed, and Cancelled with date, time, user, and remark when available. Delivery is active/in-progress; only Completed is counted as delivered/completed. Cancelled shipments remain visible as historical shipment activity.

The export contains Summary, Shipment Status, and Timeline sheets. The Timeline sheet columns are Tracking, Status, Date, Time, User, and Remark.

# Reports

Reports Phase 1 provides three active reports:

- รายงานสรุปการจัดส่งสินค้า / Shipment Summary Report
- รายงานรายการที่สร้างผ่าน Sisahygo Connect / Connect Order Checking Report
- รายงานค่าขนส่งและสถานะการชำระเงิน / Freight and Payment Report

The `/reports` page requires `report.view`. Each report page supports filters, summary values, paginated details, and Excel export when the account also has `report.export`.

Connect does not access the Core database. It sends the selected filters to Core report endpoints and renders the returned summary, rows, and pagination. Excel exports reuse the same criteria with `export=1`; exports are limited to 5,000 rows and 366 days.

## Files

- Shipment: `sisahygo-shipment-report-YYYYMMDD-YYYYMMDD.xlsx`
- Order Checking: `sisahygo-order-checking-report-YYYYMMDD-YYYYMMDD.xlsx`
- Freight and Payment: `sisahygo-payment-report-YYYYMMDD-YYYYMMDD.xlsx`

Workbooks contain a Summary sheet and a detail sheet. Order Checking exports use an Order Items sheet as the detail dataset.

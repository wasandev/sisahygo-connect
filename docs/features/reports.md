# Reports

Reports now provides six active reports:

- รายงานสรุปการจัดส่งสินค้า / Shipment Summary Report
- รายงานสถานะและระยะเวลาการขนส่ง / Shipment Status & Timeline Report
- รายงานผู้รับและพื้นที่จัดส่ง / Receiver & Delivery Area Report
- รายงานสินค้าและปริมาณการขนส่ง / Product & Shipment Volume Report
- รายงานรายการที่สร้างผ่าน Sisahygo Connect / Connect Order Checking Report
- รายงานค่าขนส่งและสถานะการชำระเงิน / Freight and Payment Report

The /reports page requires report.view. Each report page supports filters, summary values, paginated details, and Excel export when the account also has report.export.

Connect does not access the Core database. It sends the selected filters to Core report endpoints and renders the returned summary, rows, timeline data, and pagination. Excel exports reuse the same criteria with export=1; exports are limited to 5,000 rows and 366 days.

## Files

- Shipment: sisahygo-shipment-report-YYYYMMDD-YYYYMMDD.xlsx
- Shipment Status & Timeline: sisahygo-shipment-status-report-YYYYMMDD-YYYYMMDD.xlsx
- Receiver & Delivery Area: `sisahygo-receiver-area-report-YYYYMMDD-YYYYMMDD.xlsx`
- Product & Shipment Volume: `sisahygo-product-volume-report-YYYYMMDD-YYYYMMDD.xlsx`
- Order Checking: `sisahygo-order-checking-report-YYYYMMDD-YYYYMMDD.xlsx`
- Freight and Payment: `sisahygo-payment-report-YYYYMMDD-YYYYMMDD.xlsx`

Workbooks contain a Summary sheet and a detail sheet. Receiver exports add a Delivery Areas sheet. Product exports add a Product Details audit sheet. Order Checking exports use an Order Items sheet as the detail dataset.

## Shipment Status & Timeline

The Shipment Status & Timeline report uses Core endpoint GET /api/v1/client/reports/shipment-status. It reuses sender, receiver, and both customer visibility, report.view for page access, and report.export for Excel downloads.

Filters include date_from, date_to, relationship, status, search, only_delayed, and only_in_progress. Delayed detection is calculated in Sisahygo from existing shipment status timestamps and the configurable delayed threshold. A shipment is delayed only when it is still active, including delivery/problem states, and elapsed processing time exceeds the configured threshold. Completed and cancelled shipments are not delayed.

Each row can expand to show timeline events from existing shipment status history: Checking, New, Loaded, In Transit, Arrival, Branch Warehouse, Delivery, Completed, and Cancelled with date, time, user, and remark when available. Delivery is active/in-progress; only Completed is counted as delivered/completed. Cancelled shipments remain visible as historical shipment activity.

The export contains Summary, Shipment Status, and Timeline sheets. The Timeline sheet columns are Tracking, Status, Date, Time, User, and Remark.


## Receiver & Delivery Area

The Receiver & Delivery Area report uses Core endpoint GET /api/v1/client/reports/receivers. It reuses sender, receiver, and both shipment visibility from the existing Client Account scope. Filters include date_from, date_to, relationship, search, province, district, and sub_district. Browser-supplied customer IDs are not accepted for authorization.

Rows aggregate by receiver and structured destination area from the receiver customer record. Summary values include total shipments, unique receivers, total quantity from order detail amounts, total freight amount from order headers, top receiver by shipment count, and top destination province. Phone and address fields are intentionally not exposed.

The export contains Summary, Receivers, and Delivery Areas sheets. Delivery Areas aggregates province, district, sub-district, shipment count, unique receivers, total quantity, and freight amount using the same filters as the HTML report.

## Product & Shipment Volume

The Product & Shipment Volume report uses Core endpoint GET /api/v1/client/reports/products. It reuses the same Client Account shipment visibility and supports date_from, date_to, relationship, search, product, and unit filters. Product and unit filters match stable product/unit records by name or identifier where Core safely supports it, while the UI displays names.

Rows aggregate by Product + Unit. Shipment count is distinct by order so repeated product lines in one shipment do not inflate the shipment count. Quantity uses order detail amount. Receiver count is distinct receiver count. Freight amount uses detail-level persisted values only: order_details.price multiplied by order_details.amount. Header freight is not allocated across product lines.

The export contains Summary, Products, and Product Details sheets. Product Details contains authorized source detail rows within the existing export limit for audit.

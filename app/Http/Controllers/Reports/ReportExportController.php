<?php

namespace App\Http\Controllers\Reports;

use App\Application\Reports\ReportDefinitions;
use App\Application\Reports\ReportQueryService;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Http\Controllers\Controller;
use App\Support\Excel\SimpleXlsxWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, string $report, CurrentClientAccountResolver $accounts, ReportQueryService $service, SimpleXlsxWorkbook $xlsx): BinaryFileResponse
    {
        $definitions = ReportDefinitions::all();
        abort_unless(isset($definitions[$report]), 404);
        $account = $accounts->resolveForUser($request->user());
        abort_unless($account, 403);
        Gate::authorize('report.export', $account);

        $filters = $request->query();
        $summaryResult = $service->fetch($request->user(), $account, $report, $filters, true);
        $definition = $definitions[$report];
        $columns = $definition['columns'];
        $summaryRows = [
            [__('reports.export.field'), __('reports.export.value')],
            [__('reports.export.title'), $definition['title']],
            [__('reports.export.account'), $account->name.' / '.$account->code],
            [__('reports.export.period'), ($summaryResult['meta']['filters']['date_from'] ?? '').' - '.($summaryResult['meta']['filters']['date_to'] ?? '')],
            [__('reports.export.generated_at'), now(config('app.timezone'))->format('Y-m-d H:i:s')],
            [__('reports.export.generated_by'), $request->user()->name],
        ];
        foreach (($summaryResult['data']['summary'] ?? []) as $key => $value) {
            $summaryRows[] = [__('reports.fields.'.$key), $value];
        }
        $detailRows = [array_map(fn ($key) => __('reports.fields.'.$key), $columns)];
        foreach ($summaryResult['data']['rows'] as $row) {
            $detailRows[] = array_map(fn ($key) => $this->displayValue($row, $key), $columns);
        }

        $sheets = [['title' => __('reports.export.summary_sheet'), 'rows' => $summaryRows]];
        if ($report === 'shipment-status') {
            $timelineColumns = $definition['timeline_columns'];
            $timelineRows = [array_map(fn ($key) => __('reports.fields.'.$key), $timelineColumns)];
            foreach ($summaryResult['data']['rows'] as $row) {
                foreach (($row['timeline'] ?? []) as $event) {
                    $timelineRows[] = [
                        data_get($row, 'tracking_number', ''),
                        $this->statusLabel(data_get($event, 'status')),
                        data_get($event, 'date', ''),
                        data_get($event, 'time', ''),
                        data_get($event, 'user', ''),
                        data_get($event, 'remark', ''),
                    ];
                }
            }
            $sheets[] = ['title' => __('reports.export.shipment_status_sheet'), 'rows' => $detailRows];
            $sheets[] = ['title' => __('reports.export.timeline_sheet'), 'rows' => $timelineRows];
        } elseif ($report === 'receivers') {
            $sheets[] = ['title' => __('reports.export.receivers_sheet'), 'rows' => $detailRows];
            $areaColumns = $definition['area_columns'];
            $areaRows = [array_map(fn ($key) => __('reports.fields.'.$key), $areaColumns)];
            foreach (($summaryResult['data']['area_rows'] ?? []) as $row) $areaRows[] = array_map(fn ($key) => $this->displayValue($row, $key), $areaColumns);
            $sheets[] = ['title' => __('reports.export.delivery_areas_sheet'), 'rows' => $areaRows];
        } elseif ($report === 'products') {
            $sheets[] = ['title' => __('reports.export.products_sheet'), 'rows' => $detailRows];
            $detailColumns = $definition['detail_columns'];
            $productRows = [array_map(fn ($key) => __('reports.fields.'.$key), $detailColumns)];
            foreach (($summaryResult['data']['product_details'] ?? []) as $row) $productRows[] = array_map(fn ($key) => $this->displayValue($row, $key), $detailColumns);
            $sheets[] = ['title' => __('reports.export.product_details_sheet'), 'rows' => $productRows];
        } elseif ($report === 'order-checkings') {
            $items = $service->fetch($request->user(), $account, 'order-checking-items', $filters, true);
            $itemColumns = ['client_reference', 'batch_reference', 'order_number', 'product', 'unit', 'quantity', 'unit_price', 'line_amount', 'pricing_status', 'item_remark', 'client_item_no'];
            $itemRows = [array_map(fn ($key) => __('reports.fields.'.$key), $itemColumns)];
            foreach ($items['data']['rows'] as $row) $itemRows[] = array_map(fn ($key) => $this->displayValue($row, $key), $itemColumns);
            $sheets[] = ['title' => __('reports.export.order_items_sheet'), 'rows' => $itemRows];
        } else {
            $sheets[] = ['title' => $report === 'payments' ? __('reports.export.payment_details_sheet') : __('reports.export.shipment_details_sheet'), 'rows' => $detailRows];
        }

        $path = tempnam(sys_get_temp_dir(), 'sisahygo-report-').'.xlsx';
        $xlsx->save($path, $sheets);
        $filename = $definition['file'].'-'.str_replace('-', '', $summaryResult['meta']['filters']['date_from']).'-'.str_replace('-', '', $summaryResult['meta']['filters']['date_to']).'.xlsx';

        return response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function displayValue(array $row, string $key): string
    {
        $value = data_get($row, $key, '');

        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return __('reports.values.boolean.'.($value ? 'true' : 'false'));
        }

        if ($key === 'current_status' || $key === 'order_status') {
            return $this->statusLabel($value);
        }

        $translationKey = 'reports.values.'.$key.'.'.$value;
        $translation = __($translationKey);

        return $translation === $translationKey ? (string) $value : (string) $translation;
    }

    private function statusLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $translationKey = 'reports.values.current_status.'.$value;
        $translation = __($translationKey);

        return $translation === $translationKey ? (string) $value : (string) $translation;
    }
}

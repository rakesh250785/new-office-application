<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\AuthorisedSummaryExport;
use App\Exports\BranchSummaryExport;
use App\Exports\CategorySummaryExport;
use App\Exports\CustomerSummaryExport;
use App\Exports\PrincipalSummaryExport;
use App\Exports\SaleReportExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\EnqueueSaleDataImport;
use App\Models\ImportJob;
use App\Models\SaleReport;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class PerformanceSummaryController extends Controller
{
    public function getSaleReport(Request $request)
    {
        try {
            $data = $request->validate([
                'page' => ['nullable', 'integer', 'min:1'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'sort_by' => [
                    'nullable',
                    Rule::in([
                        'invoice_date',
                        'invoice',
                        'customer_name',
                        'branch',
                        'principal_name',
                        'category',
                        'amount',
                        'qty',
                        'fy_year',
                        'month',
                    ]),
                ],
                'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],

                'q' => ['nullable', 'string', 'max:100'],
                'month' => ['nullable', 'string', 'max:16'],
                'branch' => ['nullable', 'string', 'max:128'],
                'principal' => ['nullable', 'string', 'max:128'],
                'category' => ['nullable', 'string', 'max:128'],
                'authorised' => ['nullable', 'string', 'max:64'],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date'],
            ]);

            $perPage = (int) ($data['per_page'] ?? 15);
            $sortBy = $data['sort_by'] ?? 'invoice_date';
            $sortDir = $data['sort_dir'] ?? 'desc';
            $branchId = $request->input('branch_list');
            $query = SaleReport::query();
            if (! empty($data['q'])) {
                $q = $data['q'];

                $query->where(function ($qq) use ($q) {
                    $qq->where('invoice', 'like', "%{$q}%")
                        ->orWhere('order_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('branch', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('part_no', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%")
                        ->orWhere('principal_name', 'like', "%{$q}%")
                        ->orWhere('authorised', 'like', "%{$q}%")
                        ->orWhere('month', 'like', "%{$q}%")
                        ->orWhere('fy_year', 'like', "%{$q}%")
                        ->orWhere('qtr', 'like', "%{$q}%")
                        ->orWhere('amount', 'like', "%{$q}%")
                        ->orWhere('invoice_date', 'like', "%{$q}%")
                        ->orWhere('created_at', 'like', "%{$q}%")
                        ->orWhere('updated_at', 'like', "%{$q}%");
                });
            }

            $query->when($data['fy_year'] ?? null, fn ($q, $v) => $q->where('fy_year', $v))
                ->when($data['month'] ?? null, fn ($q, $v) => $q->where('month', $v))
                ->when($data['branch'] ?? null, fn ($q, $v) => $q->where('branch', $v))
                ->when($data['principal'] ?? null, fn ($q, $v) => $q->where('principal_name', $v))
                ->when($data['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
                ->when($data['authorised'] ?? null, fn ($q, $v) => $q->where('authorised', $v));

            if (! empty($data['date_from'])) {
                $query->whereDate('invoice_date', '>=', $data['date_from']);
            }
            if (! empty($data['date_to'])) {
                $query->whereDate('invoice_date', '<=', $data['date_to']);
            }

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if (
                Utility::checkViewPermission('financial_report') ||
                Utility::checkBranchesViewPermission('financial_report')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('financial_report')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('financial_report')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }

                });
            }

            $query->orderBy($sortBy, $sortDir)
                ->orderBy('id', 'desc');

            $records = $query->paginate($perPage);

            return Utility::apiSuccess('Sale report list', $records, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error quotationStatusReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function importSaleData(Request $request)
    {
        try {
            // --- validation (simple & fast) ---
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 211);
            }

            $uploaded = $request->file('file');
            if (! $uploaded || ! $uploaded->isValid()) {
                return Utility::apiError('Invalid uploaded file', [], 211);
            }

            // store safely inside storage (no public webroot)
            $storeDir = storage_path('app/imports');
            if (! is_dir($storeDir)) {
                mkdir($storeDir, 0755, true);
            }

            $filename = 'sale_data_upload_'.time().'_'.Str::random(8).'.'.$uploaded->getClientOriginalExtension();
            // use move to avoid extra memory/copies
            $moved = $uploaded->move($storeDir, $filename);
            $fullPath = $moved->getPathname();

            if (! file_exists($fullPath)) {
                return Utility::apiError('Failed to save file to storage', [], 500);
            }

            // --- Header-only detection. Do NOT pre-count rows here. ---
            $headers = null;

            if (class_exists(\Box\Spout\Reader\Common\Creator\ReaderEntityFactory::class)) {
                try {
                    $reader = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createReaderForFile($fullPath);
                    $reader->open($fullPath);

                    // read only the first non-empty row as header, then stop
                    foreach ($reader->getSheetIterator() as $sheet) {
                        foreach ($sheet->getRowIterator() as $row) {
                            $cells = $row->toArray();
                            $nonEmpty = array_filter($cells, fn ($c) => ! is_null($c) && trim((string) $c) !== '');
                            if (empty($nonEmpty)) {
                                continue;
                            }
                            $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $cells);
                            break 2; // header found — exit both loops
                        }
                    }
                    $reader->close();
                } catch (Throwable $e) {
                    Log::warning('Spout header read failed: '.$e->getMessage());
                    $headers = null;
                }
            }

            // --- Fall back to PhpSpreadsheet header-only read if Spout absent or failed ---
            if ($headers === null) {
                try {
                    $phpReader = IOFactory::createReaderForFile($fullPath);
                    $phpReader->setReadDataOnly(true);

                    $readFilter = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
                    {
                        public function readCell($column, $row, $worksheetName = '')
                        {
                            return $row === 1; // header row only
                        }
                    };

                    $phpReader->setReadFilter($readFilter);
                    $sheetOnly = $phpReader->load($fullPath)->getActiveSheet();
                    $highestColumn = $sheetOnly->getHighestColumn();
                    $firstRow = $sheetOnly->rangeToArray("A1:{$highestColumn}1", null, true, false)[0] ?? [];
                    // free worksheet
                    $sheetOnly->getParent()->disconnectWorksheets();
                    unset($sheetOnly);

                    $headers = array_values(array_map(fn ($h) => strtolower(trim((string) $h)), $firstRow));
                } catch (Throwable $e) {
                    Log::warning('PhpSpreadsheet header read failed: '.$e->getMessage());
                    @unlink($fullPath);

                    return Utility::apiError('Uploaded file is empty or unreadable', [], 221);
                }
            }

            // validate headers (exact lowercase names expected)
            $headersNormalized = array_values(array_filter($headers, fn ($h) => $h !== ''));
            $required = [
                'qtr', 'month', 'year', 'invoice', 'invoice date', 'order no',
                'customer name', 'branch', 'description', 'part no',
                'categories', 'principal name', 'authorised', 'qty', 'amount',
            ];
            $missing = array_diff($required, $headersNormalized);
            if (! empty($missing)) {
                @unlink($fullPath);

                return Utility::apiError('Invalid file header. Missing required: '.implode(', ', $missing), 221);
            }

            // create tracking job quickly (small DB write)
            // NOTE: set total_rows to 0 so worker owns counting to avoid mismatch
            $job = ImportJob::create([
                'file_name' => $filename,
                'file_path' => Str::replaceFirst(storage_path('app').DIRECTORY_SEPARATOR, '', $fullPath),
                'status' => 'pending',
                'total_rows' => 0,
                'processed_rows' => 0,
                'file_deleted' => false,
            ]);

            // pass headers as-is; worker should stream/process rows (avoid loading here)
            EnqueueSaleDataImport::dispatch($fullPath, $job->id, $headersNormalized, null, Auth::id());

            return Utility::apiSuccess('File uploaded and queued for processing.', ['job_id' => $job->id]);
        } catch (Throwable $ex) {
            Log::error($ex);

            return Utility::apiError('Error uploading file.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function exportSaleData(Request $request)
    {
        try {
            $filters = $request->only(['q', 'date_from', 'date_to', 'branch_list']);
            $fileName = 'financial_report_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(new SaleReportExport($filters), $fileName);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error quotationStatusReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function saleImportImportStatus(Request $request)
    {

        try {
            $data = $request->only(['id']);

            if (empty($data['id'])) {
                return Utility::apiError('Priduct upload job id  not found', [], 211);
            }
            $job = ImportJob::find($data['id']);

            if (! $job) {
                return Utility::apiError('Job not found', [], 211);
            }

            return Utility::apiSuccess('File uploaded successfully. Processing started.', [
                'status' => $job->status,
                'processed_rows' => $job->processed_rows,
                'total_rows' => $job->total_rows,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error importStatus product.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function branchSummaryReport(Request $request)
    {
        try {

            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);
            $download = $request->boolean('download');

            /*
            |--------------------------------------------------------------------------
            | Default Financial Years
            |--------------------------------------------------------------------------
            */
            if (empty($years) || ! is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2).'-'.($fyStart - 1),
                    ($fyStart - 1).'-'.$fyStart,
                    ($fyStart).'-'.($fyStart + 1),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Normalize FY helper
            |--------------------------------------------------------------------------
            */
            $normalizeYearRange = function (string $y) {

                $y = trim($y);

                if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $y, $m)) {
                    $short = sprintf('%02d-%02d', $m[1] % 100, $m[2] % 100);

                    return ['label' => $short, 'variants' => [$short, "{$m[1]}-{$m[2]}"]];
                }

                if (preg_match('/^(\d{2})\s*-\s*(\d{2})$/', $y, $m)) {
                    $s2 = (int) $m[1];
                    $e2 = (int) $m[2];
                    $start4 = 2000 + $s2;
                    $end4 = 2000 + $e2;
                    if ($e2 < $s2) {
                        $end4 += 100;
                    }

                    $short = sprintf('%02d-%02d', $s2, $e2);

                    return ['label' => $short, 'variants' => [$short, "{$start4}-{$end4}"]];
                }

                if (preg_match('/^\d{4}$/', $y)) {
                    $short = sprintf('%02d-%02d', $y % 100, ($y + 1) % 100);

                    return ['label' => $short, 'variants' => [$short, "{$y}-".($y + 1)]];
                }

                if (preg_match('/^\d{2}$/', $y)) {
                    $start4 = 2000 + (int) $y;
                    $short = sprintf('%02d-%02d', $start4 % 100, ($start4 + 1) % 100);

                    return ['label' => $short, 'variants' => [$short, "{$start4}-".($start4 + 1)]];
                }

                return ['label' => $y, 'variants' => [$y]];
            };

            $yearItems = [];
            foreach ($years as $y) {
                $yearItems[] = $normalizeYearRange((string) $y);
            }

            /*
            |--------------------------------------------------------------------------
            | Month / Quarter Mapping
            |--------------------------------------------------------------------------
            */
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March',
                4 => 'April', 5 => 'May', 6 => 'June',
                7 => 'July', 8 => 'August', 9 => 'September',
                10 => 'October', 11 => 'November', 12 => 'December',
            ];

            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];

            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            /*
            |--------------------------------------------------------------------------
            | Dynamic Select Columns
            |--------------------------------------------------------------------------
            */
            $selects = ['branch'];

            foreach ($yearItems as $it) {

                $variantsSql = implode(', ', array_map(
                    fn ($v) => "'".str_replace("'", "''", $v)."'",
                    $it['variants']
                ));

                $label = str_replace('`', '', $it['label']);

                $selects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                        THEN amount ELSE 0 END) as `{$label}`";
            }

            /*
            |--------------------------------------------------------------------------
            | Growth Column
            |--------------------------------------------------------------------------
            */
            if (count($yearItems) >= 2) {

                $prev = $yearItems[count($yearItems) - 2];
                $curr = $yearItems[count($yearItems) - 1];

                $prevSql = implode(', ', array_map(fn ($v) => "'$v'", $prev['variants']));
                $currSql = implode(', ', array_map(fn ($v) => "'$v'", $curr['variants']));

                $selects[] = "CASE
                WHEN SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END) > 0
                THEN ROUND(
                    (
                        SUM(CASE WHEN fy_year IN ({$currSql}) THEN amount ELSE 0 END)
                        -
                        SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                    )
                    /
                    SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                *100,2)
                ELSE NULL END as growth";
            } else {
                $selects[] = 'NULL as growth';
            }

            /*
            |--------------------------------------------------------------------------
            | Base Query
            |--------------------------------------------------------------------------
            */
            $query = DB::table('performance_reports')
                ->where('status', 'approved');

            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && ! empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            if (
                Utility::checkViewPermission('performance_report') ||
                Utility::checkBranchesViewPermission('performance_report')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('performance_report')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('performance_report')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }

                });
            }

            $baseQuery = (clone $query)
                ->selectRaw(implode(', ', $selects))
                ->groupBy('branch')
                ->orderBy('branch');

            /*
            |--------------------------------------------------------------------------
            | Pagination OR Export dataset
            |--------------------------------------------------------------------------
            */
            $rows = $download
                ? $baseQuery->get()
                : $baseQuery->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Totals Row
            |--------------------------------------------------------------------------
            */
            $totalSelects = ["'Total' as branch"];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(fn ($v) => "'$v'", $it['variants']));
                $label = str_replace('`', '', $it['label']);

                $totalSelects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                            THEN amount ELSE 0 END) as `{$label}`";
            }

            $totalSelects[] = 'NULL as growth';

            $totals = (clone $query)
                ->selectRaw(implode(', ', $totalSelects))
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Headers
            |--------------------------------------------------------------------------
            */
            $headers = array_merge(
                ['branch'],
                array_map(fn ($it) => $it['label'], $yearItems),
                ['growth']
            );

            /*
            |--------------------------------------------------------------------------
            | QUEUED EXPORT
            |--------------------------------------------------------------------------
            */
            if ($download) {

                $filename = 'branch_summary_'.now()->format('Ymd_His').'.xlsx';
                (new BranchSummaryExport($headers, $rows, $totals))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess(
                    'Export started. You will get a download link soon.',
                    [
                        'file' => $filename,
                        'url' => url("storage/exports/{$filename}"),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Normal API Response
            |--------------------------------------------------------------------------
            */
            return Utility::apiSuccess('Branch summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Error branchSummaryReport',
                ['exception' => $ex->getMessage()]
            );
        }
    }

    public function principalSummaryReport(Request $request)
    {
        try {

            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);
            $download = $request->boolean('download');

            /*
            |--------------------------------------------------------------------------
            | Default Financial Years
            |--------------------------------------------------------------------------
            */
            if (empty($years) || ! is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2).'-'.($fyStart - 1),
                    ($fyStart - 1).'-'.$fyStart,
                    ($fyStart).'-'.($fyStart + 1),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Normalize FY
            |--------------------------------------------------------------------------
            */
            $normalizeYearRange = function (string $y) {
                $y = trim($y);

                if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $y, $m)) {
                    $short = sprintf('%02d-%02d', $m[1] % 100, $m[2] % 100);

                    return ['label' => $short, 'variants' => [$short, "{$m[1]}-{$m[2]}"]];
                }

                if (preg_match('/^(\d{2})\s*-\s*(\d{2})$/', $y, $m)) {
                    $s2 = (int) $m[1];
                    $e2 = (int) $m[2];
                    $start4 = 2000 + $s2;
                    $end4 = 2000 + $e2;
                    if ($e2 < $s2) {
                        $end4 += 100;
                    }
                    $short = sprintf('%02d-%02d', $s2, $e2);

                    return ['label' => $short, 'variants' => [$short, "{$start4}-{$end4}"]];
                }

                if (preg_match('/^\d{4}$/', $y)) {
                    $short = sprintf('%02d-%02d', $y % 100, ($y + 1) % 100);

                    return ['label' => $short, 'variants' => [$short, "{$y}-".($y + 1)]];
                }

                return ['label' => $y, 'variants' => [$y]];
            };

            $yearItems = [];
            foreach ($years as $y) {
                $yearItems[] = $normalizeYearRange((string) $y);
            }

            /*
            |--------------------------------------------------------------------------
            | Month / Quarter
            |--------------------------------------------------------------------------
            */
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March',
                4 => 'April', 5 => 'May', 6 => 'June',
                7 => 'July', 8 => 'August', 9 => 'September',
                10 => 'October', 11 => 'November', 12 => 'December',
            ];

            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];

            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            /*
            |--------------------------------------------------------------------------
            | Build Select Columns
            |--------------------------------------------------------------------------
            */
            $selects = ['principal_name as principal'];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(
                    fn ($v) => "'".str_replace("'", "''", $v)."'",
                    $it['variants']
                ));
                $label = str_replace('`', '', $it['label']);
                $selects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                            THEN amount ELSE 0 END) as `{$label}`";
            }

            if (count($yearItems) >= 2) {

                $prev = $yearItems[count($yearItems) - 2];
                $curr = $yearItems[count($yearItems) - 1];

                $prevSql = implode(', ', array_map(fn ($v) => "'$v'", $prev['variants']));
                $currSql = implode(', ', array_map(fn ($v) => "'$v'", $curr['variants']));

                $selects[] = "CASE
                    WHEN SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END) > 0
                    THEN ROUND(
                        (
                            SUM(CASE WHEN fy_year IN ({$currSql}) THEN amount ELSE 0 END)
                            -
                            SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                        )
                        /
                        SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                    *100,2)
                    ELSE NULL END as growth";
            } else {
                $selects[] = 'NULL as growth';
            }

            /*
            |--------------------------------------------------------------------------
            | Base Query
            |--------------------------------------------------------------------------
            */
            $query = DB::table('performance_reports')
                ->where('status', 'approved');

            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && ! empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            if (
                Utility::checkViewPermission('performance_report') ||
                Utility::checkBranchesViewPermission('performance_report')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('performance_report')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('performance_report')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }

                });
            }

            $baseQuery = (clone $query)
                ->selectRaw(implode(', ', $selects))
                ->groupBy('principal_name')
                ->orderBy('principal_name');

            $rows = $download
                ? $baseQuery->get()
                : $baseQuery->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */
            $totalSelects = ["'Total' as principal"];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(fn ($v) => "'$v'", $it['variants']));
                $label = str_replace('`', '', $it['label']);
                $totalSelects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                                THEN amount ELSE 0 END) as `{$label}`";
            }

            $totalSelects[] = 'NULL as growth';

            $totals = (clone $query)
                ->selectRaw(implode(', ', $totalSelects))
                ->first();

            $headers = array_merge(
                ['principal'],
                array_map(fn ($it) => $it['label'], $yearItems),
                ['growth']
            );

            /*
            |--------------------------------------------------------------------------
            | EXPORT
            |--------------------------------------------------------------------------
            */
            if ($download) {

                $filename = 'principal_summary_'.now()->format('Ymd_His').'.xlsx';

                (new PrincipalSummaryExport($headers, $rows, $totals))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess(
                    'Export started. You will get a download link soon.',
                    [
                        'file' => $filename,
                        'url' => url("storage/exports/{$filename}"),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Normal API Response
            |--------------------------------------------------------------------------
            */
            return Utility::apiSuccess('Principal summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (\Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Error principalSummaryReport',
                ['exception' => $ex->getMessage()]
            );
        }
    }

    public function customerSummaryReport(Request $request)
    {
        try {

            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);
            $download = $request->boolean('download'); // ⭐ NEW

            /*
            |--------------------------------------------------------------------------
            | Default Financial Years
            |--------------------------------------------------------------------------
            */
            if (empty($years) || ! is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2).'-'.($fyStart - 1),
                    ($fyStart - 1).'-'.$fyStart,
                    ($fyStart).'-'.($fyStart + 1),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Normalize FY
            |--------------------------------------------------------------------------
            */
            $normalizeYearRange = function (string $y) {
                $y = trim($y);

                if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $y, $m)) {
                    $short = sprintf('%02d-%02d', $m[1] % 100, $m[2] % 100);

                    return ['label' => $short, 'variants' => [$short, "{$m[1]}-{$m[2]}"]];
                }

                if (preg_match('/^(\d{2})\s*-\s*(\d{2})$/', $y, $m)) {
                    $s2 = (int) $m[1];
                    $e2 = (int) $m[2];
                    $start4 = 2000 + $s2;
                    $end4 = 2000 + $e2;
                    if ($e2 < $s2) {
                        $end4 += 100;
                    }
                    $short = sprintf('%02d-%02d', $s2, $e2);

                    return ['label' => $short, 'variants' => [$short, "{$start4}-{$end4}"]];
                }

                if (preg_match('/^\d{4}$/', $y)) {
                    $short = sprintf('%02d-%02d', $y % 100, ($y + 1) % 100);

                    return ['label' => $short, 'variants' => [$short, "{$y}-".($y + 1)]];
                }

                return ['label' => $y, 'variants' => [$y]];
            };

            $yearItems = [];
            foreach ($years as $y) {
                $yearItems[] = $normalizeYearRange((string) $y);
            }

            /*
            |--------------------------------------------------------------------------
            | Month / Quarter
            |--------------------------------------------------------------------------
            */
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March',
                4 => 'April', 5 => 'May', 6 => 'June',
                7 => 'July', 8 => 'August', 9 => 'September',
                10 => 'October', 11 => 'November', 12 => 'December',
            ];

            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];

            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            /*
            |--------------------------------------------------------------------------
            | Build Select Columns
            |--------------------------------------------------------------------------
            */
            $selects = ['customer_name as customer'];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(
                    fn ($v) => "'".str_replace("'", "''", $v)."'",
                    $it['variants']
                ));
                $label = str_replace('`', '', $it['label']);
                $selects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                            THEN amount ELSE 0 END) as `{$label}`";
            }

            if (count($yearItems) >= 2) {

                $prev = $yearItems[count($yearItems) - 2];
                $curr = $yearItems[count($yearItems) - 1];

                $prevSql = implode(', ', array_map(fn ($v) => "'$v'", $prev['variants']));
                $currSql = implode(', ', array_map(fn ($v) => "'$v'", $curr['variants']));

                $selects[] = "CASE
                    WHEN SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END) > 0
                    THEN ROUND(
                        (
                            SUM(CASE WHEN fy_year IN ({$currSql}) THEN amount ELSE 0 END)
                            -
                            SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                        )
                        /
                        SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                    *100,2)
                    ELSE NULL END as growth";
            } else {
                $selects[] = 'NULL as growth';
            }

            /*
            |--------------------------------------------------------------------------
            | Base Query
            |--------------------------------------------------------------------------
            */
            $query = DB::table('performance_reports')
                ->where('status', 'approved');

            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && ! empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            if (
                Utility::checkViewPermission('performance_report') ||
                Utility::checkBranchesViewPermission('performance_report')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('performance_report')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('performance_report')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }

                });
            }
            $baseQuery = (clone $query)
                ->selectRaw(implode(', ', $selects))
                ->groupBy('customer_name')
                ->orderBy('customer_name');

            $rows = $download
                ? $baseQuery->get()
                : $baseQuery->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */
            $totalSelects = ["'Total' as customer"];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(fn ($v) => "'$v'", $it['variants']));
                $label = str_replace('`', '', $it['label']);
                $totalSelects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                                THEN amount ELSE 0 END) as `{$label}`";
            }

            $totalSelects[] = 'NULL as growth';

            $totals = (clone $query)
                ->selectRaw(implode(', ', $totalSelects))
                ->first();

            $headers = array_merge(
                ['customer'],
                array_map(fn ($it) => $it['label'], $yearItems),
                ['growth']
            );

            /*
            |--------------------------------------------------------------------------
            | EXPORT
            |--------------------------------------------------------------------------
            */
            if ($download) {

                $filename = 'customer_summary_'.now()->format('Ymd_His').'.xlsx';

                (new CustomerSummaryExport($headers, $rows, $totals))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess(
                    'Export started. You will get a download link soon.',
                    [
                        'file' => $filename,
                        'url' => url("storage/exports/{$filename}"),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Normal API Response
            |--------------------------------------------------------------------------
            */
            return Utility::apiSuccess('Customer summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Error customerSummaryReport',
                ['exception' => $ex->getMessage()]
            );
        }
    }

    public function categorySummaryReport(Request $request)
    {
        try {

            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);
            $download = $request->boolean('download'); // ⭐ added

            if (empty($years) || ! is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2).'-'.($fyStart - 1),
                    ($fyStart - 1).'-'.$fyStart,
                    ($fyStart).'-'.($fyStart + 1),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Year Normalizer
            |--------------------------------------------------------------------------
            */
            $normalizeYearRange = function (string $y) {
                $y = trim($y);

                if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $y, $m)) {
                    $short = sprintf('%02d-%02d', $m[1] % 100, $m[2] % 100);

                    return ['label' => $short, 'variants' => [$short, "{$m[1]}-{$m[2]}"]];
                }

                if (preg_match('/^(\d{2})\s*-\s*(\d{2})$/', $y, $m)) {
                    $s2 = (int) $m[1];
                    $e2 = (int) $m[2];
                    $start4 = 2000 + $s2;
                    $end4 = 2000 + $e2;
                    if ($e2 < $s2) {
                        $end4 += 100;
                    }
                    $short = sprintf('%02d-%02d', $s2, $e2);

                    return ['label' => $short, 'variants' => [$short, "{$start4}-{$end4}"]];
                }

                if (preg_match('/^\d{4}$/', $y)) {
                    $short = sprintf('%02d-%02d', $y % 100, ($y + 1) % 100);

                    return ['label' => $short, 'variants' => [$short, "{$y}-".($y + 1)]];
                }

                return ['label' => $y, 'variants' => [$y]];
            };

            $yearItems = [];
            foreach ($years as $y) {
                $yearItems[] = $normalizeYearRange((string) $y);
            }

            /*
            |--------------------------------------------------------------------------
            | Month / Quarter Filter
            |--------------------------------------------------------------------------
            */
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March',
                4 => 'April', 5 => 'May', 6 => 'June',
                7 => 'July', 8 => 'August', 9 => 'September',
                10 => 'October', 11 => 'November', 12 => 'December',
            ];

            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];

            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            /*
            |--------------------------------------------------------------------------
            | Select Columns
            |--------------------------------------------------------------------------
            */
            $selects = ['category'];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(
                    fn ($v) => "'".str_replace("'", "''", $v)."'",
                    $it['variants']
                ));
                $label = str_replace('`', '', $it['label']);

                $selects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                        THEN amount ELSE 0 END) as `{$label}`";
            }

            if (count($yearItems) >= 2) {

                $prev = $yearItems[count($yearItems) - 2];
                $curr = $yearItems[count($yearItems) - 1];

                $prevSql = implode(', ', array_map(fn ($v) => "'$v'", $prev['variants']));
                $currSql = implode(', ', array_map(fn ($v) => "'$v'", $curr['variants']));

                $selects[] = "CASE
                WHEN SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END) > 0
                THEN ROUND(
                    (
                        SUM(CASE WHEN fy_year IN ({$currSql}) THEN amount ELSE 0 END)
                        -
                        SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                    )
                    /
                    SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                *100,2)
                ELSE NULL END as growth";
            } else {
                $selects[] = 'NULL as growth';
            }

            /*
            |--------------------------------------------------------------------------
            | Base Query
            |--------------------------------------------------------------------------
            */
            $query = DB::table('performance_reports')
                ->where('status', 'approved');

            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && ! empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            if (
                Utility::checkViewPermission('performance_report') ||
                Utility::checkBranchesViewPermission('performance_report')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('performance_report')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('performance_report')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }

                });
            }

            $baseQuery = (clone $query)
                ->selectRaw(implode(', ', $selects))
                ->groupBy('category')
                ->orderBy('category');

            $rows = $download
                ? $baseQuery->get()
                : $baseQuery->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */
            $totalSelects = ["'Total' as category"];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(fn ($v) => "'$v'", $it['variants']));
                $label = str_replace('`', '', $it['label']);
                $totalSelects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                            THEN amount ELSE 0 END) as `{$label}`";
            }

            $totalSelects[] = 'NULL as growth';

            $totals = (clone $query)
                ->selectRaw(implode(', ', $totalSelects))
                ->first();

            $headers = array_merge(
                ['category'],
                array_map(fn ($it) => $it['label'], $yearItems),
                ['growth']
            );

            /*
            |--------------------------------------------------------------------------
            | Export Block
            |--------------------------------------------------------------------------
            */
            if ($download) {

                $filename = 'category_summary_'.now()->format('Ymd_His').'.xlsx';

                (new CategorySummaryExport($headers, $rows, $totals))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess(
                    'Export started. You will get a download link soon.',
                    [
                        'file' => $filename,
                        'url' => url("storage/exports/{$filename}"),
                    ]
                );
            }

            return Utility::apiSuccess('Category summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Error categorySummaryReport',
                ['exception' => $ex->getMessage()]
            );
        }
    }

    public function authorisedSummaryReport(Request $request)
    {
        try {

            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);
            $download = $request->boolean('download'); // ⭐ added

            if (empty($years) || ! is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2).'-'.($fyStart - 1),
                    ($fyStart - 1).'-'.$fyStart,
                    $fyStart.'-'.($fyStart + 1),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Year Normalizer
            |--------------------------------------------------------------------------
            */
            $normalizeYearRange = function (string $y) {

                $y = trim($y);

                if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $y, $m)) {
                    $short = sprintf('%02d-%02d', $m[1] % 100, $m[2] % 100);

                    return ['label' => $short, 'variants' => [$short, "{$m[1]}-{$m[2]}"]];
                }

                if (preg_match('/^(\d{2})\s*-\s*(\d{2})$/', $y, $m)) {
                    $s2 = (int) $m[1];
                    $e2 = (int) $m[2];
                    $start4 = 2000 + $s2;
                    $end4 = 2000 + $e2;
                    if ($e2 < $s2) {
                        $end4 += 100;
                    }
                    $short = sprintf('%02d-%02d', $s2, $e2);

                    return ['label' => $short, 'variants' => [$short, "{$start4}-{$end4}"]];
                }

                if (preg_match('/^\d{4}$/', $y)) {
                    $short = sprintf('%02d-%02d', $y % 100, ($y + 1) % 100);

                    return ['label' => $short, 'variants' => [$short, "{$y}-".($y + 1)]];
                }

                return ['label' => $y, 'variants' => [$y]];
            };

            $yearItems = [];
            foreach ($years as $y) {
                $yearItems[] = $normalizeYearRange((string) $y);
            }

            /*
            |--------------------------------------------------------------------------
            | Month / Quarter Filter
            |--------------------------------------------------------------------------
            */
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March',
                4 => 'April', 5 => 'May', 6 => 'June',
                7 => 'July', 8 => 'August', 9 => 'September',
                10 => 'October', 11 => 'November', 12 => 'December',
            ];

            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];

            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            /*
            |--------------------------------------------------------------------------
            | Build Selects
            |--------------------------------------------------------------------------
            */
            $selects = ['authorised'];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(
                    fn ($v) => "'".str_replace("'", "''", $v)."'",
                    $it['variants']
                ));

                $label = str_replace('`', '', $it['label']);

                $selects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                        THEN amount ELSE 0 END) as `{$label}`";
            }

            if (count($yearItems) >= 2) {

                $prev = $yearItems[count($yearItems) - 2];
                $curr = $yearItems[count($yearItems) - 1];

                $prevSql = implode(', ', array_map(fn ($v) => "'$v'", $prev['variants']));
                $currSql = implode(', ', array_map(fn ($v) => "'$v'", $curr['variants']));

                $selects[] = "CASE
                WHEN SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END) > 0
                THEN ROUND(
                    (
                        SUM(CASE WHEN fy_year IN ({$currSql}) THEN amount ELSE 0 END)
                        -
                        SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                    )
                    /
                    SUM(CASE WHEN fy_year IN ({$prevSql}) THEN amount ELSE 0 END)
                *100,2)
                ELSE NULL END as growth";
            } else {
                $selects[] = 'NULL as growth';
            }

            /*
            |--------------------------------------------------------------------------
            | Base Query
            |--------------------------------------------------------------------------
            */
            $query = DB::table('performance_reports')
                ->where('status', 'approved');

            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && ! empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            if (
                Utility::checkViewPermission('performance_report') ||
                Utility::checkBranchesViewPermission('performance_report')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('performance_report')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('performance_report')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }

                });
            }
            $baseQuery = (clone $query)
                ->selectRaw(implode(', ', $selects))
                ->groupBy('authorised')
                ->orderBy('authorised');

            $rows = $download
                ? $baseQuery->get()
                : $baseQuery->paginate($perPage);

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */
            $totalSelects = ["'Total' as authorised"];

            foreach ($yearItems as $it) {
                $variantsSql = implode(', ', array_map(fn ($v) => "'$v'", $it['variants']));
                $label = str_replace('`', '', $it['label']);
                $totalSelects[] = "SUM(CASE WHEN fy_year IN ({$variantsSql})
                            THEN amount ELSE 0 END) as `{$label}`";
            }

            $totalSelects[] = 'NULL as growth';

            $totals = (clone $query)
                ->selectRaw(implode(', ', $totalSelects))
                ->first();

            $headers = array_merge(
                ['authorised'],
                array_map(fn ($it) => $it['label'], $yearItems),
                ['growth']
            );

            /*
            |--------------------------------------------------------------------------
            | Export
            |--------------------------------------------------------------------------
            */
            if ($download) {

                $filename = 'authorised_summary_'.now()->format('Ymd_His').'.xlsx';

                (new AuthorisedSummaryExport($headers, $rows, $totals))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess(
                    'Export started. You will get a download link soon.',
                    [
                        'file' => $filename,
                        'url' => url("storage/exports/{$filename}"),
                    ]
                );
            }

            return Utility::apiSuccess('Authorised summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Error authorisedSummaryReport',
                ['exception' => $ex->getMessage()]
            );
        }
    }

    public function updateInvoiceStatus(Request $request)
    {
        try {

            $data = $request->only('ids', 'status');
            $validator = Validator::make($request->all(), [
                'ids' => 'array|min:1',
                'status' => 'required|string',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 211);
            }

            $msg = 'status updated successsfully.';
            if ($data['status'] == 'flush_all') {
                $msg = 'data flush';
                $status = SaleReport::delete();

            } else {
                $status = SaleReport::whereIn('id', $data['ids'])->update(['status' => $data['status']]);
            }

            if (! $status) {
                return Utility::apiError('Update status error', $validator->errors(), 211);
            }

            return Utility::apiSuccess($msg, [], 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error updateInvoiceStatus', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteSaleRecords(Request $request)
    {
        try {

            $data = $request->only('ids', 'status');

            if (! empty($data['ids'])) {
                $status = SaleReport::whereIn('id', $data['ids'])->delete();
            }

            if (empty($data['ids'])) {
                $status = SaleReport::query()->delete();
            }
            if (! $status) {
                return Utility::apiError('Fail to delete records', [], 211);
            }

            return Utility::apiSuccess('records deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error deleted sale report', ['exception' => $ex->getMessage()]);
        }
    }
}

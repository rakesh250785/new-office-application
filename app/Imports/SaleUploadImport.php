<?php

namespace App\Imports;

use App\Models\ImportJob;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SaleUploadImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public int $jobId;

    public array $updateCols;

    protected int $readChunkSize = 2000;

    protected int $upsertChunkSize = 1500;

    public function __construct(int $jobId, array $updateCols, ?string $tmpDir = null)
    {
        $this->jobId = $jobId;

        $allowed = [
            'qtr', 'month', 'year', 'invoice', 'invoice date', 'order no',
            'customer name', 'branch', 'description', 'part no',
            'categories', 'principal name', 'authorised', 'qty', 'amount',
        ];

        $this->updateCols = array_values(array_intersect($allowed, array_map('strtolower', $updateCols)));
    }

    public function collection(Collection $rows)
    {
        if (empty($this->updateCols) || $rows->isEmpty()) {
            return;
        }

        $now = Carbon::now()->toDateTimeString();
        $batch = [];
        $validRowCount = 0;

        foreach ($rows as $r) {
            $rawPart = $r['part_no'] ?? null;
            $rawOrder = $r['invoice'] ?? null;

            // skip rows missing keys
            if ($rawPart === null || $rawOrder === null) {
                continue;
            }

            $part = trim((string) $rawPart);
            $orderNo = trim((string) $rawOrder);

            if ($part === '' || $orderNo === '') {
                continue;
            }

            // normalize to reduce mismatch (choose one: upper or lower)
            $part = mb_strtoupper($part);
            $orderNo = mb_strtoupper($orderNo);

            $qty = isset($r['qty']) && is_numeric($r['qty']) ? (int) $r['qty'] : null;
            $amount = isset($r['amount']) && is_numeric($r['amount']) ? round((float) $r['amount'], 2) : null;
            $invoiceDate = $this->resolveInvoiceDate($r['invoice_date'] ?? null);

            $batch[] = [
                'qtr' => $this->nullableTrim($r['qtr'] ?? null),
                'month' => $this->nullableTrim($r['month'] ?? null),
                'fy_year' => $this->nullableTrim($r['year'] ?? null),
                'invoice' => $this->nullableTrim($r['invoice'] ?? null),
                'invoice_date' => $invoiceDate,
                'order_no' => $orderNo,
                'customer_name' => $this->nullableTrim($r['customer_name'] ?? null),
                'branch' => $this->nullableTrim($r['branch'] ?? null),
                'description' => $this->nullableTrim($r['description'] ?? null),
                'part_no' => $part,
                'category' => $this->nullableTrim($r['categories'] ?? null),
                'principal_name' => $this->nullableTrim($r['principal_name'] ?? null),
                'authorised' => $this->nullableTrim($r['authorised'] ?? null),
                'qty' => $qty,
                'amount' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $validRowCount++;

            if (count($batch) >= $this->upsertChunkSize) {
                $this->flushUpsert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $this->flushUpsert($batch);
        }

        if ($validRowCount > 0) {
            try {
                ImportJob::where('id', $this->jobId)->increment('processed_rows', $validRowCount);
            } catch (Exception $e) {
                Log::error("Failed to increment processed_rows for ImportJob {$this->jobId}: ".$e->getMessage());
            }
        }
    }

    protected function flushUpsert(array $rows)
    {
        if (empty($rows)) {
            return;
        }

        try {
            DB::transaction(function () use ($rows) {
                DB::table('performance_reports')->upsert(
                    $rows,
                    ['invoice', 'part_no'],
                    [
                        'qty', 'amount', 'customer_name', 'branch', 'description', 'category',
                        'principal_name', 'authorised', 'invoice', 'invoice_date', 'month',
                        'qtr', 'fy_year', 'updated_at',
                    ]
                );
            }, 3);
        } catch (Exception $e) {
            Log::error("Upsert failed on job {$this->jobId}: ".$e->getMessage(), [
                'rows' => count($rows),
            ]);
        }
    }

    protected function resolveInvoiceDate($raw)
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw) && (float) $raw > 0) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);

                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function nullableTrim($v)
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    public function chunkSize(): int
    {
        return $this->readChunkSize;
    }
}

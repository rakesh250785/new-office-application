<?php

namespace App\Imports;

use App\Models\ImportJob;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SaleUploadImport implements  ToCollection, WithChunkReading, WithHeadingRow
{
    public int $jobId;

    public array $updateCols;

    protected string $tmpDir;

    // Tunables (adjust to environment)
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
        // normalize and filter incoming headers
        $this->updateCols = array_values(array_intersect($allowed, array_map('strtolower', $updateCols)));

        $this->tmpDir = $tmpDir ?: storage_path('app/import_chunks');
        if (! is_dir($this->tmpDir)) {
            @mkdir($this->tmpDir, 0755, true);
        }
    }

    /**
     * Called per chunk. $rows uses heading row keys (thanks to WithHeadingRow).
     */
    public function collection(Collection $rows)
    {
        if (empty($this->updateCols) || $rows->isEmpty()) {
            return;
        }

        $now = Carbon::now()->toDateTimeString();
        $batch = [];
        $validRowCount = 0; // number of rows we'll treat as valid (non-empty & upserted)

        foreach ($rows as $r) {
            $part = isset($r['part_no']) ? trim((string) $r['part_no']) : null;
            $orderNo = isset($r['order_no']) ? trim((string) $r['order_no']) : null;

            // skip invalid rows
            if (! $part || ! $orderNo) {
                continue;
            }

            $qty = isset($r['qty']) && is_numeric($r['qty']) ? (int) $r['qty'] : 0;
            $amount = isset($r['amount']) && is_numeric($r['amount']) ? round((float) $r['amount'], 2) : 0.00;
            $invoiceDate = $this->resolveInvoiceDate($r['invoice_date'] ?? null);

            $batch[] = [
                'qtr' => $r['qtr'] ?? null,
                'month' => $r['month'] ?? null,
                'fy_year' => $r['year'] ?? null,
                'invoice' => $r['invoice'] ?? null,
                'invoice_date' => $invoiceDate,
                'order_no' => $orderNo,
                'customer_name' => $r['customer_name'] ?? null,
                'branch' => $r['branch'] ?? null,
                'description' => $r['description'] ?? null,
                'part_no' => $part,
                'category' => $r['categories'] ?? null,
                'principal_name' => $r['principal_name'] ?? null,
                'authorised' => $r['authorised'] ?? null,
                'qty' => $qty,
                'amount' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $validRowCount++;

            // flush to DB in smaller upsert batches to limit memory and DB lock
            if (count($batch) >= $this->upsertChunkSize) {
                $this->flushUpsert($batch);
                $batch = [];
            }
        }

        // final flush
        if (! empty($batch)) {
            $this->flushUpsert($batch);
            $batch = [];
        }

        // Update ImportJob processed_rows only (do NOT increment total_rows here)
        if ($validRowCount > 0) {
            try {
                ImportJob::where('id', $this->jobId)->increment('processed_rows', $validRowCount);
                // Do NOT touch total_rows here — completion is set by the job handler after import ends.
            } catch (Exception $e) {
                Log::error("Failed to increment processed_rows for ImportJob {$this->jobId}: ".$e->getMessage());
            }
        }
    }

    /**
     * Upsert helper with exception handling.
     */
    protected function flushUpsert(array $rows)
    {
        if (empty($rows)) {
            return;
        }
        try {
            DB::table('performance_reports')->upsert(
                $rows,
                ['order_no', 'part_no'],
                [
                    'qty', 'amount', 'customer_name', 'branch', 'description', 'category',
                    'principal_name', 'authorised', 'invoice', 'invoice_date', 'month',
                    'qtr', 'fy_year', 'updated_at',
                ]
            );
        } catch (Exception $e) {
            Log::error("Upsert failed on job {$this->jobId}: ".$e->getMessage());
        }
    }

    /**
     * Try several strategies to normalize invoice date.
     */
    protected function resolveInvoiceDate($raw)
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // excel serial number -> date
        if (is_numeric($raw) && (float) $raw > 0) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);

                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // try parseable string
        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return $this->readChunkSize;
    }
}

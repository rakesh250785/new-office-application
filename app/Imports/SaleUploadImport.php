<?php

namespace App\Imports;

use App\Models\ImportJob;
use App\Models\SaleReport;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SaleUploadImport implements ShouldQueue, ToCollection, WithChunkReading, WithHeadingRow
{
    public int $jobId;

    public array $updateCols;

    protected string $tmpDir;

    // Tunables
    protected int $csvRowLimit = 500000;

    protected int $readChunkSize = 5000;

    protected int $upsertChunkSize = 2000;

    public function __construct(int $jobId, array $updateCols, ?string $tmpDir = null)
    {
        $this->jobId = $jobId;
        $allowed = [
            'qtr', 'month', 'year', 'invoice', 'invoice date', 'order no',
            'customer name', 'branch', 'description', 'part no',
            'categories', 'principal name', 'authorised', 'qty', 'amount',
        ];
        $this->updateCols = array_values(array_intersect($allowed, array_map('strtolower', $updateCols)));

        $this->tmpDir = $tmpDir ?: storage_path('app/import_chunks');
        if (! is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
    }

    public function collection(Collection $rows)
    {
        if (empty($this->updateCols) || $rows->isEmpty()) {
            return;
        }

        logger('Import chunk rows: '.$rows->count().' (job: '.$this->jobId.')');

        try {
            ImportJob::where('id', $this->jobId)->increment('processed_rows', $rows->count());
            $importJob = ImportJob::find($this->jobId);
            if ($importJob) {
                $importJob->update([
                    'status' => $importJob->processed_rows >= $importJob->total_rows ? 'completed' : 'processing',
                ]);
            }
        } catch (Exception $e) {
            Log::error("Failed to update ImportJob {$this->jobId}: ".$e->getMessage());
        }

        $now = Carbon::now()->toDateTimeString();
        $batch = [];

        foreach ($rows as $r) {

            $part = isset($r['part_no']) ? trim((string) $r['part_no']) : null;
            $orderNo = isset($r['order_no']) ? trim((string) $r['order_no']) : null;
            if (! $part || ! $orderNo) {
                continue;
            }

            $qty = isset($r['qty']) && is_numeric($r['qty']) ? (int) $r['qty'] : 0;
            $amount = isset($r['amount']) && is_numeric($r['amount']) ? round((float) $r['amount'], 2) : 0.00;
            $rData = !empty($r['invoice_date']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($r['invoice_date']) : null;
            $batch[] = [
                'qtr' => $r['qtr'] ?? null,
                'month' => $r['month'] ?? null,
                'fy_year' => $r['year'] ?? null,
                'invoice' => $r['invoice'] ?? null,
                'invoice_date' => ! empty($rData) ? $this->normalizeDate($rData) : null,
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
        }

        if (empty($batch)) {
            return;
        }

        $upsertKeys = ['order_no', 'part_no'];
        $updateCols = ['qty', 'amount', 'customer_name', 'branch', 'description', 'category', 'principal_name', 'authorised', 'invoice', 'invoice_date', 'month', 'qtr', 'fy_year', 'updated_at'];

        try {
            foreach (array_chunk($batch, $this->upsertChunkSize) as $chunk) {
                try {
                    DB::table('performance_reports')->upsert($chunk, $upsertKeys, $updateCols);
                } catch (Exception $e) {
                    Log::error("Upsert failed on job {$this->jobId}: ".$e->getMessage());
                }
            }
        } catch (Exception $e) {
            Log::error("Batch processing failed for job {$this->jobId}: ".$e->getMessage());
        }
    }

    protected function normalizeDate($value)
    {
        try {
            $dt = Carbon::parse($value);

            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    public function model(array $row)
    {
        return new SaleReport([
            'invoice_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['invoice_date']),
        ]);
    }

    public function chunkSize(): int
    {
        return $this->readChunkSize;
    }
}

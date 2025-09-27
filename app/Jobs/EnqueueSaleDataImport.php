<?php

namespace App\Jobs;

use App\Imports\SaleUploadImport;
use App\Models\ImportJob;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class EnqueueSaleDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    public int $jobId;

    public array $headers;

    public ?string $tmpDir;

    public function __construct(string $filePath, int $jobId, array $headers = [], ?string $tmpDir = null)
    {
        $this->filePath = $filePath;
        $this->jobId = $jobId;
        $this->headers = $headers;
        $this->tmpDir = $tmpDir;
    }

    public function handle()
    {
        $importJob = ImportJob::find($this->jobId);

        try {
            if ($importJob) {
                $importJob->update(['status' => 'processing']);
            }

            $import = new SaleUploadImport($this->jobId, $this->headers, $this->tmpDir);

            Excel::import($import, $this->filePath);

            if ($importJob) {
                $importJob->update([
                    'status' => 'completed',
                    'file_deleted' => true,
                    'processed_rows' => $importJob->total_rows ?: $importJob->processed_rows,
                ]);
            }

            try {
                if (file_exists($this->filePath)) {
                    @unlink($this->filePath);
                }
            } catch (Exception $e) {
                Log::warning("Imported file deleted failed for {$this->filePath}: ".$e->getMessage());
            }
        } catch (Exception $ex) {
            Log::error("Sale data import failed (job {$this->jobId}): ".$ex->getMessage(), [
                'exception' => $ex,
                'file' => $this->filePath,
            ]);

            if ($importJob) {
                $importJob->update([
                    'status' => 'failed',
                ]);
            }
            throw $ex;
        }
    }
}

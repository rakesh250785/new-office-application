<?php

namespace App\Jobs;

use App\Imports\ProductUploadImport;
use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class EnqueueProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    public int $jobId;

    public array $header;

    public function __construct(string $filePath, int $jobId, array $header)
    {
        $this->filePath = $filePath;
        $this->jobId = $jobId;
        $this->header = $header;
    }

    public function handle(): void
    {
        try {
            $import = new ProductUploadImport($this->jobId, $this->header);
            Excel::queueImport($import, $this->filePath);
        } catch (Throwable $e) {
            Log::error('EnqueueProductImport failed: '.$e->getMessage(), [
                'file' => $this->filePath,
                'jobId' => $this->jobId,
            ]);
            ImportJob::where('id', $this->jobId)->update(['status' => 'failed']);
            throw $e;
        }
    }
}

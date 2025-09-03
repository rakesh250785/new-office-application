<?php

namespace App\Imports;

use App\Models\ImportJob;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductUploadImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    protected $type;
    protected $jobId;

    public function __construct($type, $jobId)
    {
        $this->type = $type;
        $this->jobId = $jobId;
    }

    public function collection(Collection $rows)
    {
        $bulkOps = [];

        $importJob = ImportJob::find($this->jobId);

        if ($importJob) {
            $importJob->increment('processed_rows', count($rows));

            if ($importJob->processed_rows >= $importJob->total_rows) {
                $importJob->update(['status' => 'completed']);
            } else {
                $importJob->update(['status' => 'processing']);
            }
        }

        foreach ($rows as $row) {

            $partNo = $row['part_no'] ?? null;
            if (!$partNo)
                continue;

            $updateData = [];

            if ($this->type === 'price') {
                $updateData['price'] = (float) ($row['price'] ?? 0);
                $updateData['price_updated_at'] = Carbon::now();
            }

            if ($this->type === 'quantity') {
                $updateData['quantity'] = (int) ($row['quantity'] ?? 0);
                $updateData['quantity_updated_at'] = Carbon::now();
            }

            $bulkOps[] = [
                'updateOne' => [
                    ['part_no' => $partNo],
                    ['$set' => $updateData],
                    ['upsert' => true],
                ],
            ];
        }

        if (!empty($bulkOps)) {
            Product::raw()->bulkWrite($bulkOps);
        }
    }

    public function chunkSize(): int
    {
        return 2000;
    }
}

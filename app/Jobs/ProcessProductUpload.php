<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Product;
use Carbon\Carbon;

class ProcessProductUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $type;

    public function __construct($path, $type)
    {
        $this->path = $path;
        $this->type = $type;
    }

    public function handle()
    {
        $rows = Excel::toArray([], storage_path('app/' . $this->path))[0];

        $bulkOps = [];
        foreach ($rows as $row) {
            $partNo = $row['part_no'] ?? null;
            if (!$partNo)
                continue;

            $updateData = [];
            if ($this->type === 'price') {
                $updateData['price'] = (float) ($row['price'] ?? 0);
                $updateData['price_updated_at'] = Carbon::now();
            } elseif ($this->type === 'quantity') {
                $updateData['quantity'] = (int) ($row['quantity'] ?? 0);
                $updateData['quantity_updated_at'] = Carbon::now();
            }

            $bulkOps[] = [
                'updateOne' => [
                    ['part_no' => $partNo],
                    ['$set' => $updateData],
                ]
            ];
        }

        if (!empty($bulkOps)) {
            Product::raw()->bulkWrite($bulkOps);
        }
    }
}

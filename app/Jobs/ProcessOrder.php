<?php

namespace App\Jobs;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Log;
use Throwable;
class ProcessOrder implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'isPhpEnabled' => true,
            ])->loadView('order.orderPdf', $this->data)->setPaper('A4', 'landscape');

            $fileName = $this->data['pdf_name'];
            $oldFileName = $this->data['old_pdf_name'];
            $year = now()->year;
            $directory = "ordersPdf/{$year}";
            $path = "{$directory}/{$fileName}";
            $disk = Storage::disk('public');

            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
                Log::info("Created directory: {$directory}");
            }

            $criteria = $this->data['orderInfo'] ?? [];
            if (empty($criteria)) {
                Log::error('Missing order search criteria in job data');

                return;
            }

            $whereCon = [
                'id' => $criteria['id'],
                'unique_order_no' => $criteria['unique_order_no'],
                'unique_quotation_no' => $criteria['unique_quotation_no'],
            ];


            $orderInfo = Order::where($whereCon)->first();
            
            if (! $orderInfo) {
                Log::error('No order found for criteria: '.json_encode($criteria));

                return;
            }

            if (! empty($oldFileName)) {
                $oldFile = "ordersPdf/{$year}/{$oldFileName}";
                if (! $disk->exists($oldFile)) {
                    $possibleFiles = $disk->allFiles('ordersPdf');
                    foreach ($possibleFiles as $file) {
                        if (str_ends_with($file, $orderInfo->pdf_name)) {
                            $oldFile = $file;
                            break;
                        }
                    }
                }

                if ($disk->exists($oldFile)) {
                    $disk->delete($oldFile);
                }
            }

            $disk->put($path, $pdf->output());
            $orderInfo->pdf_name = $fileName;
            $orderInfo->save();

            Log::info('Order PDF generated successfully');

        } catch (Throwable $e) {
            Log::error('Order PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use Throwable;

class ProcessPartialOrder implements ShouldQueue
{
    use Queueable;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            Log::info('Starting updating order PDF for partial order generation job (Browsershot)');

            /** -------------------------------------------------
             *  1. Render Blade → HTML
             * ------------------------------------------------- */
            $html = View::make('order.orderPdf', $this->data)->render();

            /** -------------------------------------------------
             *  2. Generate PDF using Chrome
             * ------------------------------------------------- */
            $pdf = Browsershot::html($html)
                ->setChromePath('/usr/bin/google-chrome')
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 5, 10)
                ->showBackground()
                ->showBrowserHeaderAndFooter()
                ->footerHtml('
                    <div style="
                        width:100%;
                        font-size:14px;
                        text-align:right;
                        padding-right:18px;
                        color:#222;
                        font-weight:700;
                    ">
                        Page <span class="pageNumber"></span> of <span class="totalPages"></span>
                    </div>
                ')
                ->noSandbox()
                ->pdf();

            /** -------------------------------------------------
             *  3. File handling
             * ------------------------------------------------- */
            $fileName = $this->data['pdf_name'];
            $oldFileName = $this->data['old_pdf_name'];
            $directory = 'ordersPdf';
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

            $criteria = [
                'id' => $criteria['id'],
                'unique_order_no' => $criteria['unique_order_no'],
            ];
            $orderInfo = Order::where($criteria)->first();

            if (! $orderInfo) {
                Log::error('No order found for criteria: '.json_encode($criteria));

                return;
            }

            /** -------------------------------------------------
             *  4. Delete old PDF if exists
             * ------------------------------------------------- */
            if (! empty($oldFileName)) {
                $oldFile = "{$directory}/{$oldFileName}";

                if (! $disk->exists($oldFile)) {
                    foreach ($disk->allFiles($directory) as $file) {
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

            /** -------------------------------------------------
             *  5. Save PDF
             * ------------------------------------------------- */
            $disk->put($path, $pdf);

            $orderInfo->pdf_name = $fileName;
            $orderInfo->pdf_status = 'ready';
            $orderInfo->save();

            Log::info('Order PDF generated successfully (Browsershot)');

        } catch (Throwable $e) {
            Log::error('Order PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

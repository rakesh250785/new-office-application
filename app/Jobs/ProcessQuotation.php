<?php

namespace App\Jobs;

use App\Models\Quotation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Log;
use Spatie\Browsershot\Browsershot;
use Throwable;

class ProcessQuotation implements ShouldQueue
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
            Log::info('Starting quotation PDF generation job (Browsershot)');

            /** -------------------------------------------------
             *  1. Render Blade → HTML
             * ------------------------------------------------- */
            $html = View::make('quotation.quotationPdf', $this->data)->render();

            /** -------------------------------------------------
             *  2. Generate PDF using Chrome
             * ------------------------------------------------- */
            $pdf = Browsershot::html($html)
                ->setChromePath('/usr/bin/google-chrome')
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->noSandbox() // required on most servers
                ->pdf();

            /** -------------------------------------------------
             *  3. File handling (unchanged logic)
             * ------------------------------------------------- */
            $fileName = $this->data['pdf_name'];
            $oldFileName = $this->data['old_pdf_name'];
            $directory = 'quotationsPdf';
            $path = "{$directory}/{$fileName}";
            $disk = Storage::disk('public');

            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
                Log::info("Created directory: {$directory}");
            }

            $criteria = $this->data['quotationInfo'] ?? [];

            if (empty($criteria)) {
                Log::error('Missing quotation search criteria in job data');

                return;
            }

            $quotationInfo = Quotation::where($criteria)->first();

            if (! $quotationInfo) {
                Log::error('No quotation found for criteria: '.json_encode($criteria));

                return;
            }

            /** -------------------------------------------------
             *  4. Delete old PDF if exists
             * ------------------------------------------------- */
            if (! empty($oldFileName)) {
                $oldFile = "{$directory}/{$oldFileName}";

                if (! $disk->exists($oldFile)) {
                    foreach ($disk->allFiles($directory) as $file) {
                        if (str_ends_with($file, $quotationInfo->pdf_name)) {
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

            $quotationInfo->pdf_name = $fileName;
            $quotationInfo->save();

            Log::info('Quotation PDF generated successfully (Browsershot)');

        } catch (Throwable $e) {
            Log::error('Quotation PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

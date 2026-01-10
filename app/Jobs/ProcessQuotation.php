<?php

namespace App\Jobs;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Log;
use Throwable;

class ProcessQuotation implements ShouldQueue
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
            Log::info('Starting quotation PDF generation job');

            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'Calibri',
            ])->loadView('quotation.quotationPdf', $this->data)->setPaper('A4', 'landscape');

            $fileName = $this->data['pdf_name'];
            $oldFileName = $this->data['old_pdf_name'];
            $directory = "quotationsPdf";
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

            if (! empty($oldFileName)) {
                $oldFile = "quotationsPdf/{$oldFileName}";
                if (! $disk->exists($oldFile)) {
                    $possibleFiles = $disk->allFiles('quotationsPdf');
                    foreach ($possibleFiles as $file) {
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

            $disk->put($path, $pdf->output());
            $quotationInfo->pdf_name = $fileName;
            $quotationInfo->save();

            Log::info('Quotation PDF generated successfully');

        } catch (Throwable $e) {
            Log::error('Quotation PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

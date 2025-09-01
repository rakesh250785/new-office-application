<?php

namespace App\Http\Controllers;
use App\Helpers\Utility;
use Exception;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{

    public function __construct()
    {
    }
    public function downloadExport($filename)
    {
        try {
            $path = storage_path("app/public/exports/{$filename}");

            if (!file_exists($path)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found or not ready yet.'
                ], 404);
            }

            return response()->download(
                $path,
                $filename,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        } catch (Exception $ex) {
            log::debug('Courier delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while downloading.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Vendor\Source;

use App\Exports\SourceExport;
use App\Models\Branch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class SourceController extends Controller
{

    public function addUpdateSource(Request $request)
    {
        try {
            # Extract and validate input
            $data = $request->only([
                'name',
                'branch_id',
                'source_id',
                'update_status'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Map validated data
            $payload = [
                'name' => $data['name'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::id(),
            ];

            # Create or update record
            $source = Source::updateOrCreate(
                ['id' => $data['source_id'] ?? null],
                $payload
            );

            # Return if fail
            if (!$source) {
                return Utility::apiError('Failed to save source', [], 221);
            }

            # Prepare message
            $message = isset($data['source_id']) ? 'Updated successfully' : 'Created successfully';

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getSource(Request $request)
    {
        try {

            # Request specific fields
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            # If download is true
            if (!empty($data['download'])) {
                $columns = [
                    'name' => 'Source Name',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'source_' . now()->format('Ymd_His') . '.xlsx';

                (new SourceExport($data, $columns, Source::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }


            # List Records
            $query = Source::with('branch:id,name')->whereNull('deleted_at');
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhereHas('branch', fn($b) => $b->where('name', 'like', "%$search%"));
                });
            }

            if (!empty($data['branch_list'])) {
                $query->whereIn('branch_id', (array) $data['branch_list']);
            }

            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            # Get paginated records
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $sourceData = $query->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Source list fetched successfully', $sourceData);

        } catch (Exception $ex) {
            Log::error('Source fetch error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return Utility::apiError('Failed to fetch sources', [
                'exception' => $ex->getMessage()
            ], 500);
        }
    }




    public function deleteSource(Request $request)
    {
        try {

            # Request id
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:sources,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Soft delete record
            $deleted = Source::where('id', $data['id'])->delete();

            # Retunr if fail
            if (!$deleted) {
                return Utility::apiError('Failed to delete source', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Source delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting source.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

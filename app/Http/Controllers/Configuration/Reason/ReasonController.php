<?php

namespace App\Http\Controllers\Configuration\Reason;

use App\Exports\Export;
use App\Exports\ReasonExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Reason;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReasonController extends Controller
{
    public function addUpdateReason(Request $request)
    {
        try {
            // Extract and validate input
            $data = $request->only([
                'name',
                'branch_id',
                'reason_id',
                'order_type',
                'update_status',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'order_type' => 'required|string|max:255',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Map validated data
            $payload = [
                'name' => $data['name'],
                'order_type' => $data['order_type'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::id(),
            ];

            // Create or update record
            $brand = Reason::updateOrCreate(
                ['id' => $data['reason_id'] ?? null],
                $payload
            );

            // Return if fail
            if (! $brand) {
                return Utility::apiError('Failed to save brand', [], 221);
            }

            // Prepare message
            $message = isset($data['reason_id']) ? 'Updated successfully' : 'Created successfully';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getReason(Request $request)
    {
        try {
            // Extract filters
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            // Export logic
            if (! empty($data['download'])) {
                $columns = [
                    'name' => 'Reason Name',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'reason_'.now()->format('Ymd_His').'.xlsx';

                // Queue async export (no PDO serialization issues)
                (new ReasonExport($data, $columns, Reason::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Base query with branch relationship
            $query = Reason::with('branch:id,name')->whereNull('deleted_at');

            // Free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%$search%"));
                });
            }

            // Branch filter
            if (! empty($data['branch_list'])) {
                $query->where('branch_id', $data['branch_list']);
            }

            // Date filter
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Paginate
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $reasonData = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('Reason list fetched successfully', $reasonData, 200);

        } catch (Exception $ex) {
            Log::error('Reason fetch error: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);

            return Utility::apiError('Failed to fetch reasons', [
                'exception' => $ex->getMessage(),
            ], 500);
        }
    }

    public function deleteReason(Request $request)
    {
        try {

            // Request id
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:reasons,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Soft delete record
            $deleted = Reason::where('id', $data['id'])->delete();

            // Retunr if fail
            if (! $deleted) {
                return Utility::apiError('Failed to delete brand', [], 221);
            }

            // Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Reason delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting brand.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

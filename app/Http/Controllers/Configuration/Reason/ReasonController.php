<?php

namespace App\Http\Controllers\Configuration\Reason;

use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Reason;
use Illuminate\Http\Request;
use App\Exports\Export;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class ReasonController extends Controller
{

    public function addUpdateReason(Request $request)
    {
        try {
            # Extract and validate input
            $data = $request->only([
                'name',
                'branch_id',
                'reason_id',
                'order_type',
                'update_status'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'order_type' => 'required|string|max:255',
            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Map validated data
            $payload = [
                'name' => $data['name'],
                'order_type' => $data['order_type'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::id(),
            ];

            # Create or update record
            $brand = Reason::updateOrCreate(
                ['id' => $data['reason_id'] ?? null],
                $payload
            );

            # Return if fail
            if (!$brand) {
                return Utility::apiError('Failed to save brand', [], 221);
            }

            # Prepare message
            $message = isset($data['reason_id']) ? 'Updated successfully' : 'Created successfully';

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getReason(Request $request)
    {
        try {

            # Get specific fields
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            # Base query with branch relationship
            $query = Reason::with('branch:id,name')->whereNull('deleted_at');

            # Apply free-text search
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhereHas('branch', function ($b) use ($search) {
                            $b->where('name', 'like', "%$search%");
                        });
                });
            }

            # Filter by branches
            if (!empty($data['branch_list'])) {
                $query->whereIn('branch_id', $data['branch_list']);
            }

            # Filter by date range
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay()
                ]);
            }

            # Export as Excel if requested
            if (!empty($data['download'])) {
                $columns = [
                    'name' => 'Name',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];
                $filename = 'brand' . now()->format('Ymd_His') . '.xlsx';
                return Excel::download(new Export($query, $columns), $filename);
            }

            # Paginate results
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $notificationData = $query->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Reason list fetched successfully', $notificationData, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch notifications', [
                'exception' => $ex->getMessage()
            ]);
        }
    }

    public function deleteReason(Request $request)
    {
        try {

            # Request id
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:reasons,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Soft delete record
            $deleted = Reason::where('id', $data['id'])->delete();

            # Retunr if fail
            if (!$deleted) {
                return Utility::apiError('Failed to delete brand', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Reason delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting brand.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

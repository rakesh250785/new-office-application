<?php

namespace App\Http\Controllers\ClientUser\Owner;

use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;
use App\Exports\Export;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class OwnerController extends Controller
{

    public function addUpdateOwner(Request $request)
    {
        try {
            # Extract and validate input
            $data = $request->only([
                'name',
                'description',
                'owner_id',
                'update_status'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'description' => 'required',
                'owner_id' => 'nullable|integer|exists:owners,id',

            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Map validated data
            $payload = [
                'name' => $data['name'],
                'description' => $data['description'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::id(),
            ];

            # Create or update record
            $brand = Owner::updateOrCreate(
                ['id' => $data['owner_id'] ?? null],
                $payload
            );

            # Return if fail
            if (!$brand) {
                return Utility::apiError('Failed to save brand', [], 221);
            }

            # Prepare message
            $message = isset($data['owner_id']) ? 'Updated successfully' : 'Created successfully';

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getOwner(Request $request)
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
            $query = Owner::with('branch:id,name')->whereNull('deleted_at');

            # Apply free-text search
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%")
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
                    'description' => 'Description',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];
                $filename = 'owner' . now()->format('Ymd_His') . '.xlsx';
                return Excel::download(new Export($query, $columns), $filename);
            }

            # Paginate results
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $notificationData = $query->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Owner list fetched successfully', $notificationData, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch notifications', [
                'exception' => $ex->getMessage()
            ]);
        }
    }

    public function deleteOwner(Request $request)
    {
        try {

            # Request id
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:owners,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Soft delete record
            $deleted = Owner::where('id', $data['id'])->delete();

            # Retunr if fail
            if (!$deleted) {
                return Utility::apiError('Failed to delete brand', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Owner delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting brand.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

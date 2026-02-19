<?php

namespace App\Http\Controllers\Configuration\Principal;

use App\Exports\PrincipalExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Principal;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PrincipalController extends Controller
{
    public function __construct() {}

    public function addUpdatePrincipal(Request $request)
    {
        try {
            // Extract fields
            $data = $request->only([
                'type',
                'type_id',
                'update_status',
                'principal_id',
            ]);

            // Validation rules
            $validator = Validator::make($data, [
                'type' => 'required',
                'type_id' => 'required|exists:principal_types,id',
                'principal_id' => 'nullable|integer|exists:principals,id',

            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Data mapping
            $arr = [
                'type' => $data['type'],
                'type_id' => $data['type_id'],
                'user_id' => Auth::id(),
                'branch_id' => Auth::user()['branch_id'],
            ];

            // Update or create
            $principal = Principal::updateOrCreate(
                ['id' => $data['principal_id'] ?? null],
                $arr
            );

            // Return if fail
            if (! $principal) {
                return Utility::apiError('Failed to save principal ', [], 221);
            }

            // Message define
            $message = $data['principal_id']
                ? ' updated successfully'
                : ' created successfully';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in principal ', ['exception' => $ex->getMessage()]);
        }
    }

    public function getPrincipal(Request $request)
    {
        try {
            // Get specific fields
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
                    'type' => 'Principal Name',
                    'principalType.type' => 'Principal Type',
                    'branch.name' => 'Branch Name',
                    'created_at' => 'Date',
                ];

                $filename = 'principal_'.now()->format('Ymd_His').'.xlsx';

                // Use queued export (async like SourceExport)
                (new PrincipalExport($data, $columns, Principal::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Load query with relationships
            $query = Principal::with([
                'branch:id,name',
                'principalType:id,type',
            ])->whereNull('deleted_at');

            // Global free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('type', 'like', "%$search%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%$search%"))
                        ->orWhereHas('principalType', fn ($p) => $p->where('type', 'like', "%$search%"));
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

            // Pagination
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $PrincipalData = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('List principal', $PrincipalData, 200);

        } catch (Exception $ex) {
            Log::error('Principal fetch error: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);

            return Utility::apiError('Something went wrong in principal', [
                'exception' => $ex->getMessage(),
            ]);
        }
    }

    public function deletePrincipal(Request $request)
    {
        try {
            // Get requested fields
            $data = $request->only(['id']);

            // Validate fields
            $validator = Validator::make($data, [
                'id' => [
                    'required',
                    'integer',
                    'exists:principals,id',
                    function ($attribute, $value, $fail) {
                        $exists = Product::where('principal_id', $value)->exists();

                        if ($exists) {
                            $fail('This principal is already assigned to a product.');
                        }
                    },
                ],
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Delete courier
            $records = Principal::where('id', $data['id'])->delete();
            if (! $records) {
                return Utility::apiError('Fail to delete principal  !', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Principal delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting principal .', ['exception' => $ex->getMessage()], 500);
        }
    }
}

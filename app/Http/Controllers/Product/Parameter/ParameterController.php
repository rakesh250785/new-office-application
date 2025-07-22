<?php

namespace App\Http\Controllers\Product\Parameter;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Exports\Export;
use App\Models\Parameter;
use App\Helpers\Utility;
use Exception;
use Log;

class ParameterController extends Controller
{
    public function addUpdateParameter(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only([
                'parameter_id',
                'parameter_name',
                'column_name',
                'old_column_name',
                'update_status',
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'parameter_name' => 'required|string',
                'column_name' => 'required|string',
                'parameter_id' => 'nullable|numeric',
                'update_status' => 'nullable|boolean',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Payload
            $payload = [
                'parameter_name' => $data['parameter_name'],
                'column_name' => $data['column_name'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::user()['id'],
            ];

            # Add /update parameter
            $parameter = Parameter::updateOrCreate(
                ['id' => $data['parameter_id'] ?? null],
                $payload
            );

            # Return if error 
            if (!$parameter) {
                return Utility::apiError('Failed to save parameter.', [], 221);
            }

            # Update schema
            // if (!empty($data['parameter_id']) && !empty($data['old_column_name']) && $data['old_column_name'] !== $data['column_name']) {
            //     if (Schema::hasColumn('product', $data['old_column_name'])) {
            //         Schema::table('product', function (Blueprint $table) use ($data) {
            //             $table->dropColumn($data['old_column_name']);
            //         });
            //     }
            // }

            // # Added new column
            // if (!Schema::hasColumn('product', $data['column_name'])) {
            //     Schema::table('product', function (Blueprint $table) use ($data) {
            //         $table->string($data['column_name'], 200)->after('hsn_no')->nullable();
            //     });
            // }

            # Prepare message
            $message = $data['parameter_id'] ? 'updated successfully.' : 'created successfully.';

            # Return column
            return Utility::apiSuccess($message, $parameter, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving parameter', ['exception' => $ex->getMessage()]);
        }
    }

    public function getParameter(Request $request)
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
            $query = Parameter::with('branch:id,name')->whereNull('deleted_at');

            # Apply free-text search
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('parameter_name', 'like', "%$search%")
                        ->orWhere('column_name', 'like', "%$search%")
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
                    'parameter_name' => 'Parameter Name',
                    'column_name' => 'Column Name',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];
                $filename = 'parameter' . now()->format('Ymd_His') . '.xlsx';
                return Excel::download(new Export($query, $columns), $filename);
            }

            # Paginate results
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $notificationData = $query->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Notification list fetched successfully', $notificationData, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch notifications', [
                'exception' => $ex->getMessage()
            ]);
        }
    }

    /**
     * Delete a parameter.
     */
    public function deleteParameter(Request $request)
    {
        try {

            # Get specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|numeric'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Get existing parameter
            $parameter = Parameter::find($data['id']);

            # Return if not found
            if (!$parameter) {
                return Utility::apiError('Parameter not found.', [], 404);
            }

            # Get column info
            $columnName = $parameter['column_name'];

            # Delete param
            $parameter->delete();

            # Update schema of product
            if (Schema::hasColumn('product', $columnName)) {
                Schema::table('product', function (Blueprint $table) use ($columnName) {
                    $table->dropColumn($columnName);
                });
            }

            # Return response
            return Utility::apiSuccess('deleted successfully.', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting parameter', ['exception' => $ex->getMessage()]);
        }
    }
}

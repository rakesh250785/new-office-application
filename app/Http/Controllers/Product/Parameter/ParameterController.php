<?php

namespace App\Http\Controllers\Product\Parameter;

use App\Exports\Export;
use App\Exports\ParameterExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class ParameterController extends Controller
{
    public function addUpdateParameter(Request $request)
    {
        try {
            // Request specific fields
            $data = $request->only([
                'parameter_id',
                'parameter_name',
                'column_name',
                'old_column_name',
                'update_status',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'parameter_name' => 'required|string',
                'column_name' => 'required|string',
                'parameter_id' => 'nullable|numeric',
                'update_status' => 'nullable|boolean',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Payload
            $payload = [
                'parameter_name' => $data['parameter_name'],
                'column_name' => $data['column_name'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::user()['id'],
            ];

            // Add /update parameter
            $parameter = Parameter::updateOrCreate(
                ['id' => $data['parameter_id'] ?? null],
                $payload
            );

            // Return if error
            if (! $parameter) {
                return Utility::apiError('Failed to save parameter.', [], 221);
            }

            // Update schema
            if (! empty($data['parameter_id']) && ! empty($data['old_column_name']) && $data['old_column_name'] !== $data['column_name']) {
                if (Schema::hasColumn('products', $data['old_column_name'])) {
                    Schema::table('products', function (Blueprint $table) use ($data) {
                        $table->dropColumn($data['old_column_name']);
                    });
                }
            }

            // Added new column
            if (! Schema::hasColumn('products', $data['column_name'])) {
                Schema::table('products', function (Blueprint $table) use ($data) {
                    $table->string($data['column_name'], 200)->after('hsn_no')->nullable();
                });
            }

            // Prepare message
            $message = $data['parameter_id'] ? 'updated successfully.' : 'created successfully.';

            // Return column
            return Utility::apiSuccess($message, $parameter, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error saving parameter', ['exception' => $ex->getMessage()]);
        }
    }

    public function getParameter(Request $request)
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

            // Export as Excel (async queue)
            if (! empty($data['download'])) {
                $columns = [
                    'parameter_name' => 'Parameter Name',
                    'column_name' => 'Column Name',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'parameter_'.now()->format('Ymd_His').'.xlsx';

                // Queue async export (safe for big data)
                (new ParameterExport($data, $columns, Parameter::class, Auth::id()))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Base query with branch relationship
            $query = Parameter::with('branch:id,name')->whereNull('deleted_at');

            // Apply free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('parameter_name', 'like', "%$search%")
                        ->orWhere('column_name', 'like', "%$search%")
                        ->orWhereHas('branch', function ($b) use ($search) {
                            $b->where('name', 'like', "%$search%");
                        });
                });
            }

            // Filter by branches
            if (! empty($data['branch_list'])) {
                $query->where('branch_id', $data['branch_list']);
            }

            // Filter by date range
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            if (Utility::checkViewPermission('parameter')) {
                $query->where('user_id', Auth::id());
            }

            // Paginate results
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $parameterData = $query->orderByDesc('id')->paginate($perPage);

            // Return response
            return Utility::apiSuccess('Parameter list fetched successfully', $parameterData, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch parameters', [
                'exception' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * Delete a parameter.
     */
    public function deleteParameter(Request $request)
    {
        try {
            $data = $request->only(['id']);

            // Basic validation: id must exist in parameters
            $validator = Validator::make($data, [
                'id' => ['required', 'integer', 'exists:parameters,id'],
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Fetch parameter
            $parameter = Parameter::find($data['id']);
            if (! $parameter) {
                return Utility::apiError('Parameter not found.', [], 404);
            }

            $columnName = $parameter?->column_name;

            if (Schema::hasColumn('products', $columnName)) {

                $used = Product::whereNotNull($columnName)
                    ->where($columnName, '<>', '')
                    ->exists();

                if ($used) {
                    return Utility::apiError(
                        'Validation failed',
                        ['id' => ['This Parameter is already assigned to a product.']],
                        221
                    );
                }
            }

            $parameter->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error deleting parameter', ['exception' => $ex->getMessage()], 500);
        }
    }
}

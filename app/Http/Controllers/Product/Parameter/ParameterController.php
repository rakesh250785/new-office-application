<?php

namespace App\Http\Controllers\Product\Parameter;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Parameter;
use App\Helpers\Utility;
use Exception, Log;

class ParameterController extends Controller
{
    public function addUpdateParameter(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['id', 'name', 'column_name', 'old_column_name']);

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string',
                'column_name' => 'required|string',
                'id' => 'nullable|numeric',
                'old_column_name' => 'nullable|sometimes'
            ]);

            # Retunr validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Payload info
            $payload = [
                'name' => $data['name'],
                'column_name' => $data['column_name'],
                'branch_id' => Auth::user()->branch_id,
            ];

            # Update or create
            $parameter = Parameter::updateOrCreate(
                ['id' => $data['id'] ?? null],
                $payload
            );

            # Return if fail
            if (!$parameter) {
                return Utility::apiError('Failed to save parameter.', [], 221);
            }

            # Update column info
            if (!empty($data['id']) && !empty($data['old_column_name']) && $data['old_column_name'] !== $data['column_name']) {
                if (Schema::hasColumn('product', $data['old_column_name'])) {
                    Schema::table('product', function (Blueprint $table) use ($data) {
                        $table->dropColumn($data['old_column_name']);
                    });
                }
            }

            # Update if not exist
            if (!Schema::hasColumn('product', $data['column_name'])) {
                Schema::table('product', function (Blueprint $table) use ($data) {
                    $table->string($data['column_name'], 200)->after('hsn_no')->nullable();
                });
            }

            # Prepare message
            $message = !empty($data['id']) ? 'Parameter updated successfully.' : 'Parameter created successfully.';

            # Return response
            return Utility::apiSuccess($message, $parameter, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving parameter', ['exception' => $ex->getMessage()]);
        }
    }

    public function getParameterList(Request $request)
    {
        try {
            # Get specif fields
            $data = $request->only(['per_page', 'search']);

            # Set pagination
            $perPage = $data['per_page'] ?? 10;

            # Get records
            $data = Parameter::whereNull('deleted_at')->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Parameter list fetched.', $data, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching parameters', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteParameter(Request $request)
    {
        try {
            # Get sepcific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|numeric'
            ]);

            # Validation rule
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete parameter
            $parameter = Parameter::find($data['id'])->delete();

            # Return if fail
            if (!$parameter) {
                return Utility::apiError('Fail to  delete parameter.', [], 404);
            }

            # Delete colomn schema
            if (Schema::hasColumn('product', $parameter['column_name'])) {
                Schema::table('product', function (Blueprint $table) use ($parameter) {
                    $table->dropColumn($parameter['column_name']);
                });
            }

            # Return response
            return Utility::apiSuccess('Parameter deleted successfully.', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting parameter', ['exception' => $ex->getMessage()]);
        }
    }
}

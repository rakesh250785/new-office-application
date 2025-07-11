<?php

namespace App\Http\Controllers\Api\Source;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Source;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class SourceController extends Controller
{
    public function __construct() {
    }

    public function addUpdteSource(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['name', 'id']);

            # Validation rule
            $validator = Validator::make($request->all(), [
                'source_name' => 'required|string|max:255',
                'id' => 'nullable|integer|exists:sources,id',
            ]);

            # Validation rule
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Check for update condition
            $isUpdate = !empty($data['id']);

            # Prepare data
            $data = [
                'name' => $data['name'] ?? null,
                'user_id' => Auth::id(),
            ];

            if ($isUpdate) {
                $data['updated_at'] = Carbon::now();
            } else {
                $data['branch_id'] = Auth::user()->branch_id;
                $data['created_at'] = Carbon::now();
                $data['deleted_at'] = null;
            }

            # Update or create 
            $source = Source::updateOrCreate(
                ['id' => $data['id']],
                $data
            );

            # Prepare message
            $msg = $isUpdate ? 'Source updated successfully' : 'Source created successfully';

            # Return response
            return Utility::apiSuccess($msg, $source, $isUpdate ? 200 : 221);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving source', ['exception' => $ex->getMessage()]);
        }
    }

    public function getSources(Request $request)
    {
        try {
            # Get specific fields
            $perPage = $request->only(['per_page', 'page', 'search']);

            # Get list
            $sources = Source::whereNull('deleted_at')->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Source list fetched', $sources, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching getSources', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteSource(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:sources,id',
            ]);

            # Validation rule
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete source
            $deleted = Source::where('id', $data['id'])->delete();

            # Return if fail
            if (!$deleted) {
                return Utility::apiError('Failed to delete source', [], 400);
            }

            # Return response
            return Utility::apiSuccess('Source deleted successfully');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting source', ['exception' => $ex->getMessage()]);
        }
    }
}

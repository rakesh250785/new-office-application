<?php

namespace App\Http\Controllers\ClientUser\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Owner;
use Illuminate\Http\Request;
use Exception, Log;
use App\Helpers\Utility;
class OwnerController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateOwner(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['id', 'name', 'desciption']);

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'desciption' => 'required|string|max:500',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Perform insert or update
            $owner = Owner::updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'name' => $data['name'],
                    'desciption' => $data['desciption'],
                    'branch_id' => Auth::user()->branch_id,
                ]
            );

            # Manage message
            $message = $data['id'] ? 'Owner updated successfully.' : 'Owner created successfully.';

            # Return api response
            return Utility::apiSuccess($message, ['owner_id' => $owner->id], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error occurred while saving owner.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getOwner(Request $request)
    {
        try {
            $data = $request->only(['search', 'per_page']);
            $perPage = $data['per_page'] ?? 10;

            # Base query with branch relation
            $owner = Owner::query();

            # Search across multiple fields
            if (!empty($data['search'])) {
                $search = $data['search'];
                $owner->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", [$search]);
                });
            }

            # Get paginated result
            $owner = $owner->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Owner list', $owner, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch getOwner.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteOwner(Request $request)
    {
        try {
            # Extract relevant fields
            $data = $request->only([
                'id',
            ]);

            #  Validation rule
            $validator = Validator::make($data, [
                'id' => 'required'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete user
            $records = Owner::where('id', $data['id'])->delete();

            # Return if fail
            if (!$records) {
                return Utility::apiError('Failed to owner user', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Owner deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error in  deleteOwner.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

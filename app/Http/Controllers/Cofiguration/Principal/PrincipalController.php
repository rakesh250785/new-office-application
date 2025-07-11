<?php

namespace App\Http\Controllers\Cofiguration\Principal;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Principal;
use App\Models\PrincipalType;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Exception;
use SebastianBergmann\CodeCoverage\Util\Percentage;
class PrincipalController extends Controller
{
    public function __construct(){
    }

    public function addUpdatePrincipal(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['principal_name', 'principal_type_id']);

            # Validation rule
            $validator = Validator::make($data, [
                'principal_name' => 'required',
                'principal_type_id' => 'required',
                'principal_id' => 'nullable||sometimes'
            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Get principal type
            $principalType = PrincipalType::where('id', $data['principal_type_id'])->first();

            # Return if not found
            if (!$principalType) {
                return Utility::apiError('Principal type not found', [], 221);
            }

            # Check type & status
            $status = false;
            $isAuthorized = 0;
            $fnStatus = false;
            $message = 'Principal added successfully';
            if ($principalType['name'] == 'Authorised') {
                $isAuthorized = 1;
                $status = 1;
            }

            # Prepare array
            $arr = [
                'name' => $data['principal_name'] ?? null,
                'type' => $data['type'] ?? null,
                'is_authorized' => $isAuthorized,
                'small_logo_image' => 'no image',
                'branch_id' => Auth::user()->branch_id,
                'status' => $status,
                'deleted_at' => null,
            ];

            # Update principal
            if (!empty($data['principal_id'])) {
                $message = 'Principal updated successfully';
                $fnStatus = Principal::where('id', $data['principal_id'])->update($arr);
            }

            # Add principal
            $fnStatus = Principal::create($arr);
            if (!$fnStatus) {
                return Utility::apiError('Fail to add principal', [], 221);
            }

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error occurred while action on addUpdatePrincipal', ['exception' => $ex->getMessage()]);
        }
    }

    public function getPrincipal(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['page', 'per_page', 'search']);

            # Get principal
            $principal = Principal::whereNull('deleted_at')->orderBy('id', 'desc')->paginate(10);

            # Return response
            return Utility::apiSuccess('List principal', $principal, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error occurred while action on getPrincipal', ['exception' => $ex->getMessage()]);
        }
    }

    public function deletePrincipals(Request $request, $id)
    {
        try {

            # Request specific fields
            $data = $request->only(['principal_id']);

            # Validation rule
            $validator = Validator::make($data, [
                'principal_id' => 'required',
            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete principal
            $status = Principal::where('id', $data['principal_id'])->delete();
            if (!$status) {
                return Utility::apiError('Fail to delete principal', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Principal deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::debug($ex);
            return Utility::apiError('Error occurred while action on deletePrincipals', ['exception' => $ex->getMessage()]);
        }
    }
}

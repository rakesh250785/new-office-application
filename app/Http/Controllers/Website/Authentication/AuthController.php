<?php

namespace App\Http\Controllers\Website\Authentication;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\WebUser;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{   
    public function addUpdateWebUser(Request $request)
    {
        try {
            $id = $request->input('id');

            $rules = [
                'id' => 'nullable|integer|exists:web_users,id',
                'name' => 'required|string|max:128',
                'last_name' => 'required|string|max:128',
                'email' => [
                    'required',
                    'email',
                    'max:191',
                    Rule::unique('web_users', 'email')->ignore($id),
                ],
                'mobile' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('web_users', 'mobile')->ignore($id),
                ],
                'gender' => 'nullable|in:male,female,other',
            ];

            if (empty($id)) {
                $rules['password'] = 'required|string|min:8';
            } elseif ($request->filled('password')) {
                $rules['password'] = 'string|min:8';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $vendor = $request->filled('id') ? WebUser::find($request->input('id')) : new WebUser;

            if (! $vendor) {
                return Utility::apiError('Web user not found', [], 404);
            }

            $vendor->name = $request->input('name');
            $vendor->last_name = $request->input('last_name');
            $vendor->email = $request->input('email');
            $vendor->mobile = $request->input('mobile');
            $vendor->gender = $request->input('gender', null);
            $vendor->user_id = Auth::id() ?? null;

            if ($request->filled('password')) {
                $vendor->password = Hash::make($request->input('password'));
            }

            $vendor->save();

            $msg = $request->filled('id') ? 'updated successfully' : 'created successfully';

            return Utility::apiSuccess($msg, $vendor, 200);

        } catch (Exception $ex) {
            Log::error('Web add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getWebUser(Request $request, $id = null)
    {
        try {
            if ($id) {
                $vendor = WebUser::find($id);
                if (! $vendor) {
                    return Utility::apiError('Web User not found', [], 404);
                }

                return Utility::apiSuccess('Web User fetched successfully', $vendor, 200);
            }

            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = WebUser::query();

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('gender', 'like', '%'.$search.'%');
                });
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $result = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('list fetched successfully', $result, 200);

        } catch (Exception $ex) {
            Log::error('Web User fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch vendor details', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteWebUser(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:web_users,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $vendor = WebUser::find($request->input('id'));
            if (! $vendor) {
                return Utility::apiError('Web User not found', [], 404);
            }

            $deleted = $vendor->delete();

            if (! $deleted) {
                return Utility::apiError('Failed to delete vendor', [], 221);
            }

            return Utility::apiSuccess('deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('Web User delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting vendor', ['exception' => $ex->getMessage()], 500);
        }
    }
}

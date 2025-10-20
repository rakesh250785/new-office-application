<?php

namespace App\Http\Controllers\ClientUser\User;

use App\Exports\Export;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessUser;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct() {}

    public function addUpdateUser(Request $request)
    {
        try {

            // Request specific fields
            $data = $request->only([
                'name',
                'last_name',
                'user_name',
                'password',
                'email',
                'cc_email',
                'name',
                'branch_id',
                'user_id',
                'role_id',
                'team_type',
            ]);

            // Check if update user
            $isUpdate = ! empty($data['user_id']);

            // Define rule
            $rules = [
                'team_type' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'user_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users', 'user_name')->ignore($data['user_id']),
                ],
                'password' => $isUpdate ? 'nullable|min:6' : 'required|min:6',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($data['user_id']),
                ],
                'cc_email' => 'required|string',
                'branch_id' => 'required|integer',
                'role_id' => 'required|integer',
            ];

            // Validate user
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Make payload
            $userPayload = [
                'team_type' => $data['team_type'],
                'name' => $data['name'],
                'last_name' => $data['last_name'],
                'user_name' => $data['user_name'],
                'email' => $data['email'],
                'cc_email' => $data['cc_email'],
                'branch_id' => $data['branch_id'],
                'role_id' => $data['role_id'],
            ];

            // Bcrypt password
            if (! empty($data['password']) && $data['password'] != '********') {
                $userPayload['password'] = bcrypt($data['password']);
            }

            // Add update user
            $user = User::updateOrCreate(
                ['id' => $data['user_id'] ?? 0],
                $userPayload
            );

            // Fail to add user
            if (! $user) {
                return Utility::apiError('Fail to add user', [], 221);
            }

            if (! $isUpdate) {
                $branchName = Branch::where('id', $data['branch_id'])->first();
                $cc = array_map('trim', explode(',', $data['cc_email']));
                $mailData = [
                    'email' => $data['email'],
                    'cc' => $cc,
                    'admin_info' => [
                        'admin_fname' => $user['first_name'],
                        'admin_lname' => $user['last_name'],
                        'username' => $user['username'],
                        'password' => $data['password'],
                        'email' => $user['email'],
                        'branch_name' => $branchName['name'] ?? null,
                        'admin_rights' => 1,
                        'created_at' => $user['created_at'],
                    ],
                ];

                // Dispatch email
                // dispatch(new ProcessUser($mailData));
            }

            // Prepare message
            $message = $isUpdate ? 'updated successfully.' : 'created successfully.';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error('addOrUpdateUser Error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getUser(Request $request)
    {
        try {
            // Get specific fields
            $data = $request->only(['search', 'per_page', 'branch_list', 'download', 'start_date', 'end_date']);

            // Base query with branch relation
            $query = User::with('branch:id,name');

            // Filter by branch_id
            if (! empty($data['branch_list'])) {
                $query->whereIn('branch_id', $data['branch_list']);
            }

            // Search across multiple fields
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhereDate('created_at', $search)
                        ->orWhereHas('branch', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by date range
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Export logic
            if (! empty($data['download'])) {
                $columns = [
                    'name' => 'First Name',
                    'last_name' => 'Last Name',
                    'user_name' => 'User Name',
                    'email' => 'Email Id',
                    'cc_email' => 'CC Email',
                    'branch.name' => 'Branch Name',
                    'created_at' => 'Date',
                ];

                $filename = 'user'.now()->format('Ymd_His').'.xlsx';

                return Excel::download(new Export($query, $columns), $filename);
            }

            // Get paginated result
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $users = $query->orderByDesc('id')->paginate($perPage);

            // Return response
            return Utility::apiSuccess('User list', $users, 200);
        } catch (Exception $ex) {
            Log::error('getUser error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch users.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        try {
            // Extract relevant fields
            $data = $request->only([
                'id',
            ]);

            //  Validation rule
            $validator = Validator::make($data, [
                'id' => 'required',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Delete user
            $records = User::where('id', $data['id'])->delete();

            // Return if fail
            if (! $records) {
                return Utility::apiError('Failed to delete user', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error in  deleteUser.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

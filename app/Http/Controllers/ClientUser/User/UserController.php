<?php

namespace App\Http\Controllers\ClientUser\User;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessUser;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception, Log;
use App\Helpers\Utility;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateUser(Request $request)
    {
        try {
            # Extract relevant fields
            $data = $request->only([
                'id',
                'first_name',
                'last_name',
                'username',
                'password',
                'email',
                'cc_email',
                'name',
                'branch_id',
                'permission'
            ]);

            $isUpdate = !empty($data['id']);

            # Validation rules
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', $isUpdate ? Rule::unique('users', 'user_name')->ignore($data['id']) : 'unique:users,user_name'],
                'password' => $isUpdate ? 'nullable|min:6' : 'required|min:6',
                'email' => ['required', 'email', $isUpdate ? Rule::unique('users', 'email')->ignore($data['id']) : 'unique:users,email'],
                'cc_email' => 'required|string',
                'name' => [$isUpdate ? 'nullable' : 'required', Rule::unique('roles', 'name')->ignore($request->role_id)],
                'branch_id' => 'required|integer',
                'permission' => 'required|array',
            ];

            # Return validation error
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Permissions
            $permissionsInput = $data['permission'];
            $permissionMap = Permission::all()->groupBy('identifier');
            $newPermissions = [];
            $existingPermissionIds = [];

            foreach ($permissionsInput as $identifier => $names) {
                $existing = $permissionMap->get($identifier, collect())->pluck('name')->toArray();
                foreach ($names as $name) {
                    if (in_array($name, $existing)) {
                        $perm = Permission::where('name', $name)->where('identifier', $identifier)->first();
                        if ($perm) {
                            $existingPermissionIds[] = $perm->id;
                        }
                    } else {
                        $newPermissions[] = [
                            'name' => $name,
                            'identifier' => $identifier,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }
            }

            # Insert any new permissions
            if (!empty($newPermissions)) {
                Permission::insert($newPermissions);
                $newIds = Permission::whereIn('name', array_column($newPermissions, 'name'))
                    ->pluck('id')
                    ->toArray();
                $existingPermissionIds = array_merge($existingPermissionIds, $newIds);
            }

            if ($isUpdate) {
                # Update user
                $user = User::findOrFail($data['id']);
                $user->first_name = $data['first_name'];
                $user->last_name = $data['last_name'];
                $user->user_name = $data['username'];
                $user->email = $data['email'];
                $user->cc_email = $data['cc_email'];
                $user->branch_id = $data['branch_id'];
                if (!empty($data['password'])) {
                    $user->password = bcrypt($data['password']);
                }
                $user->save();

                if (!empty($data['name'])) {
                    $role = Role::firstOrCreate(['name' => $data['name']]);
                    $role->permissions()->sync($existingPermissionIds);
                    $user->roles()->sync([$role->id]);
                }

                $message = 'User updated successfully.';
            } else {
                # Create role
                $role = Role::create(['name' => $data['name']]);
                $role->permissions()->sync($existingPermissionIds);

                # Create user
                $user = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'user_name' => $data['username'],
                    'password' => bcrypt($data['password']),
                    'email' => $data['email'],
                    'branch_id' => $data['branch_id'],
                    'cc_email' => $data['cc_email'],
                    'dt_created' => Carbon::now(),
                ]);
                $user->roles()->attach($role->id);

                # Email job
                $branchName = Branch::pluck('name', 'id')[$data['branch_id']] ?? 'Unknown';
                $cc = array_map('trim', explode(',', $data['cc_email']));

                $mailData = [
                    'email' => $data['email'],
                    'cc' => $cc,
                    'admin_info' => [
                        'admin_fname' => $user->first_name,
                        'admin_lname' => $user->last_name,
                        'username' => $user->user_name,
                        'password' => $data['password'],
                        'email' => $user->email,
                        'branch_name' => $branchName,
                        'admin_rights' => 1,
                        'created_at' => Carbon::now(),
                    ],
                ];
                dispatch(new ProcessUser($mailData));

                $message = 'User created successfully.';
            }

            return Utility::apiSuccess($message, ['user' => $user], 200);
        } catch (Exception $ex) {
            Log::error('addOrUpdateUser Error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $data = $request->only(['search', 'per_page', 'branch_id']);
            $perPage = $data['per_page'] ?? 10;

            # Base query with branch relation
            $query = User::with('branch');

            # Filter by branch_id
            if (!empty($data['branch_id'])) {
                $query->where('branch_id', $data['branch_id']);
            }

            # Search across multiple fields
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhereDate('dt_created', $search)
                        ->orWhereHas('branch', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            # Get paginated result
            $users = $query->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('User list', $users, 200);
        } catch (Exception $ex) {
            Log::error('getUser error: ' . $ex->getMessage());
            return Utility::apiError('Failed to fetch users.', ['exception' => $ex->getMessage()], 500);
        }
    }


    public function deleteUser(Request $request)
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
            $records = User::where('id', $data['id'])->delete();

            # Return if fail
            if (!$records) {
                return Utility::apiError('Failed to delete user', [], 221);
            }

            # Return response
            return Utility::apiSuccess('User deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error in  deleteUser.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

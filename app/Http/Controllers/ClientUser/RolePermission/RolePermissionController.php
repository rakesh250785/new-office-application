<?php

namespace App\Http\Controllers\ClientUser\RolePermission;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Validator;

class RolePermissionController extends Controller
{
    public function addUpdateRole(Request $request)
    {
        try {

            // Request specific fields
            $data = $request->only(['id', 'name', 'permissions']);

            // Ids
            $id = isset($data['id']) ? trim($data['id']) : null;

            // Define rule
            $rules = [
                'name' => 'required|unique:roles,name'.($id ? ','.$id : ''),
                'permissions' => 'required|array',
            ];

            // Validation rule
            $validate = Validator::make($data, $rules);

            // Return if fails
            if ($validate->fails()) {
                return Utility::apiError($validate->errors()->first(), 221);
            }

            // Update or create
            $role = Role::updateOrCreate(
                ['id' => $data['id']],
                ['name' => $data['name'], 'branch_id' => Auth::user()['branch_id']],

            );

            // Map permission
            $permissionIds = [];
            foreach ($data['permissions'] as $module => $codes) {
                foreach ($codes as $code) {
                    $perm = Permission::where('module_name', $module)->where('name', $code)->first();
                    if ($perm) {
                        $permissionIds[] = $perm->id;
                    }
                }
            }

            // Sync permission with role
            $role->permissions()->sync($permissionIds);

            // Return response
            return Utility::apiSuccess($data['id'] ? ' updated successfully' : ' created successfully');
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to storeRole.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function listPermissions(Request $request)
    {
        try {
            // Get permission list
            $permissions = Permission::orderBy('id')->get();
            $grouped = $permissions->groupBy('module_name')->map(function ($perms) {
                return $perms->pluck('name')->values()->toArray();
            });

            // Return response
            return Utility::apiSuccess('Permissions fetched successfully', $grouped, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch permission list.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function listRolesWithPermissions(Request $request)
    {
        try {

            // Request specific fields
            $data = $request->only([
                'page',
                'per_page',
                'search',
                'start_date',
                'end_date',
                'branch_list',
            ]);

            // Config pagination
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $page = $data['page'] ?? 1;

            // Get role with permissions
            $query = Role::with('permissions');

            // Optional search by role name
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where('name', 'like', "%{$search}%");
            }

            // Filter by date range
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Filter by branch_id
            if (! empty($data['branch_list'])) {
                $query->where('branch_id', $data['branch_list']);
            }

            // Get records
            $roles = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            // Transform to group permissions by module_name
            $roles->getCollection()->transform(function ($role) {
                $groupedPermissions = $role->permissions->groupBy('module_name')->map(function ($perms) {
                    return $perms->pluck('name')->values();
                });

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'created_at' => $role?->created_at?->format('d/m/Y'),
                    'permissions' => $groupedPermissions,
                ];
            });

            // Return response
            return Utility::apiSuccess('Roles list fetched successfully', $roles, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch roles list.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteRole(Request $request)
    {

        try {
            // Request specific fields
            $data = $request->only([
                'id',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => 'required',

            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }
            // Find role
            $role = Role::find($data['id'])->delete();

            // Return if fail to delete
            if (! $role) {
                return Utility::apiError('Fail to delete', 221);
            }

            // Retunr response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to updateRole.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function assignRole(Request $request)
    {
        try {
            $v = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'role' => 'required|exists:roles,name',
            ]);
            if ($v->fails()) {
                return Utility::apiError($v->errors()->first(), 422);
            }

            $user = User::find($request->user_id);
            $user->syncRoles([$request->role]);

            return Utility::apiSuccess($user->roles, 'Role assigned');
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to updatePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function assignPermission(Request $request)
    {
        try {
            $v = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
                'permissions' => 'required|array',
            ]);
            if ($v->fails()) {
                return Utility::apiError($v->errors()->first(), 422);
            }

            $role = Role::find($request->role_id);
            $role->syncPermissions($request->permissions);

            return Utility::apiSuccess($role->permissions, 'Permissions assigned');
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to updatePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function rolesPermissionsOverview()
    {
        try {
            $authUserId = Auth::id();

            $user = User::select(['id', 'role_id', 'name'])
                ->with(['role.permissions:id,name,module_name'])
                ->where('id', $authUserId)
                ->first();

            if (! $user) {
                return Utility::apiError('User not found', [], 404);
            }

            $permissions = $user->role->permissions
                ->groupBy('module_name')
                ->map(fn ($perms) => $perms->pluck('name', 'name'));

            $response = [
                'id' => $user->id,
                'role_id' => $user->role_id,
                'name' => $user->name,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'guard_name' => $user->role->guard_name,
                    'branch_id' => $user->role->branch_id,
                    'created_at' => $user->role->created_at,
                    'updated_at' => $user->role->updated_at,
                    'permissions' => $permissions,
                ],
            ];

            return Utility::apiSuccess(
                'User permission list',
                $response,
                200
            );

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError(
                'Failed to updatePermission.',
                ['exception' => $ex->getMessage()],
                500
            );
        }
    }
}

<?php

namespace App\Http\Controllers\ClientUser\RolePermission;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Helpers\Utility;
use Validator;
use Exception, Log;
class RolePermissionController extends Controller
{
    # ---- Role CRUD ----
    public function listRoles()
    {
        try {
            return Utility::apiSuccess(Role::all(), 'Roles fetched');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch listRoles.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function storeRole(Request $request)
    {
        try {
            $v = Validator::make($request->all(), ['name' => 'required|unique:roles']);
            if ($v->fails())
                return Utility::apiError($v->errors()->first(), 422);
            $role = Role::create(['name' => $request->name]);
            return Utility::apiSuccess($role, 'Role created');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to storeRole.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function updateRole(Request $request, $id)
    {
        try {
            $role = Role::find($id);
            if (!$role)
                return Utility::apiError('Not found', 404);
            $v = Validator::make($request->all(), ['name' => 'required|unique:roles,name,' . $id]);
            if ($v->fails())
                return Utility::apiError($v->errors()->first(), 422);
            $role->update(['name' => $request->name]);
            return Utility::apiSuccess($role, 'Role updated');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to updateRole.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteRole($id)
    {

        try {
            $role = Role::find($id);
            if (!$role)
                return Utility::apiError('Not found', 404);
            $role->delete();
            return Utility::apiSuccess([], 'Role deleted');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to updateRole.', ['exception' => $ex->getMessage()], 500);
        }
    }

    # ---- Permission CRUD ----
    public function listPermissions()
    {
        try {
            return Utility::apiSuccess(Permission::all(), 'Permissions fetched');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to listPermissions.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function storePermission(Request $request)
    {
        try {
            $v = Validator::make($request->all(), ['name' => 'required|unique:permissions']);
            if ($v->fails())
                return Utility::apiError($v->errors()->first(), 422);
            $perm = Permission::create(['name' => $request->name]);
            return Utility::apiSuccess($perm, 'Permission created');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to storePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function updatePermission(Request $request, $id)
    {
        try {
            $perm = Permission::find($id);
            if (!$perm)
                return Utility::apiError('Not found', 404);
            $v = Validator::make($request->all(), ['name' => 'required|unique:permissions,name,' . $id]);
            if ($v->fails())
                return Utility::apiError($v->errors()->first(), 422);
            $perm->update(['name' => $request->name]);
            return Utility::apiSuccess($perm, 'Permission updated');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to updatePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deletePermission($id)
    {
        try {
            $perm = Permission::find($id);
            if (!$perm)
                return Utility::apiError('Not found', 404);
            $perm->delete();
            return Utility::apiSuccess([], 'Permission deleted');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to updatePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }

    # ---- Assign ----
    public function assignRole(Request $request)
    {
        try {
            $v = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'role' => 'required|exists:roles,name'
            ]);
            if ($v->fails())
                return Utility::apiError($v->errors()->first(), 422);

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
                'permissions' => 'required|array'
            ]);
            if ($v->fails())
                return Utility::apiError($v->errors()->first(), 422);

            $role = Role::find($request->role_id);
            $role->syncPermissions($request->permissions);
            return Utility::apiSuccess($role->permissions, 'Permissions assigned');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to updatePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }

    # ---- Overview ----
    public function rolesPermissionsOverview()
    {
        try {
            $roles = Role::with('permissions')->get();
            $users = User::with(['roles', 'permissions'])->get();
            $data = [
                'roles' => $roles->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'permissions' => $r->permissions->pluck('name')
                ]),
                'users' => $users->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->roles->pluck('name'),
                    'permissions' => $u->permissions->pluck('name')
                ])
            ];
            return Utility::apiSuccess($data, 'Overview fetched');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to updatePermission.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

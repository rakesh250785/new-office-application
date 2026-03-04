<?php

namespace App\Http\Middleware;

use App\Helpers\Utility;
use App\Models\User;
use Auth;
use Closure;
use DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SingleWindowMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return Utility::apiError('Token not provided', [], 401);
        }
        $moduleName = str_replace('-', '_', $request->header('X-Page-URL'));
        $authUserId = Auth::id();

        if (! $authUserId) {
            return Utility::apiError('Permission denied', [], 403);
        }

        if (! empty($moduleName)) {
            $moduleExists = DB::table('permissions')
                ->where('module_name', $moduleName)
                ->exists();

            if ($moduleExists) {
                $hasPermission = User::whereKey($authUserId)
                    ->whereHas('role.permissions', function ($query) use ($moduleName) {
                        $query->whereIn('name', [
                            'view_'.$moduleName,
                            'edit_'.$moduleName,
                        ]);
                    })
                    ->exists();

                if (! $hasPermission) {
                    return Utility::apiError('Permission denied', [], 403);
                }
            }

        }

        try {

            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // valid token → normal flow
            if ($user->active_jwt !== $token) {
                return Utility::apiError(
                    'Unauthenticated.',
                    [],
                    401
                );
            }

        } catch (TokenExpiredException $e) {
            User::where('active_jwt', $token)->update([
                'active_jwt' => null,
            ]);

            return Utility::apiError(
                'Unauthenticated.',
                [],
                401
            );

        } catch (TokenInvalidException $e) {
            User::where('active_jwt', $token)->update([
                'active_jwt' => null,
            ]);

            return Utility::apiError(
                'Unauthenticated.',
                [],
                401
            );

        } catch (JWTException $e) {
            User::where('active_jwt', $token)->update([
                'active_jwt' => null,
            ]);

            return Utility::apiError(
                'Unauthenticated.',
                [],
                401
            );
        }

        return $next($request);
    }
}

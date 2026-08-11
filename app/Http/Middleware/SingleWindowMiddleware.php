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
    /**
     * Idle session timeout in minutes.
     */
    private const IDLE_TIMEOUT = 60;

    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return Utility::apiError('Token not provided', [], 401);
        }

        try {
            JWTAuth::setToken($token);

            $user = JWTAuth::authenticate();

            if (! $user) {
                return Utility::apiError('Unauthenticated.', [], 401);
            }

            /*
             * Check whether the user's session has been idle
             * for more than 1 hour.
             */
            if (
                $user->token_expires_at &&
                now()->greaterThanOrEqualTo($user->token_expires_at)
            ) {
                $user->update([
                    'active_jwt' => null,
                    'token_expires_at' => null,
                ]);

                return Utility::apiError(
                    'Session expired due to inactivity.',
                    [],
                    401
                );
            }

            /*
             * Single-window validation.
             *
             * If another login has replaced the active JWT,
             * reject this request.
             */
            if ($user->active_jwt !== $token) {
                return Utility::apiError(
                    'Unauthenticated.',
                    [],
                    401
                );
            }

            /*
             * User is active.
             *
             * Reset the idle timeout for another 1 hour.
             *
             * IMPORTANT:
             * Do this only after validating the active JWT.
             */
            $user->update([
                'token_expires_at' => now()->addMinutes(self::IDLE_TIMEOUT),
            ]);

            $moduleName = str_replace(
                '-',
                '_',
                $request->header('X-Page-URL') ?? ''
            );

            $authUserId = Auth::id();

            if (! $authUserId) {
                return Utility::apiError(
                    'Permission denied',
                    [],
                    403
                );
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
                        return Utility::apiError(
                            'Permission denied',
                            [],
                            403
                        );
                    }
                }
            }

        } catch (TokenExpiredException $e) {

            User::where('active_jwt', $token)->update([
                'active_jwt' => null,
                'token_expires_at' => null,
            ]);

            return Utility::apiError(
                'Unauthenticated.',
                [],
                401
            );

        } catch (TokenInvalidException $e) {

            User::where('active_jwt', $token)->update([
                'active_jwt' => null,
                'token_expires_at' => null,
            ]);

            return Utility::apiError(
                'Unauthenticated.',
                [],
                401
            );

        } catch (JWTException $e) {

            User::where('active_jwt', $token)->update([
                'active_jwt' => null,
                'token_expires_at' => null,
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

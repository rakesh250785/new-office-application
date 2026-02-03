<?php

namespace App\Http\Middleware;

use App\Helpers\Utility;
use App\Models\User;
use Closure;
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

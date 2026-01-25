<?php

namespace App\Http\Middleware;

use App\Helpers\Utility;
use App\Models\User;
use Closure;
use Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SingleWindowMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            /* ================= GET TOKEN ================= */
            $token = JWTAuth::getToken();

            if (! $token) {
                return Utility::apiError('Token not provided', [], 401);
            }

            /* ================= AUTHENTICATE TOKEN ================= */
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return Utility::apiError('User not authenticated', [], 401);
            }

            /* ================= SINGLE SESSION CHECK ================= */
            if (
                empty($user->active_jwt) ||
                ! hash_equals((string) $user->active_jwt, (string) $token)
            ) {
                return Utility::apiError(
                    'Token error OR You are already logged in on another device',
                    [],
                    409
                );
            }

        } catch (TokenExpiredException $e) {

            try {   
                // get payload even if token is expired
                $payload = JWTAuth::getPayload($token);
                logger('payloadddddddddddd');
                logger($payload);
                $userId = $payload->get('sub');
                logger('userIdiddddddddddddd');
                logger($userId);
                User::where('id', $userId)
                    ->update([
                        'active_jwt' => null,
                    ]);

            } catch (\Exception $e) {
                Log::error('Token exception error: '.$e);
            }

            return Utility::apiError(
                'Session expired. Please login again.',
                [],
                401
            );
        } catch (JWTException $e) {

            /* ================= INVALID TOKEN ================= */
            return Utility::apiError(
                'Invalid or malformed token',
                [],
                409
            );
        }

        return $next($request);
    }
}

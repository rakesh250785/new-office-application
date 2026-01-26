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
                    'You are logged in from another device',
                    [],
                    401
                );
            }

        } catch (TokenExpiredException $e) {

            logger("TokenExpiredException");
            // expired but readable → cleanup allowed
            try {
                $payload = JWTAuth::setToken($token)->getPayload();
                $userId = $payload->get('   ');

                User::where('id', $userId)->update([
                    'active_jwt' => null,
                ]);
            } catch (\Exception $e) {
                // ignore — nothing we can do
            }

            return Utility::apiError(
                'Session expired. Please login again.',
                [],
                401
            );

        } catch (TokenInvalidException $e) {
            logger("TokenInvalidException");
            // malformed token → cannot know user
            return Utility::apiError(
                'Invalid token',
                [],
                401
            );

        } catch (JWTException $e) {
            logger("JWTException");
            return Utility::apiError(
                'Authentication error',
                [],
                401
            );
        }

        return $next($request);
    }
}

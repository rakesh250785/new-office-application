<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends Controller
{

    public function apiLogin(Request $request)
    {
        try {
            # Get validation rule
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Attempt login
            $credentials = $request->only('email', 'password');
            if (!$token = JWTAuth::attempt($credentials)) {
                return Utility::apiError('Invalid credentials', [], 401);
            }

            # Return response
            return Utility::apiSuccess('Login successful', ['token' => $token], 200);
        } catch (Exception $ex) {
            Log::error('Login error: ' . $ex);
            return Utility::apiError('Unexpected error occurred', [], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {

            # Get token
            $token = JWTAuth::getToken();

            # If not token found
            if (!$token) {
                return Utility::apiError('Token not provided.', [], 221);
            }

            # Invalidate token
            JWTAuth::invalidate($token);

            # Return response
            return Utility::apiSuccess('Logout successful.', [], 200);
        } catch (JWTException $ex) {
            Log::error('Logout error: ' . $ex);
            return Utility::apiError('JWT Logout error: ' . $ex->getMessage(), [], 221);
        }
    }

}

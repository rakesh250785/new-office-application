<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends Controller
{
    
    public function apiLogin(Request $request)
    {
        try {
            # Get validation rule
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            # Attempt login
            $credentials = $request->only('email', 'password');
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }


            # Get user
            $user = Auth::user();

            # Return response
            return response()->json([
                'status'   => true,
                'message'  => 'Login successful.',
                'token'    => $token,
                'user'     => $user,
            ]);
        } catch (Exception $ex) {
            Log::error('Login error: ' . $ex);
            Log::error('Login error: ' . $ex->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error occurred.',
            ], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {
            # Logout user
            $user = auth();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }

            # Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => true,
                'message' => 'Logout successful.',
            ]);
        } catch (Exception $ex) {
            Log::error('Logout error: ' . $ex->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong during logout.',
            ], 500);
        }
    }

}

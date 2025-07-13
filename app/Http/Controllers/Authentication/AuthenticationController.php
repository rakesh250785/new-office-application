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

    public function logout(Request $request){
        try {
            # Logout from appications
            $email = \Auth::user()->email;
            $key = strtolower(trim('login_type_'.$email));
            \Cache::pull($key);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            #send user info to admin
            $localIP = file_get_contents("http://ipecho.net/plain");
            $userIp = $request->ip();
            $locationData = Location::get($userIp);

            if($locationData){
                $locationData->username = $email;
                $locationData->msg = "Below is the account details Logout Details";
                $locationData->localIP = $localIP;
                $send = new Login($locationData);
                Mail::to('info@chromatographyworld.com')->send($send);
            }
            
            return redirect()->route('login')->with('message', 'Logout successfully !');
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function getLogin(Request $request){
        try { 
            # Validate credentials
            $validator = Validator::make($request->all(),[
                'email' => 'required',
                "password"    => 'required',
            ]);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            # Check for alredy login user
            $key = strtolower(trim('login_type_'.$request->email));
            if(\Cache::has($key)){
                return redirect()->route('login')->with('message', 'Already logged in from other location');
            }
            # Authenticate user
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {

                $lifetime = config('session.lifetime');
                \Cache::put($key, true, $lifetime);
                $request->session()->regenerate();
                $localIP = file_get_contents("http://ipecho.net/plain");
                #send user info to admin
                $userIp = $request->ip();
                $locationData = Location::get($userIp);
                if($locationData){
                $locationData->username = $request->email;
                $locationData->msg = "Login Details";
                $locationData->localIP = $localIP;
                $send = new Login($locationData);
                Mail::to('info@chromatographyworld.com')->send($send);
                }
                if(Auth::user()->branch_id == 1){
                    return redirect()->route('dashboard')->with('message', 'Login done successfully.');
                }else{
                    return redirect()->route('dashboardBranch')->with('message', 'Login done successfully.');
                }
            }
            return back()->withErrors([
                'message' => 'The provided credentials do not match our records.',
            ]);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

}

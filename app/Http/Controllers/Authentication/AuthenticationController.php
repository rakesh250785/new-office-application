<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\SendLoginLogoutEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends Controller
{
    private function getUserLocation(string $ip): array
    {
        try {
            $position = Location::get('103.177.112.254');
            if ($position) {
                return [
                    'country' => $position->countryName ?? null,
                    'state' => $position->regionName ?? null,
                    'city' => $position->cityName ?? null,
                    'zip' => $position->zipCode ?? null,
                ];
            }
        } catch (Exception $ex) {
            Log::error('Login error: '.$ex);
        }

        return ['country' => null, 'state' => null, 'city' => null, 'zip' => null];
    }

    private function parseAgent(string $userAgent): array
    {
        try {
            // Get agent info
            $agent = new Agent;
            $agent->setUserAgent($userAgent);

            $browser = $agent->browser() ?: 'Unknown';
            $version = $agent->version($browser) ?: '';
            $platform = $agent->platform() ?: 'Unknown';
            $device = $agent->device() ?: ($agent->isDesktop() ? 'Desktop' : ($agent->isMobile() ? 'Mobile' : 'Unknown'));

            // Retunr agent info
            return [
                'browser' => trim($browser.' '.$version),
                'platform' => $platform,
                'device' => $device,
            ];
        } catch (Exception $e) {
            Log::warning('Agent parse failed: '.$e->getMessage());

            return ['browser' => 'Unknown', 'platform' => 'Unknown', 'device' => 'Unknown'];
        }
    }

    public function apiLogin(Request $request)
    {
        try {

            // Validate credential
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Return if fail
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            // Auth user
            $credentials = $request->only('email', 'password');
            if (! $token = JWTAuth::attempt($credentials)) {
                return Utility::apiError('Invalid credentials', [], 401);
            }

            // Get user after auth
            $user = Auth::user();
            $ip = $request->ip() ?? $request->server('REMOTE_ADDR') ?? 'unknown';
            $userAgent = $request->header('User-Agent', 'unknown');

            // Gather primitives before dispatching job
            $location = $this->getUserLocation($ip);
            $agentInfo = $this->parseAgent($userAgent);

            $time = Carbon::now();
            $details = [
                'username' => $user->name ?? null,
                'event' => 'Login',
                'ip' => $ip,
                'country' => $location['country'],
                'state' => $location['state'],
                'city' => $location['city'],
                'zip' => $location['zip'],
                'time' => $time->format('d-m-Y h:i:s A'),
                'time_short' => $time->toDateTimeString(),
                // 'to_email' => $user->email,
                'user_agent' => $userAgent,
                'browser' => $agentInfo['browser'],
                'platform' => $agentInfo['platform'],
                'device' => $agentInfo['device'],
                'to_email' => 'mkyadav59@gmail.com',
                'logo_url' => asset('appLogo/logo.png'),
            ];

            // Dispatch to queue (use queue name 'emails')
            SendLoginLogoutEmail::dispatch($details)->onQueue('emails');

            // Return resposne
            return Utility::apiSuccess('Login successful', ['token' => $token], 200);
        } catch (Exception $ex) {
            Log::error('Login error: '.$ex);

            return Utility::apiError('Unexpected error occurred', [], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {
            // Get existing user
            $user = Auth::user();
            if (! $user) {
                return Utility::apiError('User not authenticated', [], 401);
            }

            // Get email info
            $ip = $request->ip() ?? $request->server('REMOTE_ADDR') ?? 'unknown';
            $userAgent = $request->header('User-Agent', 'unknown');
            $location = $this->getUserLocation($ip);
            $agentInfo = $this->parseAgent($userAgent);
            $time = Carbon::now();
            $details = [
                'username' => $user->name ?? null,
                'event' => 'Logout',
                'ip' => $ip,
                'country' => $location['country'],
                'state' => $location['state'],  
                'city' => $location['city'],
                'zip' => $location['zip'],
                'time' => $time->format('d-m-Y h:i:s A'),
                'time_short' => $time->toDateTimeString(),
                'user_agent' => $userAgent,
                'browser' => $agentInfo['browser'],
                'platform' => $agentInfo['platform'],
                'device' => $agentInfo['device'],
                'to_email' => 'mkyadav59@gmail.com',
                'logo_url' => asset('appLogo/logo.png'),
            ];

            // Invalidate token (JWT)
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (Exception $e) {
                Log::warning('JWT invalidate error: '.$e->getMessage());
            }

            // Send email
            SendLoginLogoutEmail::dispatch($details)->onQueue('emails');

            // eturn  resposne
            return Utility::apiSuccess('Logout successful', [], 200);
        } catch (Exception $ex) {
            Log::error('Logout error: '.$ex);

            return Utility::apiError('Unexpected error occurred', [], 500);
        }
    }
}

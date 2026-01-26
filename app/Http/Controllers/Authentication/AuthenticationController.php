<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\SendLoginLogoutEmail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            $position = Location::get('184.168.121.218');
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

            /* ================= VALIDATION ================= */
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            /* ================= FETCH USER ================= */
            $user = User::where('email', $request?->email)->first();

            if (! Hash::check($request?->password, $user?->password)) {
                return Utility::apiError('Invalid credentials', [], 401);
            }

            /* ================= SINGLE SESSION CHECK ================= */
            if (! is_null($user->active_jwt)) {
                return Utility::apiError(
                    'You are already logged in on another device',
                    [],
                    401
                );
            }

            /* ================= ISSUE TOKEN ================= */
            $token = JWTAuth::fromUser($user);

            /* ================= ATOMIC UPDATE ================= */
            $updated = User::where('id', $user->id)
                ->whereNull('active_jwt')
                ->update(['active_jwt' => $token]);

            if (! $updated) {
                return Utility::apiError(
                    'Fail to update JWT token',
                    [],
                    401
                );
            }

            /* ================= LOGIN META ================= */
            $ip = $request->ip() ?? 'unknown';
            $userAgent = $request->header('User-Agent', 'unknown');

            $location = $this->getUserLocation($ip);
            $agentInfo = $this->parseAgent($userAgent);

            $time = Carbon::now();

            $details = [
                'username' => $user->name,
                'event' => 'Login',
                'ip' => $ip,
                'country' => $location['country'] ?? null,
                'state' => $location['state'] ?? null,
                'city' => $location['city'] ?? null,
                'zip' => $location['zip'] ?? null,
                'time' => $time->format('d-m-Y h:i:s A'),
                'time_short' => $time->toDateTimeString(),
                'user_agent' => $userAgent,
                'browser' => $agentInfo['browser'] ?? null,
                'platform' => $agentInfo['platform'] ?? null,
                'device' => $agentInfo['device'] ?? null,
                'to_email' => $user->email,
                'logo_url' => asset('appLogo/logo.png'),
            ];
            SendLoginLogoutEmail::dispatch($details)->onQueue('emails');

            return Utility::apiSuccess('Login successful', [
                'token' => $token,
            ], 200);

        } catch (Exception $e) {
            Log::error('Login error', ['exception' => $e]);

            return Utility::apiError('Unexpected error occurred', [], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return Utility::apiError('User not authenticated', [], 401);
            }
            $ip = $request->ip() ?? 'unknown';
            $userAgent = $request->header('User-Agent', 'unknown');

            $location = $this->getUserLocation($ip);
            $agentInfo = $this->parseAgent($userAgent);

            $time = Carbon::now();

            $details = [
                'username' => $user->name,
                'event' => 'Logout',
                'ip' => $ip,
                'country' => $location['country'] ?? null,
                'state' => $location['state'] ?? null,
                'city' => $location['city'] ?? null,
                'zip' => $location['zip'] ?? null,
                'time' => $time->format('d-m-Y h:i:s A'),
                'time_short' => $time->toDateTimeString(),
                'user_agent' => $userAgent,
                'browser' => $agentInfo['browser'] ?? null,
                'platform' => $agentInfo['platform'] ?? null,
                'device' => $agentInfo['device'] ?? null,
                'to_email' => $user->email,
                'logo_url' => asset('appLogo/logo.png'),
            ];

            $user->update(['active_jwt' => null]);
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Throwable $e) {
                Log::info('JWT already invalidated or expired');
            }

            SendLoginLogoutEmail::dispatch($details)->onQueue('emails');

            return Utility::apiSuccess('Logout successful', [], 200);

        } catch (Exception $e) {
            Log::error('Logout error', ['exception' => $e]);

            return Utility::apiError('Unexpected error occurred', [], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Utility;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{

    public function getProfile(Request $request)
    {
        try {
            # Auth user
            $user = Auth::user();
            if (!$user) {
                return Utility::apiError('Invalid user', [], 221);
            }

            # Get profile
            $profile = User::with('branch', 'role')->where('id', $user['id'])->first();

            # Return if not found
            if (!$profile) {
                return Utility::apiError('Profile info not found', [], 221);
            }

            if (!empty($profile['profile_image'])) {
                $profile['profile_image'] = url("/profileLogo/{$profile['profile_image']}");
            }

            # Return response
            return Utility::apiSuccess('Profile fetched successfully', $profile, 200);

        } catch (Exception $ex) {
            Log::error('Login error: ' . $ex);
            return Utility::apiError('Unexpected getProfile error occurred', [], 500);
        }
    }


    public function updateProfile(Request $request)
    {
        try {
            # Get requested data
            $data = $request->only([
                'name',
                'last_name',
                'profile_image',
            ]);

            # Get user id
            $id = Auth::id();

            # Validate user info
            $validator = Validator::make($data, [
                'name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            # Return if fail
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Get user instance
            $user = User::findOrFail($id);

            # Handle profile image
            $imageName = $user->profile_image;
            if ($request->file('profile_image')) {
                $extension = $request->file('profile_image')->getClientOriginalExtension();
                $path = public_path('profileLogo/');
                $imageName = 'profile_logo' . date('Y-m-d') . '_' . time() . '.' . $extension;
                $request->file('profile_image')->move($path, $imageName);
            }

            # Update user with explicit fields
            $user->update([
                'name' => $data['name'],
                'last_name' => $data['last_name'],
                'profile_image' => $imageName,
            ]);

            # Return response
            return Utility::apiSuccess('profile updated successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Login error: ' . $ex);
            return Utility::apiError('Unexpected updateProfile error occurred', [], 500);
        }
    }

    public function updatePassWword(Request $request)
    {
        try {
            # Get requested data
            $data = $request->only([
                'password',
                'confirm_password',
            ]);

            # Get user id
            $id = Auth::id();

            # Validate user info
            $validator = Validator::make($data, [
                'password' => 'required|min:8|same:confirm_password',
                'confirm_password' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Get user instance
            $user = User::findOrFail($id);

            # Handle password
            $password = $user->password;
            if (!empty($data['password'])) {
                $password = Hash::make($data['password']);
            }

            # Update user with explicit fields
            $user->update([
                'password' => $password,
            ]);

            # Return response
            return Utility::apiSuccess('password updated successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Login error: ' . $ex);
            return Utility::apiError('Unexpected updatePassWword error occurred', [], 500);
        }
    }
}

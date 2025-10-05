<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return Utility::apiError('Invalid user', [], 221);
            }

            $profile = User::with('branch', 'role')->find($user->id);
            if (! $profile) {
                return Utility::apiError('Profile info not found', [], 221);
            }

            $image = $profile->profile_image ?? null;

            if ($image) {
                if (filter_var($image, FILTER_VALIDATE_URL)) {
                    $profile->profile_image = $image;
                } else {
                    $publicPath = public_path('profileLogo/'.$image);
                    $storageRelative = 'profileLogo/'.$image;

                    if (file_exists($publicPath)) {
                        $profile->profile_image = url('profileLogo/'.$image);
                    } elseif (Storage::disk('public')->exists($storageRelative)) {
                        $profile->profile_image = Storage::disk('public')->url($storageRelative);
                    } else {
                        Log::warning("Profile image missing for user {$user->id}: {$image}");
                        $profile->profile_image = null;
                    }
                }
            } else {
                $profile->profile_image = null;
            }

            return Utility::apiSuccess('Profile fetched successfully', $profile, 200);
        } catch (Exception $ex) {
            Log::error('getProfile error: '.$ex);

            return Utility::apiError('Unexpected getProfile error occurred', [], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $id = Auth::id();
            if (! $id) {
                return Utility::apiError('Invalid user', [], 221);
            }

            $rules = [
                'name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:150',
            ];

            if ($request->hasFile('profile_image')) {
                $rules['profile_image'] = 'file|image|mimes:jpg,jpeg,png|max:5120'; // 5 MB
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            $user = User::findOrFail($id);

            $imageName = $user->profile_image;

            if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                $file = $request->file('profile_image');
                $filename = 'profile_logo_'.now()->format('Ymd_His').'.'.$file->getClientOriginalExtension();

                $storedPath = $file->storeAs('profileLogo', $filename, 'public');

                if (! $storedPath) {
                    Log::error('Profile image upload failed: storage->storeAs returned false for user '.$id);

                    return Utility::apiError('Upload failed', [], 500);
                }

                $imageName = basename($storedPath);

                if ($user->profile_image) {
                    $oldRelative = 'profileLogo/'.basename($user->profile_image);
                    if (Storage::disk('public')->exists($oldRelative)) {
                        Storage::disk('public')->delete($oldRelative);
                    }   
                }
            }

            // Update allowed fields
            $user->update([     
                'name' => $request->input('name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'profile_image' => $imageName,
            ]);

            $profileImageUrl = $imageName ? Storage::disk('public')->url('profileLogo/'.$imageName) : null;

            return Utility::apiSuccess('Profile updated successfully', [
                'profile_image' => $imageName,
                'profile_image_url' => $profileImageUrl,
            ], 200);

        } catch (Exception $ex) {
            Log::error('updateProfile error: '.$ex);

            return Utility::apiError('Unexpected updateProfile error occurred', [], 500);
        }
    }

    public function updatePassWword(Request $request)
    {
        try {
            // Get requested data
            $data = $request->only([
                'password',
                'confirm_password',
            ]);

            // Get user id
            $id = Auth::id();

            // Validate user info
            $validator = Validator::make($data, [
                'password' => 'required|min:8|same:confirm_password',
                'confirm_password' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            // Get user instance
            $user = User::findOrFail($id);

            // Handle password
            $password = $user->password;
            if (! empty($data['password'])) {
                $password = Hash::make($data['password']);
            }

            // Update user with explicit fields
            $user->update([
                'password' => $password,
            ]);

            // Return response
            return Utility::apiSuccess('password updated successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Login error: '.$ex);

            return Utility::apiError('Unexpected updatePassWword error occurred', [], 500);
        }
    }
}

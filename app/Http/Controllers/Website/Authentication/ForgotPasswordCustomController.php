<?php

namespace App\Http\Controllers\Website\Authentication;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Website\ForgotPasswordRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordCustomController extends Controller
{
   
    public function sendResetLink(Request $request)
    {
        try {
            $rules = [
                'email' => 'required|email|exists:users,email',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $user = User::where('email', $request->input('email'))->first();

            if (! $user) {
                return Utility::apiError('User not found', [], 404);
            }

            // create token and store request in DB
            $token = Password::createToken($user);

            $fpr = ForgotPasswordRequest::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => $token,
                'status' => 'active',
                'requested_at' => now(),
            ]);

            // send reset link via default broker
            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status === Password::RESET_LINK_SENT) {
                return Utility::apiSuccess('Password reset link sent successfully.', [
                    'request' => $fpr,
                    'status' => $status,
                ], 200);
            }

            // anything else is a failure from broker
            return Utility::apiError('Failed to send password reset link', ['status' => $status], 500);
        } catch (Exception $ex) {
            Log::error('ForgotPassword sendResetLink error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getForgetPassword(Request $request)
    {
        try {
            $data = $request->only(['page', 'per_page']);

            $page = max(1, (int) ($data['page'] ?? 1));
            $perPage = (int) ($data['per_page'] ?? config('constant.per_page', 25));

            $query = ForgotPasswordRequest::with('user')->orderByDesc('requested_at');

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Forgot password request list fetched successfully', $paginator, 200);
        } catch (Exception $ex) {
            Log::error('ForgotPassword getForgetPassword error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch requests', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteForgetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:forgot_password_requests,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = ForgotPasswordRequest::find($request->input('id'));
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('ForgotPassword delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting request', ['exception' => $ex->getMessage()], 500);
        }
    }
}

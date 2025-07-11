<?php

namespace App\Http\Controllers\Cofiguration\Notification;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Auth;
use Exception;
class NotificationController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateNotification(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['name', 'email', 'multi_email', 'branch_id']);

            # Validate rule
            $validator = Validator::make($data, [
                'name' => 'required',
                'email' => 'required',
                'multi_email' => 'required',
                'branch_id' => 'required',
                'notification_id' => 'nullable||sometimes',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Create notification
            $status = false;
            $message = 'Notification added successfully';
            $arr = [
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'cc_email' => $data['multi_email'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
            ];

            # Update notification
            if (!empty($data['notification_id'])) {
                $status = Notification::where('id', $data['notification_id'])->update($arr);
                $message = 'Notification updated successfully';
            }

            # Add notification
            $status = Notification::create($arr);
            if (!$status) {
                return Utility::apiError('Fail to add notification', [], 221);
            }

            # Return response
            return Utility::apiSuccess($message, [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error occurred while action on addUpdateNotification', ['exception' => $ex->getMessage()]);
        }
    }

    public function getNotifications(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['page', 'per_page', 'search']);

            # Get principal
            $principal = Notification::whereNull('deleted_at')->orderBy('id', 'desc')->paginate(10);

            # Return response
            return Utility::apiSuccess('List notication', $principal, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error occurred while action on getNotifications', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteNotification(Request $request)
    {
        try {

            # Request specific fields
            $data = $request->only(['notification_id']);

            # Validation rule
            $validator = Validator::make($data, [
                'notification_id' => 'required',
            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete principal
            $status = Notification::where('id', $data['notification_id'])->delete();
            if (!$status) {
                return Utility::apiError('Fail to delete notification', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Notification deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::debug($ex);
            return Utility::apiError('Error occurred while action on deleteNotification', ['exception' => $ex->getMessage()]);
        }
    }
}

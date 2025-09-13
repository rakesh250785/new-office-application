<?php

namespace App\Http\Controllers\NotificationPush;

use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Notifications\DatabaseNotification;
use Log;
class NotificationPushController extends Controller
{
    public function notifications(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 5);

            $notifications = $user->notifications()->whereNull('read_at')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'data' => $notifications->items(),
                'meta' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                ],
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail at statusQuotationChange server error', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function markRead(Request $request)
    {
        try {
            $id = $request->input('id');

            $notification = DatabaseNotification::where('id', $id)
                ->first();

            if (!$notification) {
                return Utility::apiError('Notification not found', 404);
            }

            $notification->update(['read_at' => now()]);

            return Utility::apiSuccess('Read status updated', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail at markRead server error', ['exception' => $ex->getMessage()], 500);
        }
    }


    public function markAllRead(Request $request)
    {
        try {
            $request->user()->unreadNotifications->markAsRead();
            return Utility::apiSuccess('Read all status updated', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail at markRead server error', ['exception' => $ex->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\NotificationPush;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class NotificationPushController extends Controller
{
    public function notifications(Request $request)
    {
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
    }

    public function markRead(Request $request)
    {
        $id = $request->input('id');
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * SSE stream endpoint. Authenticates by token query param: ?token=PERSONAL_ACCESS_TOKEN
     * Keep the connection open and push new notifications.
     */
    public function stream(Request $request)
    {
        // Authenticate by token in query param
        $token = $request->query('token');
        if (!$token) {
            return response('Unauthorized', 401);
        }

        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
        if (!$user) {
            return response('Unauthorized', 401);
        }

        // We will use a simple long-running response (SSE)
        return response()->stream(function () use ($user) {
            // last seen notification id — start from latest so we don't resend old ones
            $lastId = optional($user->notifications()->orderByDesc('created_at')->first())->id ?? null;

            // Send a ping comment to keep connection alive in some proxies
            echo ": connected\n\n";
            ob_flush();
            flush();

            // Keep this loop modest: runs until client disconnects or after N cycles
            $start = time();
            while (!connection_aborted()) {
                // Fetch new notifications with id > $lastId
                $query = $user->notifications()->orderBy('created_at', 'asc');
                if ($lastId) {
                    $query->where('id', '>', $lastId);
                }
                $new = $query->get();

                if ($new->isNotEmpty()) {
                    foreach ($new as $n) {
                        // SSE event name 'notification' and JSON payload
                        $payload = json_encode([
                            'id' => $n->id,
                            'data' => $n->data,
                            'read_at' => $n->read_at,
                            'created_at' => $n->created_at->toDateTimeString(),
                        ]);
                        echo "event: notification\n";
                        echo "data: {$payload}\n\n";
                        ob_flush();
                        flush();
                        $lastId = $n->id;
                    }
                } else {
                    // optionally send heartbeat every 15s so proxies keep the connection alive
                    echo ": heartbeat\n\n";
                    ob_flush();
                    flush();
                }

                // sleep 2 seconds before checking again (adjust as needed)
                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    public function pollNotifications(Request $request)
    {
        $user = Auth::user();
        $lastId = $request->input('last_seen_id');

        $query = $user->notifications()->whereNull('read_at')->orderBy('id', 'desc');

        if ($lastId) {
            $query->where('id', '>', $lastId);
        }

        $new = $query->get();

        return response()->json([
            'data' => $new,
        ]);
    }

}

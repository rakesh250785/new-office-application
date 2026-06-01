<?php

namespace App\Http\Controllers\NotificationPush;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
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

            if (! $notification) {
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

    public function getNotificationsList(Request $request)
    {
        try {
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'filter',
                'entity_type',
                'search',
            ]);

            $page = max((int) ($data['page'] ?? 1), 1);
            $perPage = max((int) ($data['per_page'] ?? config('constant.per_page', 15)), 1);
            $search = isset($data['search']) ? trim($data['search']) : '';
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $filter = $data['filter'] ?? 'all';
            $entityType = $data['entity_type'] ?? null;

            $query = DatabaseNotification::query()->select('notifications.*');

            // --- Date range handling (safe, inclusive) ---
            // Normalize & validate dates. If both provided and start > end, swap them.
            $start = null;
            $end = null;
            try {
                if (! empty($startDate)) {
                    $start = Carbon::parse($startDate)->startOfDay();
                }
            } catch (\Throwable $e) {
                $start = null;
            }
            try {
                if (! empty($endDate)) {
                    $end = Carbon::parse($endDate)->endOfDay();
                }
            } catch (\Throwable $e) {
                $end = null;
            }

            if ($start && $end) {
                // if user accidentally passed start > end, swap to be safe
                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                $query->whereBetween('notifications.created_at', [$start, $end]);
            } elseif ($start) {
                $query->where('notifications.created_at', '>=', $start);
            } elseif ($end) {
                $query->where('notifications.created_at', '<=', $end);
            }

            // # filter read / unread
            if ($filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($filter === 'read') {
                $query->whereNotNull('read_at');
            }

            // # Filter by entity_type stored inside JSON `data`
            if (! empty($entityType)) {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.entity_type')) = ?", [$entityType]);
            }

            if ($search !== '') {
                $like = '%'.str_replace('%', '\\%', $search).'%';

                $query->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'notifications.notifiable_id')
                        ->where('notifications.notifiable_type', '=', User::class);
                });

                $query->where(function ($q) use ($like) {
                    $q->where('notifications.data', 'like', $like)
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`notifications`.`data`, '$.message')) LIKE ?", [$like])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`notifications`.`data`, '$.meta.quotation_no')) LIKE ?", [$like])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`notifications`.`data`, '$.meta.order_no')) LIKE ?", [$like])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`notifications`.`data`, '$.meta.partial_order_no')) LIKE ?", [$like])
                        ->orWhere('users.name', 'like', $like)
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`notifications`.`data`, '$.meta.created_by')) LIKE ?", [$like]);
                });
            }

            // # Eager-load polymorphic notifiable so we can safely access name in various notifiable types
            $query->with('notifiable');

            // # Order and paginate
            $paginator = $query->orderByDesc('notifications.created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            // # Transform output and decode JSON data safely
            $paginator->getCollection()->transform(function ($item) {
                $data = $item->data;
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    $data = $decoded === null ? [] : $decoded;
                }

                $userName = null;
                if ($item->notifiable && isset($item->notifiable->name)) {
                    $userName = $item->notifiable->name;
                } else {
                    $userName = $data['meta']['created_by'] ?? null;
                }

                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'notifiable_type' => $item->notifiable_type,
                    'user_name' => $userName,
                    'entity_type' => $data['entity_type'] ?? null,
                    'entity_id' => $data['entity_id'] ?? null,
                    'message' => $data['message'] ?? ($data['meta']['message'] ?? null),
                    'meta' => $data['meta'] ?? null,
                    'read_at' => $item->read_at ? $item->read_at->toDateTimeString() : null,
                    'created_at' => $item->created_at ? $item->created_at->format('d/m/Y') : null,
                ];
            });

            return Utility::apiSuccess('Notifications fetched successfully.', $paginator, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch notifications.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getNotificationEntityTypes(Request $request)
    {
        try {
            $types = DatabaseNotification::query()
                ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.entity_type')) as entity_type")
                ->distinct()
                ->pluck('entity_type')
                ->filter()
                ->values();

            $result = [];
            foreach ($types as $type) {
                $result[$type] = ucfirst(str_replace('_', ' ', $type));
            }

            return Utility::apiSuccess('Entity types fetched successfully.', $result, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch notifications.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

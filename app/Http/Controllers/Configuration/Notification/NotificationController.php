<?php

namespace App\Http\Controllers\Configuration\Notification;

use App\Exports\Export;
use App\Exports\NotificationExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\NotificationEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function addUpdateNotification(Request $request)
    {
        try {
            // Extract and validate input
            $data = $request->only([
                'name',
                'email',
                'email_list',
                'branch_id',
                'notification_id',
                'update_status',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('notifications_email', 'email')->ignore($data['notification_id'] ?? null),
                ],
                'email_list' => 'required|string',
                'branch_id' => 'required|integer|exists:branches,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Map validated data
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'email_list' => $data['email_list'],
                'branch_id' => $data['branch_id'],
                'user_id' => Auth::id(),
            ];

            // Create or update record
            $notification = NotificationEmail::updateOrCreate(
                ['id' => $data['notification_id'] ?? null],
                $payload
            );

            // Return if fail
            if (! $notification) {
                return Utility::apiError('Failed to save notification', [], 221);
            }

            // Prepare message
            $message = isset($data['notification_id']) ? 'Updated successfully' : 'Created successfully';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getNotification(Request $request)
    {
        try {
            // Extract request data
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            // Export handling
            if (! empty($data['download'])) {
                $columns = [
                    'name' => 'Name',
                    'email' => 'Email',
                    'email_list' => 'Email List',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'notification_'.now()->format('Ymd_His').'.xlsx';

                (new NotificationExport($data, $columns, NotificationEmail::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Base query with branch relationship
            $query = NotificationEmail::with('branch:id,name')
                ->whereNull('deleted_at');

            // Free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('email_list', 'like', "%$search%")
                        ->orWhereHas('branch', function ($b) use ($search) {
                            $b->where('name', 'like', "%$search%");
                        });
                });
            }

            // Branch filter
            if (! empty($data['branch_list'])) {
                $query->whereIn('branch_id', (array) $data['branch_list']);
            }

            // Date filter
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Paginate response
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $notificationData = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('Notification list fetched successfully', $notificationData, 200);

        } catch (Exception $ex) {
            Log::error('Notification fetch error: '.$ex->getMessage(), [
                'trace' => $ex->getTraceAsString(),
            ]);

            return Utility::apiError('Failed to fetch notifications', [
                'exception' => $ex->getMessage(),
            ]);
        }
    }

    public function deleteNotification(Request $request)
    {
        try {

            // Request id
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:notifications,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Soft delete record
            $deleted = NotificationEmail::where('id', $data['id'])->delete();

            // Retunr if fail
            if (! $deleted) {
                return Utility::apiError('Failed to delete notification', [], 221);
            }

            // Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Notification delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting notification.', ['exception' => $ex->getMessage()], 500);
        }
    }
}

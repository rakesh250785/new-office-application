<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Order;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        Log::info('OrderObserver created fired for id: ' . $order->id);

        $recipients = Auth::user();
        $status = $order->pendingQuotationDetails->status_code ?? null;
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('order', $order->id, [
            'amount' => $order->total_amount,
            'status' => $status,
            'branch' => $branchName,
            'created_at' => Carbon::parse($order->created_at)->format('d-m-Y h:i:s A'),
            'created_by' => Auth::user()->name,
            'message' => 'New Order',
            'order_no' => $order->unique_order_no,
            'type' => 'order',
        ]));
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        Log::info('OrderObserver updated fired for id: ' . $order->id);

        $recipients = Auth::user();
        $status = $order->pendingQuotationDetails->status_code ?? null;
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('order', $order->id, [
            'amount' => $order->total_amount,
            'status' => $status,
            'branch' => $branchName,
            'created_at' => Carbon::parse($order->created_at)->format('d-m-Y h:i:s A'),
            'created_by' => Auth::user()->name,
            'message' => 'Order Updated',
            'order_no' => $order->unique_order_no,
            'type' => 'order',
        ]));
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}

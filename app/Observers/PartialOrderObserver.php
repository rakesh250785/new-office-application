<?php

namespace App\Observers;

use App\Models\PartialOrder;
use App\Models\Branch;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class PartialOrderObserver
{
    /**
     * Handle the PartialOrder "created" event.
     */
    public function created(PartialOrder $partialOrder): void
    {
        Log::info('PartialOrderObserver created fired for id: ' . $partialOrder->id);

        $recipients = Auth::user();
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('partial_order', $partialOrder->id, [
            'amount' => $partialOrder->total_amount,
            'status' => "new",
            'branch' => $branchName,
            'created_at' => Carbon::parse($partialOrder->created_at)->format('d-m-Y h:i:s A'),
            'created_by' => Auth::user()->name,
            'message' => 'New Partial Order',
            'partial_order_no' => $partialOrder->unique_partial_order_no,
            'type' => 'partial_order',
        ]));
    }

    /**
     * Handle the PartialOrder "updated" event.
     */
    public function updated(PartialOrder $partialOrder): void
    {
        Log::info('PartialOrderObserver updated fired for id: ' . $partialOrder->id);

        $recipients = Auth::user();
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('partial_order', $partialOrder->id, [
            'amount' => $partialOrder->total_amount,
            'status' => "new",
            'branch' => $branchName,
            'created_at' => Carbon::parse($partialOrder->created_at)->format('d-m-Y h:i:s A'),
            'created_by' => Auth::user()->name,
            'message' => 'Partial Order Update',
            'partial_order_no' => $partialOrder->unique_partial_order_no,
            'type' => 'partial_order',
        ]));
    }

    /**
     * Handle the PartialOrder "deleted" event.
     */
    public function deleted(PartialOrder $partialOrder): void
    {
        //
    }

    /**
     * Handle the PartialOrder "restored" event.
     */
    public function restored(PartialOrder $partialOrder): void
    {
        //
    }

    /**
     * Handle the PartialOrder "force deleted" event.
     */
    public function forceDeleted(PartialOrder $partialOrder): void
    {
        //
    }
}

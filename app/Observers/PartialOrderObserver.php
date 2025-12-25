<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\PartialOrder;
use App\Models\User;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PartialOrderObserver
{
    public function created(PartialOrder $partialOrder): void
    {
        $this->notify($partialOrder, 'created');
    }

    public function updated(PartialOrder $partialOrder): void
    {
        $this->notify($partialOrder, 'updated');
    }

    private function notify(PartialOrder $partialOrder, string $event): void
    {
        $user = User::find($partialOrder->user_id);

        if (! $user) {
            Log::warning("PartialOrder {$event}: user not found", [
                'partial_order_id' => $partialOrder->id,
                'user_id' => $partialOrder->user_id,
            ]);

            return;
        }

        $branchName = optional(
            Branch::find($user->branch_id)
        )->name;

        Log::info("PartialOrder {$event} fired", [
            'partial_order_id' => $partialOrder->id,
            'user_id' => $user->id,
        ]);

        $user->notify(new EntityCreated(
            'partial_order',
            $partialOrder->id,
            [
                'amount' => $partialOrder->total_amount,
                'status' => 'new',
                'branch' => $branchName,
                'created_at' => Carbon::parse(
                    $event === 'created'
                        ? $partialOrder->created_at
                        : $partialOrder->updated_at
                )->format('d-m-Y h:i:s A'),
                'created_by' => $user->name,
                'message' => $event === 'created'
                                        ? 'New Partial Order'
                                        : 'Partial Order Updated',
                'partial_order_no' => $partialOrder->unique_partial_order_no,
                'type' => 'partial_order',
            ]
        ));
    }
}

<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->notify($order, 'created');
    }

    public function updated(Order $order): void
    {
        $this->notify($order, 'updated');
    }

    private function notify(Order $order, string $event): void
    {
        $user = User::find($order->user_id);

        if (! $user) {
            Log::warning("Order {$event}: user not found", [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);

            return;
        }

        $branchName = optional(
            Branch::find($user->branch_id)
        )->name;

        Log::info("Order {$event} fired", [
            'order_id' => $order->id,
            'user_id' => $user->id,
        ]);

        $user->notify(new EntityCreated(
            'order',
            $order->id,
            [
                'amount' => $order->total_amount,
                'status' => $order->pendingQuotationDetails->status_code ?? null,
                'branch' => $branchName,
                'created_at' => Carbon::parse(
                    $event === 'created'
                        ? $order->created_at
                        : $order->updated_at
                )->format('d-m-Y h:i:s A'),
                'created_by' => $user->name,
                'message' => $event === 'created'
                                ? 'New Order'
                                : 'Order Updated',
                'order_no' => $order->unique_order_no,
                'type' => 'order',
            ]
        ));
    }
}

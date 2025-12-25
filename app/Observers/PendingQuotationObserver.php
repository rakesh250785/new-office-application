<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\PendingQuotation;
use App\Models\User;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PendingQuotationObserver
{
    public function created(PendingQuotation $pendingQuotation): void
    {
        $this->notify($pendingQuotation, 'created');
    }

    public function updated(PendingQuotation $pendingQuotation): void
    {
        $this->notify($pendingQuotation, 'updated');
    }

    private function notify(PendingQuotation $pendingQuotation, string $event): void
    {
        $user = User::find($pendingQuotation->user_id);

        if (! $user) {
            Log::warning("PendingQuotation {$event}: user not found", [
                'pending_quotation_id' => $pendingQuotation->id,
                'user_id' => $pendingQuotation->user_id,
            ]);

            return;
        }

        $branchName = optional(
            Branch::find($user->branch_id)
        )->name;

        Log::info("PendingQuotation {$event} fired", [
            'pending_quotation_id' => $pendingQuotation->id,
            'user_id' => $user->id,
        ]);

        $user->notify(new EntityCreated(
            'quotation_status',
            $pendingQuotation->id,
            [
                'amount' => $pendingQuotation->total_amount,
                'status' => $pendingQuotation->status_code,
                'branch' => $branchName,
                'created_at' => Carbon::parse(
                    $event === 'created'
                        ? $pendingQuotation->created_at
                        : $pendingQuotation->updated_at
                )->format('d-m-Y h:i:s A'),
                'created_by' => $user->name,
                'message' => 'Quotation Status Update',
                'quotation_no' => $pendingQuotation->unique_quotation_no,
                'type' => 'quotation_status',
            ]
        ));
    }
}

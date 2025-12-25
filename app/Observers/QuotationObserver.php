<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Quotation;
use App\Models\User;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class QuotationObserver
{
    public function created(Quotation $quotation): void
    {
        $this->notify($quotation, 'created');
    }

    public function updated(Quotation $quotation): void
    {
        $this->notify($quotation, 'updated');
    }

    private function notify(Quotation $quotation, string $event): void
    {
        $user = User::find($quotation->user_id);

        if (! $user) {
            Log::warning("Quotation {$event}: user not found", [
                'quotation_id' => $quotation->id,
                'user_id' => $quotation->user_id,
            ]);

            return;
        }

        $branchName = optional(
            Branch::find($user->branch_id)
        )->name;

        Log::info("QuotationObserver {$event} fired", [
            'quotation_id' => $quotation->id,
            'user_id' => $user->id,
        ]);

        $user->notify(new EntityCreated(
            'quotation',
            $quotation->id,
            [
                'amount' => $quotation->total_amount,
                'status' => $quotation->pendingQuotationDetails->status_code ?? null,
                'branch' => $branchName,
                'created_at' => Carbon::parse(
                    $event === 'created'
                        ? $quotation->created_at
                        : $quotation->updated_at
                )->format('d-m-Y h:i:s A'),
                'created_by' => $user->name,
                'message' => $event === 'created'
                                    ? 'New Quotation'
                                    : 'Quotation Updated',
                'quotation_no' => $quotation->unique_quotation_no,
                'type' => 'quotation',
            ]
        ));
    }
}

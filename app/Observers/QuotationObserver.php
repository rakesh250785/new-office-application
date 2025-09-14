<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Quotation;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuotationObserver
{
    /**
     * Handle the QuotationDetail "created" event.
     */
    public function created(Quotation $quotation)
    {
        // Example for Quotation. Repeat for Order, PartialOrder, Invoice (change model names).
        // Choose recipients. Example: users with role 'admin' in same tenant

        Log::info('QuotationObserver created fired for id: ' . $quotation->id);


        $recipients = Auth::user();
        $status = $quotation->pendingQuotationDetails->status_code ?? null;
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('quotation', $quotation->id, [
            'amount' => $quotation->total_amount,
            'status' => $status,
            'branch' => $branchName,
            'created_at' => Carbon::parse($quotation->created_at)->format('d-m-Y h:i:s A'),
            'created_by' => Auth::user()->name,
            'message' => 'New Quotation',
            'quotation_no' => $quotation->unique_quotation_no,
            'type' => 'quotation',
        ]));
    }

    public function updated(Quotation $quotation): void
    {
        // Example for Quotation. Repeat for Order, PartialOrder, Invoice (change model names).
        // Choose recipients. Example: users with role 'admin' in same tenant
        $recipients = Auth::user();
        Log::info('QuotationObserver updated fired for id: ' . $quotation->id);

        logger("herererer");
        $status = $quotation->pendingQuotationDetails->status_code ?? null;
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('quotation', $quotation->id, [
            'amount' => $quotation->total_amount,
            'status' => $status,
            'branch' => $branchName,
            'created_at' => Carbon::parse($quotation->created_at)->format('d-m-Y h:i:s A'),
            'created_by' => Auth::user()->name,
            'message' => 'Quotation updated',
            'quotation_no' => $quotation->unique_quotation_no,
            'type' => 'quotation',
        ]));
    }

}

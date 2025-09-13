<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\PendingQuotation;
use App\Models\Quotation;
use App\Observers\QuotationObserver;
use App\Notifications\EntityCreated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class PendingQuotationObserver
{
    /**
     * Handle the PendingQuotation "created" event.
     */

    protected QuotationObserver $notifier;

    public function __construct()
    {

    }
    public function created(PendingQuotation $pendingQuotation): void
    {
        // Example for Quotation. Repeat for Order, PartialOrder, Invoice (change model names).
        // Choose recipients. Example: users with role 'admin' in same tenant
        $recipients = Auth::user();
        Log::info('PendingQuotation created fired for id: ' . $pendingQuotation);



        // Example for Quotation. Repeat for Order, PartialOrder, Invoice (change model names).
        // Choose recipients. Example: users with role 'admin' in same tenant
        $recipients = Auth::user();
        Log::info('PendingQuotation created fired for id: ' . $pendingQuotation->id);

        $status = $pendingQuotation->status_code ?? null;
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('quotation', $pendingQuotation->id, [
            'amount' => $pendingQuotation->total_amount,
            'status' => $status,
            'branch' => $branchName,
            'created_at' => $pendingQuotation->created_at,
            'created_by' => Auth::user()->name,
            'message' => 'Quotation Status Update',
            'quotation_no' => $pendingQuotation->unique_quotation_no,
        ]));
    }

    /**
     * Handle the PendingQuotation "updated" event.
     */
    public function updated(PendingQuotation $pendingQuotation): void
    {
        // Example for Quotation. Repeat for Order, PartialOrder, Invoice (change model names).
        // Choose recipients. Example: users with role 'admin' in same tenant
        $recipients = Auth::user();
        Log::info('PendingQuotation updated fired for id: ' . $pendingQuotation);



        // Example for Quotation. Repeat for Order, PartialOrder, Invoice (change model names).
        // Choose recipients. Example: users with role 'admin' in same tenant
        $recipients = Auth::user();
        Log::info('PendingQuotation updated fired for id: ' . $pendingQuotation->id);

        $status = $pendingQuotation->status_code ?? null;
        $branchName = Branch::findOrFail($recipients->branch_id)->name;
        Notification::send($recipients, new EntityCreated('quotation', $pendingQuotation->id, [
            'amount' => $pendingQuotation->total_amount,
            'status' => $status,
            'branch' => $branchName,
            'created_at' => $pendingQuotation->created_at,
            'created_by' => Auth::user()->name,
            'message' => 'Quotation Status Update',
            'quotation_no' => $pendingQuotation->unique_quotation_no,
        ]));
    }

}

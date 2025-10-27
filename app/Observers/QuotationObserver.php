<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Quotation;
use App\Notifications\EntityCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class QuotationObserver
{
    /**
     * Handle the QuotationDetail "created" event.
     */
    public function created(Quotation $quotation)
    {
        // Log::info('QuotationObserver created fired for id: '.$quotation->id);
        // $recipients = Auth::user();
        // $status = $quotation->pendingQuotationDetails->status_code ?? null;
        // $branchName = Branch::findOrFail($recipients->branch_id)->name;
        // Notification::send($recipients, new EntityCreated('quotation', $quotation->id, [
        //     'amount' => $quotation->total_amount,
        //     'status' => $status,
        //     'branch' => $branchName,
        //     'created_at' => Carbon::parse($quotation->created_at)->format('d-m-Y h:i:s A'),
        //     'created_by' => Auth::user()->name,
        //     'message' => 'New Quotation',
        //     'quotation_no' => $quotation->unique_quotation_no,
        //     'type' => 'quotation',
        // ]));
    }

    public function updated(Quotation $quotation): void
    {
        // $recipients = Auth::user();
        // Log::info('QuotationObserver updated fired for id: '.$quotation->id);

        // if(!empty($recipients)){
        //     $recipients  = $recipients->toArray();
        //     $branchName = Branch::findOrFail($recipients['branch_id'])->name;
        //     $status = $quotation->pendingQuotationDetails->status_code ?? null;
           
        //     Notification::send($recipients, new EntityCreated('quotation', $quotation->id, [
        //         'amount' => $quotation->total_amount,
        //         'status' => $status,
        //         'branch' => $branchName ?? null,
        //         'created_at' => Carbon::parse($quotation->created_at)->format('d-m-Y h:i:s A'),
        //         'created_by' => $recipients['name'],
        //         'message' => 'Quotation updated',
        //         'quotation_no' => $quotation->unique_quotation_no,
        //         'type' => 'quotation',
        //     ]));
        // }
    }
}

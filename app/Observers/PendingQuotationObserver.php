<?php

namespace App\Observers;

use App\Models\PendingQuotation;

class PendingQuotationObserver
{
    /**
     * Handle the PendingQuotation "created" event.
     */
    public function created(PendingQuotation $pendingQuotation): void
    {
        //
    }

    /**
     * Handle the PendingQuotation "updated" event.
     */
    public function updated(PendingQuotation $pendingQuotation): void
    {
        //
    }

    /**
     * Handle the PendingQuotation "deleted" event.
     */
    public function deleted(PendingQuotation $pendingQuotation): void
    {
        //
    }

    /**
     * Handle the PendingQuotation "restored" event.
     */
    public function restored(PendingQuotation $pendingQuotation): void
    {
        //
    }

    /**
     * Handle the PendingQuotation "force deleted" event.
     */
    public function forceDeleted(PendingQuotation $pendingQuotation): void
    {
        //
    }
}

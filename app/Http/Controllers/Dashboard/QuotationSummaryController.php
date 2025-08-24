<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\PendingQuotation;
use Illuminate\Http\Request;


class QuotationSummaryController extends Controller
{
    public function quotationStatusReport(Request $request)
    {

        # Request fields
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
        ]);

        # Get query
        $q = PendingQuotation::query();

        # Filters
        if (!empty($validated['from'])) {
            $q->whereDate('last_updated_at', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $q->whereDate('last_updated_at', '<=', $validated['to']);
        }
        if (!empty($validated['branch_id'])) {
            $q->where('branch_id', $validated['branch_id']);
        }
        if (!empty($validated['user_id'])) {
            $q->where('user_id', $validated['user_id']);
        }

        # Single aggregate query
        $row = $q->selectRaw("
                SUM(CASE WHEN status_code = 'open'   THEN 1 ELSE 0 END) AS open_cnt,
                SUM(CASE WHEN status_code = 'win'    THEN 1 ELSE 0 END) AS win_cnt,
                SUM(CASE WHEN status_code = 'lose'   THEN 1 ELSE 0 END) AS lose_cnt,
                SUM(CASE WHEN status_code = 'closed' THEN 1 ELSE 0 END) AS closed_cnt
            ")
            ->first();

        $open = (int) ($row->open_cnt ?? 0);
        $win = (int) ($row->win_cnt ?? 0);
        $lose = (int) ($row->lose_cnt ?? 0);
        $closed = (int) ($row->closed_cnt ?? 0);

        # Payload
        $payload = [
            'labels' => ['open', 'win', 'lose', 'close'],
            'series' => [$open, $win, $lose, $closed],
            'total' => $open + $win + $lose + $closed,
            'filters' => [
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
            ],
        ];

        # Return response
        return Utility::apiSuccess('Quotation status summary', $payload);
    }
}

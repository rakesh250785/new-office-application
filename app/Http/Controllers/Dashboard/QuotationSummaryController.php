<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Models\Owner;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PendingQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Exception;
class QuotationSummaryController extends Controller
{
    public function quotationStatusReport(Request $request)
    {
        try {
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
            return Utility::apiSuccess('Quotation status summary', $payload, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error quotationStatusReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function quotationBranchReport(Request $request)
    {
        try {
            # Request data
            $validated = $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date'],
            ]);

            # Filter date
            $from = $validated['from'] ?? null;
            $to = $validated['to'] ?? null;

            # Sum per-branch via relationship
            $branches = Branch::withSum([
                'quotations' => function ($q) use ($from, $to) {
                    if ($from)
                        $q->whereDate('created_at', '>=', $from);
                    if ($to)
                        $q->whereDate('created_at', '<=', $to);
                }
            ], 'total_amount')
                ->orderBy('name')
                ->get();

            # Categories: 3-letter codes from branch name
            $categories = $branches->pluck('name')
                ->map(fn($n) => substr((string) $n, 0, 3))
                ->toArray();

            # Series data (numbers)
            $seriesData = $branches->map(fn($b) => (float) ($b->quotations_sum_total_amount ?? 0))->toArray();

            # Grand total across all branches
            $grandTotal = array_sum($seriesData);

            # If you want a nicer axis cap (optional), round up a bit:
            $yMax = $this->niceCeil($grandTotal);

            # Response data
            $response = [
                'categories' => $categories,
                'series' => [
                    ['name' => 'Amount', 'data' => $seriesData],
                ],
                'y_max' => $yMax,
                'grand_total' => $grandTotal,
            ];

            # Return response
            return Utility::apiSuccess('Branch-wise revenue summary', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error quotationBranchReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function quotationOwnerReport(Request $request)
    {
        try {
            # Request specific data
            $validated = $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date'],
                'branch_id' => ['nullable', 'integer'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'show_all' => ['nullable', 'boolean'],
            ]);

            # Date filter
            $from = $validated['from'] ?? null;
            $to = $validated['to'] ?? null;
            $branchId = $validated['branch_id'] ?? null;
            $showAll = (bool) ($validated['show_all'] ?? false);
            $limit = $validated['limit'] ?? 10;

            # Get ownner details
            $owners = Owner::withSum([
                'quotations' => function ($q) use ($from, $to, $branchId) {
                    if ($from)
                        $q->whereDate('created_at', '>=', $from);
                    if ($to)
                        $q->whereDate('created_at', '<=', $to);
                    if ($branchId)
                        $q->where('branch_id', $branchId);
                }
            ], 'total_amount')
                ->get()
                ->sortByDesc(fn($o) => (float) ($o->quotations_sum_total_amount ?? 0))
                ->values();

            # Build categories & data
            if ($showAll) {
                $use = $owners;
            } else {
                $top = $owners->take($limit);
                $others = $owners->slice($limit);
                $use = $top->values();

                # bucket Others only when not showing all
                $othersSum = (float) $others->sum('quotations_sum_total_amount');
                if ($others->count() > 0 && $othersSum > 0) {
                    $use = $use->push((object) [
                        'name' => 'Others',
                        'quotations_sum_total_amount' => $othersSum,
                    ]);
                }
            }

            # Map result into format
            $categories = $use->map(fn($o) => (string) ($o->name ?? '—'))->toArray();
            $seriesData = $use->map(fn($o) => (float) ($o->quotations_sum_total_amount ?? 0))->toArray();

            # Get total y axis data
            $grandTotal = array_sum($seriesData);
            $yMax = $this->niceCeil(max($seriesData) ?: 0);

            # rontend sizing hint: ~28px per row, min 260px
            $heightHint = max(260, 28 * count($categories));


            $response = [
                'categories' => $categories,
                'series' => [['name' => 'Amount', 'data' => $seriesData]],
                'grand_total' => $grandTotal,
                'y_max' => $yMax,
                'height_hint' => $heightHint,
                'meta' => [
                    'count' => count($categories),
                    'show_all' => $showAll,
                ],
            ];

            # Return response
            return Utility::apiSuccess('Owner-wise revenue (All owners)', $response, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error quotationOwnerReport', ['exception' => $ex->getMessage()]);
        }
    }

    private function niceCeil(float $n): float
    {
        if ($n <= 0)
            return 0;
        $log = floor(log10($n));
        $base = pow(10, $log);
        // Mantissas 1, 2, 5, 10 style
        $steps = [1, 2, 5, 10];
        foreach ($steps as $s) {
            $candidate = $s * $base;
            if ($candidate >= $n)
                return $candidate;
        }
        return 10 * $base;
    }




}

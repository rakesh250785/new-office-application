<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\PendingQuotation;
use App\Models\QuotationDetail;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuotationSummaryController extends Controller
{
    public function quotationStatusReport(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date'],
                'branch_id' => ['nullable', 'integer'],
                'user_id' => ['nullable', 'integer'],
            ]);

            // Base query
            $q = PendingQuotation::query();

            // Filters
            if (! empty($validated['from'])) {
                $q->whereDate('last_updated_at', '>=', $validated['from']);
            }
            if (! empty($validated['to'])) {
                $q->whereDate('last_updated_at', '<=', $validated['to']);
            }
            if (! empty($validated['branch_id'])) {
                $q->where('branch_id', $validated['branch_id']);
            }
            if (! empty($validated['user_id'])) {
                $q->where('user_id', $validated['user_id']);
            }

            // Aggregate query (single hit)
            $row = $q->selectRaw("
                SUM(CASE WHEN status_code = 'open'   THEN 1 ELSE 0 END) AS open_cnt,
                SUM(CASE WHEN status_code = 'open'   THEN total_amount ELSE 0 END) AS open_amt,
        
                SUM(CASE WHEN status_code = 'win'    THEN 1 ELSE 0 END) AS win_cnt,
                SUM(CASE WHEN status_code = 'win'    THEN total_amount ELSE 0 END) AS win_amt,
        
                SUM(CASE WHEN status_code = 'lose'   THEN 1 ELSE 0 END) AS lose_cnt,
                SUM(CASE WHEN status_code = 'lose'   THEN total_amount ELSE 0 END) AS lose_amt,
        
                SUM(CASE WHEN status_code = 'closed' THEN 1 ELSE 0 END) AS closed_cnt,
                SUM(CASE WHEN status_code = 'closed' THEN total_amount ELSE 0 END) AS closed_amt
            ")->first();

            // Formatter
            $fmt = fn ($v) => round((float) $v, 2);

            // Totals
            $totalCount =
                ($row->open_cnt ?? 0) +
                ($row->win_cnt ?? 0) +
                ($row->lose_cnt ?? 0) +
                ($row->closed_cnt ?? 0);

            $totalAmount =
                ($row->open_amt ?? 0) +
                ($row->win_amt ?? 0) +
                ($row->lose_amt ?? 0) +
                ($row->closed_amt ?? 0);

            // Payload
            $payload = [
                'labels' => [
                    'open ('.$fmt($row->open_amt ?? 0).')',
                    'win ('.$fmt($row->win_amt ?? 0).')',
                    'lose ('.$fmt($row->lose_amt ?? 0).')',
                    'close ('.$fmt($row->closed_amt ?? 0).')',
                    'total_amount ('.$fmt($totalAmount).')',
                ],
                'series' => [
                    (float) ($row->open_cnt ?? 0),
                    (float) ($row->win_cnt ?? 0),
                    (float) ($row->lose_cnt ?? 0),
                    (float) ($row->closed_cnt ?? 0),                
                ],
                'total' => $totalCount,
                'filters' => [
                    'from' => $validated['from'] ?? null,       
                    'to' => $validated['to'] ?? null,
                    'branch_id' => $validated['branch_id'] ?? null,
                    'user_id' => $validated['user_id'] ?? null,
                ],
            ];

            return Utility::apiSuccess('Quotation status summary', $payload, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError(
                'Error quotationStatusReport',
                ['exception' => $ex->getMessage()]
            );
        }   

    }

    public function quotationBranchReport(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $query = DB::table('pending_quotations as pqd')
            ->join('quotations as q', 'q.id', '=', 'pqd.quotation_id')
            ->join('branches as b', 'b.id', '=', 'q.branch_id')
            ->selectRaw("
            b.id,
            b.name,
            SUM(CASE WHEN pqd.status_code = 'open'   THEN pqd.total_amount ELSE 0 END) AS open_amount,
            SUM(CASE WHEN pqd.status_code = 'win'    THEN pqd.total_amount ELSE 0 END) AS win_amount,
            SUM(CASE WHEN pqd.status_code = 'lose'   THEN pqd.total_amount ELSE 0 END) AS lose_amount,
            SUM(CASE WHEN pqd.status_code = 'closed' THEN pqd.total_amount ELSE 0 END) AS closed_amount,
            SUM(pqd.total_amount) AS total_amount
        ")
            ->groupBy('b.id', 'b.name')
            ->orderBy('b.name');

        if ($from) {
            $query->whereDate('q.created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('q.created_at', '<=', $to);
        }

        $rows = $query->get();

        // Chart-ready response
        $categories = [];
        $series = [
            'open' => [],
            'win' => [],
            'lose' => [],
            'closed' => [],
        ];

        $grandTotal = 0;

        foreach ($rows as $r) {
            $categories[] = ucfirst(substr($r->name, 0, 3));

            $series['open'][] = round((float) $r->open_amount, 2);
            $series['win'][] = round((float) $r->win_amount, 2);
            $series['lose'][] = round((float) $r->lose_amount, 2);
            $series['closed'][] = round((float) $r->closed_amount, 2);

            $grandTotal += (float) $r->total_amount;
        }

        return Utility::apiSuccess('Branch-wise quotation status summary', [
            'categories' => $categories,
            'series' => [
                ['name' => 'Open',   'data' => $series['open']],
                ['name' => 'Win',    'data' => $series['win']],
                ['name' => 'Lose',   'data' => $series['lose']],
                ['name' => 'Closed', 'data' => $series['closed']],
            ],
            'grand_total' => round($grandTotal, 2),
        ]);
    }

    public function quotationOwnerReport(Request $request)
    {
        try {
            // Request specific data
            $validated = $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date'],
                'branch_id' => ['nullable', 'integer'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'show_all' => ['nullable', 'boolean'],
            ]);

            // Date filter
            $from = $validated['from'] ?? null;
            $to = $validated['to'] ?? null;
            $branchId = $validated['branch_id'] ?? null;
            $showAll = (bool) ($validated['show_all'] ?? false);
            $limit = $validated['limit'] ?? 10;

            // Get ownner details
            $owners = Owner::withSum([
                'quotations' => function ($q) use ($from, $to, $branchId) {
                    if ($from) {
                        $q->whereDate('created_at', '>=', $from);
                    }
                    if ($to) {
                        $q->whereDate('created_at', '<=', $to);
                    }
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                },
            ], 'total_amount')
                ->get()
                ->sortByDesc(fn ($o) => (float) ($o->quotations_sum_total_amount ?? 0))
                ->values();

            // Build categories & data
            if ($showAll) {
                $use = $owners;
            } else {
                $top = $owners->take($limit);
                $others = $owners->slice($limit);
                $use = $top->values();

                // bucket Others only when not showing all
                $othersSum = (float) $others->sum('quotations_sum_total_amount');
                if ($others->count() > 0 && $othersSum > 0) {
                    $use = $use->push((object) [
                        'name' => 'Others',
                        'quotations_sum_total_amount' => $othersSum,
                    ]);
                }
            }

            // Map result into format
            $categories = $use->map(fn ($o) => (string) ($o->name ?? '—'))->toArray();
            $seriesData = $use->map(fn ($o) => (float) ($o->quotations_sum_total_amount ?? 0))->toArray();

            // Get total y axis data
            $grandTotal = array_sum($seriesData);
            $yMax = $this->niceCeil(max($seriesData) ?: 0);

            // rontend sizing hint: ~28px per row, min 260px
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

            // Return response
            return Utility::apiSuccess('Owner-wise revenue (All owners)', $response, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error quotationOwnerReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function quotationPrincipalDealerReport(Request $request)
    {
        try {

            // Request input
            $data = $request->validate([
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
            ]);

            // Date filter
            $end = ! empty($data['end_date']) ? Carbon::parse($data['end_date'])->endOfDay() : now()->endOfDay();
            $start = ! empty($data['start_date']) ? Carbon::parse($data['start_date'])->startOfDay() : (clone $end)->subMonths(5)->startOfMonth();

            // Month buckets
            $months = [];
            for ($c = (clone $start)->startOfMonth(), $s = (clone $end)->startOfMonth(); $c <= $s; $c->addMonth()) {
                $months[] = ['ym' => $c->format('Y-m'), 'label' => $c->format('M')];
            }
            $monthsOut = array_column($months, 'label');
            $monthKeys = array_column($months, 'ym');
            $zeroRow = array_fill_keys($monthKeys, 0.0);

            // One query with proper aliases
            $rows = QuotationDetail::query()
                ->join('principals as p', 'p.id', '=', 'quotation_details.principal_id')
                ->join('principal_types as pt', 'pt.id', '=', 'p.type_id')
                ->when(! empty($data['start_date']) || ! empty($data['end_date']), function ($q) use ($start, $end) {
                    $q->whereBetween('quotation_details.created_at', [$start, $end]);
                })
                ->selectRaw("DATE_FORMAT(quotation_details.created_at, '%Y-%m') as ym")
                ->selectRaw('SUM(quotation_details.total) as total_amount')
                ->selectRaw('pt.type as ptype')
                ->selectRaw('p.id as principal_id')
                ->selectRaw('p.type as principal_name')
                ->groupBy('ym', 'ptype', 'principal_id', 'principal_name')
                ->get();

            // Accumulate data
            $authorizedByPrincipal = [];
            $dealerByMonth = $zeroRow;

            foreach ($rows as $r) {
                $ym = $r->ym;
                $sum = (float) $r->total_amount;

                if ($r->ptype === 'Authorized') {
                    $name = $r->principal_name ?: 'Unknown';
                    $authorizedByPrincipal[$name] ??= $zeroRow;
                    if (isset($authorizedByPrincipal[$name][$ym])) {
                        $authorizedByPrincipal[$name][$ym] += $sum;
                    }
                } elseif ($r->ptype === 'Dealers') {
                    if (isset($dealerByMonth[$ym])) {
                        $dealerByMonth[$ym] += $sum;
                    }
                }
            }

            // Shape output
            $principalsOut = [];
            foreach ($authorizedByPrincipal as $name => $perMonth) {
                $principalsOut[] = [
                    'name' => $name,
                    'data' => array_map(fn ($m) => round($perMonth[$m['ym']] ?? 0, 2), $months),
                    'color' => $this->colorFromString($name),
                ];
            }

            $dealerOut = [
                'name' => 'Dealer',
                'data' => array_map(fn ($m) => round($dealerByMonth[$m['ym']] ?? 0, 2), $months),
                'color' => $this->colorFromString('Dealer'),
            ];

            // Response
            $response = [
                'months' => $monthsOut,
                'principals' => $principalsOut,
                'dealer' => $dealerOut,
            ];

            // Return response
            return Utility::apiSuccess('Authorized vs dealer data', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error quotationOwnerReport', ['exception' => $ex->getMessage()]);
        }
    }

    private function colorFromString(string $key, int $s = 65, int $l = 55): string
    {
        $hash = crc32(strtoupper($key));
        $h = $hash % 360;

        return $this->hslToHex($h, $s, $l);
    }

    private function hslToHex(int $h, int $s, int $l): string
    {
        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        $r = $g = $b = 0;
        if ($h < 60) {
            $r = $c;
            $g = $x;
            $b = 0;
        } elseif ($h < 120) {
            $r = $x;
            $g = $c;
            $b = 0;
        } elseif ($h < 180) {
            $r = 0;
            $g = $c;
            $b = $x;
        } elseif ($h < 240) {
            $r = 0;
            $g = $x;
            $b = $c;
        } elseif ($h < 300) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }

        $to255 = fn ($v) => (int) round(($v + $m) * 255);

        return sprintf('#%02X%02X%02X', $to255($r), $to255($g), $to255($b));
    }

    private function niceCeil(float $n): float
    {
        if ($n <= 0) {
            return 0;
        }
        $log = floor(log10($n));
        $base = pow(10, $log);
        // Mantissas 1, 2, 5, 10 style
        $steps = [1, 2, 5, 10];
        foreach ($steps as $s) {
            $candidate = $s * $base;
            if ($candidate >= $n) {
                return $candidate;
            }
        }

        return 10 * $base;
    }
}

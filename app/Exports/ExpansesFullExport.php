<?php

namespace App\Exports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ExpansesFullExport implements FromCollection, WithEvents
{
    protected Collection $rows;

    /**
     * Accept Collection|array|LengthAwarePaginator and normalize to Collection of arrays.
     *
     * @param  mixed  $items
     */
    public function __construct($items)
    {
        if ($items instanceof LengthAwarePaginator) {
            $items = $items->items();
        }

        $this->rows = collect($items)->map(function ($r) {
            if (is_object($r)) {
                return method_exists($r, 'toArray') ? $r->toArray() : (array) $r;
            }

            return (array) $r;
        })->values();
    }

    /**
     * Build row collection for Excel.
     *
     * @return Collection
     */
    public function collection()
    {
        $out = collect();

        // Top header
        $username = $this->rows->first()['user']['user_name'] ?? '';
        $branch = $this->rows->first()['user']['branch']['name'] ?? '';
        $out->push(['', 'Name - '.$username, '', 'Branch - '.$branch, '', '', "October' 25", '']);

        // Blank
        $out->push([]);

        // Column headers
        $out->push([
            'Date',
            'Travel / Route Description (To)',
            'Travel / Route Description (From)',
            'Company Name',
            'Purpose of Visit',
            'Accompanying Person/s',
            'Travel Mode',
            'Travel Amt',
            'Food B/L/D',
            'Hotel',
            'Others',
            'Total',
        ]);

        // Data rows
        foreach ($this->rows as $rec) {
            logger('ckckckckck');
            logger($rec);
            $t = $rec['travel_expanses'] ?? [];
            $p = $rec['link_order'] ?? [];

            $totals = $t['totals'] ?? [];
            $travelTotal = $totals['travel'] ?? 0;
            $foodTotal = $totals['food'] ?? 0;
            $hotelTotal = $totals['hotel'] ?? 0;
            $othersTotal = $totals['others'] ?? 0;
            $grandTotal = $totals['grand'] ?? 0;

            $accomp = '';
            if (! empty($t['accompanying']) && is_array($t['accompanying'])) {
                $names = array_column($t['accompanying'], 'name');
                $accomp = implode(', ', array_filter($names));
            }

            $legs = $t['legs'] ?? [];
            if (is_array($legs) && count($legs) > 0) {
                foreach ($legs as $idx => $leg) {
                    $to = $leg['to'] ?? '';
                    $from = $leg['from'] ?? '';
                    $mode = $leg['mode'] ?? '';
                    $amt = $leg['amount'] ?? '';

                    if ($idx === 0) {
                        $out->push([
                            $rec['created_at'] ?? '',
                            $to,
                            $from,
                            $rec['company']['company_name'] ?? '',
                            $p['purpose'] ?? '',
                            $accomp,
                            $mode,
                            $amt,
                            $foodTotal ?: '',
                            $hotelTotal ?: '',
                            $othersTotal ?: '',
                            $grandTotal ?: '',
                        ]);
                    } else {
                        $out->push([
                            '',
                            $to,
                            $from,
                            '',
                            '',
                            '',
                            $mode,
                            $amt,
                            '',
                            '',
                            '',
                            $amt,
                        ]);
                    }
                }
            } else {
                // Single row when no legs
                $out->push([
                    $rec['created_at'] ?? '',
                    '',
                    '',
                    $rec['company']['company_name'] ?? '',
                    $t['purpose'] ?? '',
                    $accomp,
                    '',
                    $travelTotal ?: '',
                    $foodTotal ?: '',
                    $hotelTotal ?: '',
                    $othersTotal ?: '',
                    $grandTotal ?: '',
                ]);
            }
        }

        // ===== Bottom totals (same layout as screenshot) =====
        $sumTravel = $this->rows->sum(fn ($r) => $r['travel_expanses']['totals']['travel'] ?? 0);
        $sumFood = $this->rows->sum(fn ($r) => $r['travel_expanses']['totals']['food'] ?? 0);
        $sumHotel = $this->rows->sum(fn ($r) => $r['travel_expanses']['totals']['hotel'] ?? 0);
        $sumOthers = $this->rows->sum(fn ($r) => $r['travel_expanses']['totals']['others'] ?? 0);
        $sumGrand = $this->rows->sum(fn ($r) => $r['travel_expanses']['totals']['grand'] ?? 0);

        $advance = $this->rows->sum(fn ($r) => intval($r['payment_bill']['advance_payment'] ?? 0));
        $finalPaid = $sumGrand - $advance;

        $out->push([]); // spacer
        $out->push(['', '', '', '', '', '', 'Total Expense Amount', $sumTravel, $sumFood, $sumHotel, $sumOthers, $sumGrand]);
        $out->push(['', '', '', '', '', '', 'Advance Payment', '', '', '', '', $advance]);
        $out->push(['', '', '', '', '', '', 'Final Amount', '', '', '', '', $finalPaid]);

        return $out;
    }

    /**
     * Styling and formatting applied on AfterSheet.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highest = $sheet->getHighestRow();

                // Bold top and header rows
                $sheet->getStyle('A1:L1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2:L2')->getFont()->setBold(true);

                // Dark border for header rows
                $sheet->getStyle('A1:L2')->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
                    ->getColor()->setARGB('FF000000'); // black

                $sheet->getStyle('A1:L2')->getBorders()->getInside()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->getColor()->setARGB('FF000000'); // black

                // Auto-size columns A..L
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Borders for entire data region (header row 3 to last)
                $sheet->getStyle("A3:L{$highest}")->getBorders()
                    ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Number format for amount columns H..L (columns 8..12)
                $amountCols = ['H', 'I', 'J', 'K', 'L'];
                foreach ($amountCols as $c) {
                    $sheet->getStyle("{$c}4:{$c}{$highest}")
                        ->getNumberFormat()->setFormatCode('#,##0');
                }

                // Identify totals rows (last three rows)
                $totalsRowEnd = $highest;
                $totalsRowStart = max(4, $totalsRowEnd - 2); // safety

                // Green boxed outline for totals area (G..L)
                $rangeBox = "G{$totalsRowStart}:L{$totalsRowEnd}";

                // Fill header label (first totals row) light green background and bold
                $sheet->getStyle("G{$totalsRowStart}:G{$totalsRowStart}")
                    ->getFont()->setBold(true);

                // Apply background color for the three totals label cells (G)
                $sheet->getStyle("G{$totalsRowStart}:G{$totalsRowEnd}")->getFont()->setBold(true);
                $sheet->getStyle($rangeBox)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFDFF2E1'); // pale green

                // Outer thick green border
                $sheet->getStyle($rangeBox)->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)
                    ->getColor()->setARGB('FF000000'); // green

                $sheet->getStyle($rangeBox)->getFont()->setBold(true);
                $sheet->getStyle("L3:L{$highest}")->getFont()->setBold(true);

                // Thin inner borders
                $sheet->getStyle($rangeBox)->getBorders()->getInside()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Bold and center align the totals labels column (G)
                $sheet->getStyle("G{$totalsRowStart}:G{$totalsRowEnd}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("H{$totalsRowStart}:L{$totalsRowEnd}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // Optional: merge Name top cells to visually match screenshot
                // Merge B1:C1 (Name label)
                try {
                    $sheet->mergeCells('B1:C1');
                    // Merge E1:G1 for branch/title area if desired
                    $sheet->mergeCells('E1:G1');
                } catch (\Exception $e) {
                    // ignore merge failures in rare edge cases
                }
            },
        ];
    }
}

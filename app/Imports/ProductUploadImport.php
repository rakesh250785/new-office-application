<?php

namespace App\Imports;

use App\Models\ImportJob;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductUploadImport implements ShouldQueue, ToCollection, WithChunkReading, WithHeadingRow
{
    public int $jobId;
    public array $updateCols;

    /** column datatype definition */
    private array $columnTypes = [
        'price' => 'float',
        'quantity' => 'int',
        'igst_rate' => 'float',
        'discount' => 'float',

        'hsn_no' => 'string',
        'uom' => 'string',
        'description' => 'string',
        'additional_description' => 'string',
        'specification' => 'string',
    ];

    /** columns having *_updated_at */
    private array $timestampColumns = [
        'price',
        'quantity',
    ];

    public function __construct(int $jobId, array $updateCols)
    {
        $this->jobId = $jobId;

        $allowed = array_keys($this->columnTypes);

        $this->updateCols = array_values(
            array_intersect($allowed, array_map('strtolower', $updateCols))
        );
    }

    public function collection(Collection $rows)
    {
        \Log::debug('Import chunk: '.$rows->count());

        if ($job = ImportJob::find($this->jobId)) {
            $job->increment('processed_rows', $rows->count());
            $job->update([
                'status' => $job->processed_rows >= $job->total_rows
                    ? 'completed'
                    : 'processing'
            ]);
        }

        if (empty($this->updateCols)) {
            return;
        }

        $now = Carbon::now()->toDateTimeString();

        /*
        |--------------------------------------------------------------------------
        | Normalize Excel rows
        |--------------------------------------------------------------------------
        */
        $batch = $rows->map(function ($r) {

            $part = isset($r['part_no']) ? trim((string)$r['part_no']) : null;
            if (!$part) return null;

            $rec = ['part_no' => $part];

            foreach ($this->updateCols as $col) {

                if (!isset($r[$col]) || $r[$col] === '') {
                    continue;
                }

                $value = $r[$col];
                $type  = $this->columnTypes[$col] ?? 'string';

                switch ($type) {
                    case 'float':
                        $rec[$col] = is_numeric($value) ? (float)$value : 0.0;
                        break;

                    case 'int':
                        $rec[$col] = is_numeric($value) ? (int)$value : 0;
                        break;

                    default:
                        $rec[$col] = trim((string)$value);
                }
            }

            return count($rec) > 1 ? $rec : null;

        })->filter()->values()->all();

        if (empty($batch)) return;

        /*
        |--------------------------------------------------------------------------
        | Bulk update in chunks
        |--------------------------------------------------------------------------
        */
        foreach (array_chunk($batch, 500) as $chunk) {

            $partNos = array_column($chunk, 'part_no');
            if (empty($partNos)) continue;

            $existing = Product::whereIn('part_no', $partNos)
                ->pluck('part_no')
                ->all();

            if (empty($existing)) continue;

            // fast lookup map
            $existingMap = array_flip($existing);

            $toUpdate = array_values(array_filter(
                $chunk,
                fn($r) => isset($existingMap[$r['part_no']])
            ));

            if (empty($toUpdate)) continue;

            $setFragments = [];
            $allBindings  = [];

            foreach ($this->updateCols as $col) {

                $caseParts = [];
                $caseBindings = [];

                foreach ($toUpdate as $r) {
                    if (!array_key_exists($col, $r)) continue;

                    $caseParts[] = 'WHEN ? THEN ?';
                    $caseBindings[] = $r['part_no'];
                    $caseBindings[] = $r[$col];
                }

                if (empty($caseParts)) continue;

                $caseSql = 'CASE part_no '.implode(' ', $caseParts)." ELSE {$col} END";

                // value update
                $setFragments[] = "{$col} = {$caseSql}";
                $allBindings = array_merge($allBindings, $caseBindings);

                // timestamp update (only supported columns)
                if (in_array($col, $this->timestampColumns, true)) {

                    $placeholders = implode(',', array_fill(0, count($existing), '?'));

                    $timeCase = "CASE WHEN part_no IN ({$placeholders})
                                 THEN ?
                                 ELSE {$col}_updated_at END";

                    $setFragments[] = "{$col}_updated_at = {$timeCase}";
                    $allBindings = array_merge($allBindings, $existing, [$now]);
                }
            }

            if (empty($setFragments)) continue;

            $wherePlaceholders = implode(',', array_fill(0, count($existing), '?'));

            $sql = 'UPDATE products SET '
                . implode(', ', $setFragments)
                . " WHERE part_no IN ({$wherePlaceholders})";

            $finalBindings = array_merge($allBindings, $existing);

            DB::update($sql, $finalBindings);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
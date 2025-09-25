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
    /** @var string[] columns to update, e.g. ['price','quantity'] */
    public array $updateCols;

    /**
     * @param int $jobId
     * @param string[] $updateCols
     */
    public function __construct(int $jobId, array $updateCols)
    {
        $this->jobId = $jobId;
        // normalize lower-case column names and keep only allowed ones
        $allowed = ['price', 'quantity'];
        $this->updateCols = array_values(array_intersect($allowed, array_map('strtolower', $updateCols)));
    }

    public function collection(Collection $rows)
    {
        \Log::debug('Import chunk: '.$rows->count());

        $importJob = ImportJob::find($this->jobId);
        if ($importJob) {
            $importJob->increment('processed_rows', $rows->count());
            $importJob->update(['status' => $importJob->processed_rows >= $importJob->total_rows ? 'completed' : 'processing']);
        }

        if (empty($this->updateCols)) {
            // nothing to do
            return;
        }

        $now = Carbon::now()->toDateTimeString();

        // Build normalized batch: each item contains part_no and any present update columns
        $batch = $rows->map(function ($r) {
            $part = isset($r['part_no']) ? trim((string)$r['part_no']) : null;
            if (! $part) return null;
            $rec = ['part_no' => $part];
            foreach ($this->updateCols as $col) {
                if (isset($r[$col]) && $r[$col] !== '') {
                    $rec[$col] = $col === 'price' ? (is_numeric($r[$col]) ? (float)$r[$col] : 0.0)
                                  : (is_numeric($r[$col]) ? (int)$r[$col] : 0);
                }
            }
            // if none of requested cols present, skip
            return count($rec) > 1 ? $rec : null;
        })->filter()->values()->all();

        if (empty($batch)) return;

        foreach (array_chunk($batch, 500) as $chunk) {
            $partNos = array_column($chunk, 'part_no');
            if (empty($partNos)) continue;

            $existing = Product::whereIn('part_no', $partNos)->pluck('part_no')->all();
            if (empty($existing)) continue;

            // Filter to only existing SKUs
            $toUpdate = array_values(array_filter($chunk, fn($r) => in_array($r['part_no'], $existing, true)));
            if (empty($toUpdate)) continue;

            // Build SET parts and bindings for each requested column
            $setFragments = [];
            $allBindings = [];

            foreach ($this->updateCols as $col) {
                // Build CASE only if at least one row has this col
                $caseParts = [];
                $caseBindings = [];
                foreach ($toUpdate as $r) {
                    if (! array_key_exists($col, $r)) continue;
                    $caseParts[] = 'WHEN ? THEN ?';
                    $caseBindings[] = $r['part_no'];
                    $caseBindings[] = $r[$col];
                }
                if (empty($caseParts)) continue;

                $caseSql = 'CASE part_no '.implode(' ', $caseParts).' ELSE '.$col.' END';
                $inPlaceholders = implode(',', array_fill(0, count($existing), '?'));
                $timeCase = "CASE WHEN part_no IN ({$inPlaceholders}) THEN ? ELSE {$col}_updated_at END";

                $setFragments[] = "{$col} = {$caseSql}";
                $setFragments[] = "{$col}_updated_at = {$timeCase}";

                // bindings: caseBindings, then existing part_nos (for timeCase)
                $allBindings = array_merge($allBindings, $caseBindings, $existing, [$now]);
            }

            if (empty($setFragments)) continue;

            // WHERE placeholders (existing part_nos)
            $wherePlaceholders = implode(',', array_fill(0, count($existing), '?'));
            $sql = 'UPDATE products SET '.implode(', ', $setFragments).' WHERE part_no IN ('.$wherePlaceholders.')';

            // final bindings: previously added bindings + existing part_nos for WHERE
            $finalBindings = array_merge($allBindings, $existing);

            DB::update($sql, $finalBindings);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

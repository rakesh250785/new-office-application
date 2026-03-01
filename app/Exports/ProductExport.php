<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Schema;

class ProductExport implements FromCollection, ShouldQueue, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    /** @var array<string,mixed> */
    protected array $filters;

    /** @var array<string,string> key (dot path) => heading */
    protected array $columns;

    /** @var class-string<\Illuminate\Database\Eloquent\Model> */
    protected string $modelClass;

    /**
     * @param  array  $filters  Expect keys: search,start_date,end_date,principal_list,brand_list,category_list,branch_list
     * @param  array  $columns  ['part_no'=>'Part No.', 'category.name'=>'Category', ...]
     * @param  string  $modelClass  Usually \App\Models\Product::class
     */
    public function __construct(array $filters, array $columns, string $modelClass)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
    }

    /**
     * Build and collect entire dataset in chunks.
     */
    public function collection(): Collection
    {
        /** @var Builder $query */
        $query = ($this->modelClass)::query()
            ->with([
                'branch:id,name',
                'principal:id,type',
                'category:id,name',
                'brand:id,name',
            ])
            ->whereNull('deleted_at');

        if (! empty($this->filters['column']) && is_array($this->filters['column'])) {

            foreach ($this->filters['column'] as $column => $value) {

                // skip empty values
                if ($value === null || $value === '') {
                    continue;
                }

                if (Schema::hasColumn('products', $column)) {
                    $query->where($column, $value);
                }
            }
        }

        // Search (mirrors controller)
        if (! empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('part_no', 'like', "%{$s}%")
                    ->orWhere('hsn_no', 'like', "%{$s}%")
                    ->orWhere('price', 'like', "%{$s}%")
                    ->orWhere('uom', 'like', "%{$s}%")
                    ->orWhere('igst_rate', 'like', "%{$s}%")
                    ->orWhere('discount', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('additional_description', 'like', "%{$s}%")
                    ->orWhere('specification', 'like', "%{$s}%")
                    ->orWhere('category_id', 'like', "%{$s}%")
                    ->orWhere('quantity', 'like', "%{$s}%")
                    ->orWhere('price_updated_at', 'like', "%{$s}%")
                    ->orWhere('quantity_updated_at', 'like', "%{$s}%")
                    ->orWhereHas('principal', fn ($b) => $b->where('type', 'like', "%{$s}%"))
                    ->orWhereHas('category', fn ($b) => $b->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$s}%"));
            });
        }

        if (! empty($this->filters['principal_list'])) {
            $query->where('principal_id', $this->filters['principal_list']);
        }
        if (! empty($this->filters['category_list'])) {
            $query->where('category_id', $this->filters['category_list']);
        }
        if (! empty($this->filters['brand_list'])) {
            $query->where('brand_id', $this->filters['brand_list']);
        }
        if (! empty($this->filters['branch_list'])) {
            $query->where('branch_id', $this->filters['branch_list']);
        }

        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ]);
        }

        // Gather rows in chunks
        $all = collect();
        $query->orderBy('id')->chunk(5000, function ($rows) use (&$all) {
            foreach ($rows as $row) {
                $all->push($row);
            }
        });

        return $all;
    }

    /**
     * Map each row to the ordered columns.
     */
    public function map($row): array
    {
        $vals = [];
        foreach (array_keys($this->columns) as $key) {
            $val = $this->valueFor($row, $key);

            // Friendly date formatting for *_at or *date keys
            if ($this->looksLikeDateKey($key)) {
                $val = $this->formatDate($val);
            }

            // Clean HTML from long text fields
            if (is_string($val) && $this->looksLikeHtmlyKey($key)) {
                $val = $this->cleanText($val);
            }

            $vals[] = $val;
        }

        return $vals;
    }

    /**
     * Excel headings.
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Chunk size for reading.
     */
    public function chunkSize(): int
    {
        return 5000;
    }

    // -------- helpers --------

    /**
     * data_get with smart fallback:
     * - If key ends with ".name" and empty, try ".type"
     */
    protected function valueFor($row, string $key)
    {
        $val = data_get($row, $key);

        if (($val === null || $val === '') && Str::endsWith($key, '.name')) {
            $fallback = Str::replaceLast('.name', '.type', $key);
            $val = data_get($row, $fallback);
        }

        return $val ?? '';
    }

    protected function looksLikeDateKey(string $key): bool
    {
        $k = Str::lower($key);

        return Str::endsWith($k, ['_at', 'date']) || in_array($k, ['created_at', 'updated_at'], true);
    }

    protected function formatDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            if ($value instanceof Carbon) {
                $dt = $value;
            } elseif (is_numeric($value)) {
                $dt = Carbon::createFromTimestamp((int) $value);
            } else {
                $dt = Carbon::parse((string) $value);
            }

            return $dt->format('d-m-Y H:i');
        } catch (\Throwable $e) {
            return is_scalar($value) ? (string) $value : null;
        }
    }

    protected function looksLikeHtmlyKey(string $key): bool
    {
        $k = Str::lower($key);

        return in_array($k, ['description', 'additional_description', 'specification'], true);
    }

    protected function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text ?? '') ?? '';

        return trim($text);
    }
}

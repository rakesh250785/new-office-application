<?php

namespace App\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpansesFullExport implements FromCollection, ShouldQueue, WithHeadings, WithMapping
{
    protected Collection $rows;

    protected array $headings = [];

    protected array $flatKeys = [];

    /**
     * Accept a collection of models (with relations eager loaded)
     */
    public function __construct(Collection $rows)
    {
        $this->rows = $rows;

        // Build headings dynamically from the first row (if exists)
        if ($rows->isNotEmpty()) {
            $first = $rows->first()->toArray();
            $flat = $this->flattenArray($first);
            $this->flatKeys = array_keys($flat);
            $this->headings = $this->flatKeys;
        }
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $arr = $row->toArray();
        $flat = $this->flattenArray($arr);

        $out = [];
        foreach ($this->flatKeys as $key) {
            $val = $flat[$key] ?? null;
            $out[] = $this->formatValue($val, $key);
        }

        return $out;
    }

    protected function flattenArray(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix === '' ? $key : ($prefix.'.'.$key);

            if (is_array($value) && $this->isAssoc($value)) {
                $result = array_merge($result, $this->flattenArray($value, $fullKey));
            } elseif (is_array($value) && ! $this->isAssoc($value)) {

                $result[$fullKey] = $value;
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    protected function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Turn arrays/JSON into readable strings
     */
    protected function formatValue($value, string $key)
    {
        // If it's JSON string, try decode
        if (is_string($value)) {
            $trim = trim($value);
            if (($trim !== '') && ($this->isJson($trim))) {
                $decoded = json_decode($trim, true);
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            if ($this->looksLikeFilesArray($value)) {
                $parts = [];
                foreach ($value as $i => $f) {
                    $num = $i + 1;
                    $name = $f['fileName'] ?? $f['name'] ?? '';
                    $url = $f['fileUrl'] ?? '';
                    $parts[] = "{$num}) {$name}".($url ? " ({$url})" : '');
                }

                return implode(' ; ', $parts);
            }

            if ($this->isAssoc($value)) {
                $pairs = [];
                foreach ($value as $k => $v) {
                    $pairs[] = "{$k}: ".(is_scalar($v) || $v === null ? ($v === null ? '' : $v) : json_encode($v));
                }

                return implode(' ; ', $pairs);
            }

            $parts = array_map(function ($el) {
                if (is_scalar($el) || $el === null) {
                    return $el === null ? '' : $el;
                }

                return json_encode($el);
            }, $value);

            return implode(' ; ', $parts);
        }

        return $value;
    }

    protected function isSequential(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }

    protected function looksLikeFilesArray(array $arr): bool
    {
        if (empty($arr) || ! $this->isSequential($arr)) {
            return false;
        }

        $first = $arr[0];

        return is_array($first) && (
            array_key_exists('fileUrl', $first) ||
            array_key_exists('fileName', $first) ||
            array_key_exists('name', $first)
        );
    }

    protected function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}

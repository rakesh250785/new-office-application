<?php

namespace App\Imports;

use App\Models\SaleReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SaleReportImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            SaleReport::updateOrCreate(
                [
                    'invoice' => $row['invoice'] ?? null,
                    'part_no' => $row['part_no'] ?? null,
                ],
                [
                    'qtr' => $row['qtr'] ?? null,
                    'month' => $row['month'] ?? null,
                    'fy_year' => $row['fy_year'] ?? null,
                    'invoice_date' => !empty($row['invoice_date']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['invoice_date']) : null,
                    'order_no' => $row['order_no'] ?? null,
                    'customer_name' => $row['customer_name'] ?? null,
                    'branch' => $row['branch'] ?? null,
                    'description' => $row['description'] ?? null,
                    'category' => $row['category'] ?? null,
                    'principal_name' => $row['principal_name'] ?? null,
                    'authorised' => $row['authorised'] ?? null,
                    'qty' => (int) ($row['qty'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),

                ]
            );
        }
    }
}

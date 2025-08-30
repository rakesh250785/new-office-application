<?php

namespace Database\Seeders;

use App\Models\SaleReport;
use Illuminate\Database\Seeder;

class SaleReportSeeder extends Seeder
{
    public function run(): void
    {
        SaleReport::factory()->count(5000)->create();
    }
}

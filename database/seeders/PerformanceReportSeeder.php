<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PerformanceReport;

class PerformanceReportSeeder extends Seeder
{
    public function run(): void
    {
        PerformanceReport::factory()->count(20000)->create();
    }
}

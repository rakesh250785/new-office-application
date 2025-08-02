<?php

namespace Database\Seeders;

use App\Models\QuotationType;
use Illuminate\Database\Seeder;

class QuotationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            1 => 'CW',
            2 => 'TW',
        ];

        foreach ($branches as $id => $name) {
            QuotationType::create([
                'id' => $id,
                'type' => $name,
            ]);
        }
    }
}

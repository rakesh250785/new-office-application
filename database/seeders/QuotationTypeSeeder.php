<?php

namespace Database\Seeders;

use App\Models\QuotationType;
use Illuminate\Database\Seeder;

class QuotationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            1 => 'CW',
            2 => 'TW',
        ];

        foreach ($types as $id => $name) {
            QuotationType::updateOrCreate(
                ['id' => $id],
                ['type' => $name]
            );
        }
    }
}

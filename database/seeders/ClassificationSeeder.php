<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            1 => 'Pharma',
            2 => 'Chemical',
            3 => 'Petrochemical',
            4 => 'Environment Food',
            5 => 'F&F',
            6 => 'Institute',
            7 => 'Accademia',
            8 => 'Dealers',
            9 => 'Others',
        ];

        foreach ($items as $id => $name) {
            Classification::updateOrCreate(
                ['id' => $id],
                ['name' => $name]
            );
        }
    }
}

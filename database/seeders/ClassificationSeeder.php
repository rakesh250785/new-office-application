<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $customerClassifications = [
            '1'=> 'Pharma',
            '2'=> 'Chemical',
            '3'=> 'Petrochemical',
            '4'=> 'Environment Food',
            '5'=> 'F&F',
            '6' => 'Institute',
            '7' => 'Accademia',
            '8' => 'Dealers',
            '9'=> 'Others',
        ];

        foreach ($customerClassifications as $id => $name) {
            Classification::create([
                'id' => $id,
                'name' => $name,
            ]);
        }
    }
}

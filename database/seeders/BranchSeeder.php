<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            1 => 'Mumbai',
            2 => 'Ahmedabad',
            3 => 'Bangalore',
            4 => 'Surat',
            5 => 'Chennai',
            6 => 'Pune',
            7 => 'Chandigarh',
            8 => 'Goa',
            9 => 'Hyderabad',
            10 => 'Indore',
            11 => 'Kolkata',
            12 => 'Delhi',
            13 => 'North-3',
            14 => 'North-East',
        ];

        foreach ($branches as $id => $name) {
            Branch::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $name,
                    'code' => Str::slug($name),
                ]
            );
        }
    }
}

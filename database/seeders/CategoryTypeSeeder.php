<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategoryType as Type;
class CategoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            1 => 'HPCL Columns',
            2 => 'GC Capillary Column',
        ];

        foreach ($branches as $id => $name) {
            Type::create([
                'id' => $id,
                'type' => $name,
            ]);
        }
    }
}

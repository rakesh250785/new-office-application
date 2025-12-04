<?php

namespace Database\Seeders;

use App\Models\CategoryType as Type;
use Illuminate\Database\Seeder;

class CategoryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            1 => 'HPCL Columns',
            2 => 'GC Capillary Column',
        ];

        foreach ($types as $id => $name) {
            Type::updateOrCreate(
                ['id' => $id],
                ['type' => $name]
            );
        }
    }
}

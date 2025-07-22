<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrincipalType;

class PrincipalTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            1 => 'Authorized',
            2 => 'Dealers',
        ];

        foreach ($branches as $id => $name) {
            PrincipalType::create([
                'id' => $id,
                'type' => $name,
            ]);
        }
    }
}

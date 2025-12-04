<?php

namespace Database\Seeders;

use App\Models\PrincipalType;
use Illuminate\Database\Seeder;

class PrincipalTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            1 => 'Authorized',
            2 => 'Dealers',
        ];

        foreach ($types as $id => $name) {
            PrincipalType::updateOrCreate(
                ['id' => $id],
                [
                    'type' => $name,
                ]
            );
        }
    }
}

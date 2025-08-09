<?php

namespace Database\Seeders;

use App\Models\ReasonType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReasonStatus extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = [
            1 => 'Win',
            2 => 'Lose',
            3 => 'Open',
            4 => 'Closed',
        ];

        foreach ($status as $id => $name) {
            ReasonType::create([
                'id' => $id,
                'type' => $name,
                'code' => Str::slug($name),
            ]);
        }

    }
}

<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = ['INR', 'USD', 'EUR', 'JPY', 'GBP', 'RMB', 'CNY'];

        foreach ($currencies as $index => $code) {
            Currency::create([
                'id' => $index + 1,
                'code' => strtolower($code),
                'name' => $code,
            ]);
        }
    }

}

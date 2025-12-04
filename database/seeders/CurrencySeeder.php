<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = ['INR', 'USD', 'EUR', 'JPY', 'GBP', 'RMB', 'CNY'];

        foreach ($currencies as $index => $code) {
            Currency::updateOrCreate(
                ['id' => $index + 1],
                [
                    'code' => strtolower($code),
                    'name' => $code,
                ]
            );
        }
    }
}

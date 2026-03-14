<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'INR', 'symbol' => '₹'],
            ['name' => 'USD', 'symbol' => '$'],
            ['name' => 'EUR', 'symbol' => '€'],
            ['name' => 'JPY', 'symbol' => '¥'],
            ['name' => 'GBP', 'symbol' => '£'],
            ['name' => 'CNY', 'symbol' => '¥'],
            ['name' => 'RMB', 'symbol' => '¥'],
            ['name' => 'AUD', 'symbol' => 'A$'],
            ['name' => 'CAD', 'symbol' => 'C$'],
            ['name' => 'CHF', 'symbol' => 'Fr'],
            ['name' => 'HKD', 'symbol' => '$'],
            ['name' => 'SGD', 'symbol' => '$'],
            ['name' => 'NZD', 'symbol' => '$'],
            ['name' => 'KRW', 'symbol' => '₩'],
            ['name' => 'THB', 'symbol' => '฿'],
            ['name' => 'MYR', 'symbol' => 'RM'],
            ['name' => 'IDR', 'symbol' => 'Rp'],
            ['name' => 'PHP', 'symbol' => '₱'],
            ['name' => 'VND', 'symbol' => '₫'],
            ['name' => 'PKR', 'symbol' => '₨'],
            ['name' => 'BDT', 'symbol' => '৳'],
            ['name' => 'LKR', 'symbol' => 'Rs'],
            ['name' => 'AED', 'symbol' => 'د.إ'],
            ['name' => 'SAR', 'symbol' => '﷼'],
            ['name' => 'QAR', 'symbol' => '﷼'],
            ['name' => 'OMR', 'symbol' => '﷼'],
            ['name' => 'KWD', 'symbol' => 'د.ك'],
            ['name' => 'TRY', 'symbol' => '₺'],
            ['name' => 'RUB', 'symbol' => '₽'],
            ['name' => 'UAH', 'symbol' => '₴'],
            ['name' => 'ZAR', 'symbol' => 'R'],
            ['name' => 'NGN', 'symbol' => '₦'],
            ['name' => 'EGP', 'symbol' => '£'],
            ['name' => 'BRL', 'symbol' => 'R$'],
            ['name' => 'MXN', 'symbol' => '$'],
            ['name' => 'ARS', 'symbol' => '$'],
            ['name' => 'CLP', 'symbol' => '$'],
            ['name' => 'COP', 'symbol' => '$'],
            ['name' => 'PEN', 'symbol' => 'S/'],
            ['name' => 'SEK', 'symbol' => 'kr'],
            ['name' => 'NOK', 'symbol' => 'kr'],
            ['name' => 'DKK', 'symbol' => 'kr'],
            ['name' => 'PLN', 'symbol' => 'zł'],
            ['name' => 'CZK', 'symbol' => 'Kč'],
            ['name' => 'HUF', 'symbol' => 'Ft'],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => strtolower($currency['name'])],
                [
                    'name' => $currency['name'],
                    'symbol' => $currency['symbol'],
                ]
            );
        }
    }
}

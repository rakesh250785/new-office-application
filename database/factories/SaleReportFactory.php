<?php

namespace Database\Factories;

use App\Models\SaleReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleReportFactory extends Factory
{
    protected $model = SaleReport::class;

    public function definition(): array
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
        ];

        $months = [
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
            'January',
            'February',
            'March'
        ];

        return [
            'qtr' => $this->faker->randomElement(['Q1', 'Q2', 'Q3', 'Q4']),
            'month' => $this->faker->randomElement($months),
            'fy_year' => '2023-2024',
            'invoice' => '2023-2024/' . str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'invoice_date' => $this->faker->date(),
            'order_no' => strtoupper($this->faker->bothify('??-###/####-####')),
            'customer_name' => $this->faker->company(),
            'branch' => $this->faker->randomElement($branches),
            'description' => $this->faker->sentence(8),
            'part_no' => strtoupper($this->faker->bothify('??#####')),
            'category' => $this->faker->randomElement([
                'HPLC Columns',
                'GC Columns',
                'Vials & Septa',
                'Accessories'
            ]),
            'principal_name' => $this->faker->randomElement([
                'Shodex',
                'Restek',
                'VDS Optilab',
                'Perkin Elmer'
            ]),
            'authorised' => $this->faker->randomElement(['Authorized', 'Others']),
            'qty' => $this->faker->numberBetween(1, 50),
            'amount' => $this->faker->randomFloat(2, 500, 5_000_000),
        ];
    }
}

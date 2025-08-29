<?php

namespace Database\Factories;

use App\Models\PerformanceReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceReportFactory extends Factory
{
    protected $model = PerformanceReport::class;

    public function definition(): array
    {
        $months = ['April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March'];
        return [
            'qtr' => $this->faker->randomElement(['Q1', 'Q2', 'Q3', 'Q4']),
            'month' => $this->faker->randomElement($months),
            'fy_year' => '22-23',
            'invoice' => '22-23/' . str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'invoice_date' => $this->faker->date(),
            'order_no' => strtoupper($this->faker->bothify('??-###/####-####')),
            'customer_name' => $this->faker->company(),
            'branch' => $this->faker->city(),
            'description' => $this->faker->sentence(8),
            'part_no' => strtoupper($this->faker->bothify('??#####')),
            'category' => $this->faker->randomElement(['HPLC Columns', 'GC Columns', 'Vials & Septa', 'Accessories']),
            'principal_name' => $this->faker->randomElement(['Shodex', 'Restek', 'VDS Optilab', 'Perkin Elmer']),
            'authorised' => $this->faker->randomElement(['Authorized', 'Others']),
            'qty' => $this->faker->numberBetween(1, 50),
            'amount' => $this->faker->randomFloat(2, 500, 5_000_000),
        ];
    }
}

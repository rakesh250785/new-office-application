<?php

namespace Database\Seeders;

use App\Models\PaymentDayAdvance;
use Illuminate\Database\Seeder;

class PaymentAdvanceDay extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            1 => 'Advance Against Proforma Invoice',
            2 => 'Against PDC',
            3 => '30 Days from Date of Shipment',
            4 => '45 Days from Date of Shipment',
            5 => '60 Days from Date of Shipment',
            6 => '90 Days from Date of Shipment',
        ];

        foreach ($branches as $id => $name) {
            PaymentDayAdvance::create([
                'id' => $id,
                'date_type' => $name,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\PaymentDayAdvance;
use Illuminate\Database\Seeder;

class PaymentAdvanceDay extends Seeder
{
    public function run(): void
    {
        $items = [
            1 => 'Advance Against Proforma Invoice',
            2 => 'Against PDC',
            3 => '30 Days from Date of Shipment',
            4 => '45 Days from Date of Shipment',
            5 => '60 Days from Date of Shipment',
            6 => '90 Days from Date of Shipment',
            7 => 'Write ...',
        ];

        foreach ($items as $id => $name) {
            PaymentDayAdvance::updateOrCreate(
                ['id' => $id],
                ['date_type' => $name]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Seed the application's exchange rates (sample data).
     */
    public function run(): void
    {
        $rateDate = now()->toDateString();

        $rates = [
            ['from' => 'USD', 'to' => 'EUR', 'rate' => '0.92'],
            ['from' => 'USD', 'to' => 'ALL', 'rate' => '94.50'],
            ['from' => 'EUR', 'to' => 'ALL', 'rate' => '102.80'],
        ];

        foreach ($rates as $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'rate_date' => $rateDate,
                    'from_currency' => $rate['from'],
                    'to_currency' => $rate['to'],
                ],
                [
                    'rate' => $rate['rate'],
                ]
            );
        }
    }
}

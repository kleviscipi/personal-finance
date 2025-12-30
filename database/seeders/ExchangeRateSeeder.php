<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds some default exchange rates to help users get started.
     * These are approximate rates as of late 2024 and should be updated
     * with real rates in production.
     */
    public function run(): void
    {
        $today = Carbon::today()->toDateString();
        
        // Base rates (using USD as base)
        $rates = [
            // USD to other currencies
            ['base' => 'USD', 'target' => 'EUR', 'rate' => '0.92'],
            ['base' => 'USD', 'target' => 'GBP', 'rate' => '0.79'],
            ['base' => 'USD', 'target' => 'JPY', 'rate' => '149.50'],
            ['base' => 'USD', 'target' => 'CHF', 'rate' => '0.88'],
            ['base' => 'USD', 'target' => 'CAD', 'rate' => '1.35'],
            ['base' => 'USD', 'target' => 'AUD', 'rate' => '1.53'],
            ['base' => 'USD', 'target' => 'ALL', 'rate' => '93.50'],
            
            // EUR to other currencies
            ['base' => 'EUR', 'target' => 'USD', 'rate' => '1.09'],
            ['base' => 'EUR', 'target' => 'GBP', 'rate' => '0.86'],
            ['base' => 'EUR', 'target' => 'JPY', 'rate' => '162.50'],
            ['base' => 'EUR', 'target' => 'CHF', 'rate' => '0.96'],
            ['base' => 'EUR', 'target' => 'ALL', 'rate' => '101.50'],
            
            // GBP to other currencies
            ['base' => 'GBP', 'target' => 'USD', 'rate' => '1.27'],
            ['base' => 'GBP', 'target' => 'EUR', 'rate' => '1.16'],
            ['base' => 'GBP', 'target' => 'JPY', 'rate' => '189.50'],
            
            // Common pairs with ALL
            ['base' => 'ALL', 'target' => 'USD', 'rate' => '0.0107'],
            ['base' => 'ALL', 'target' => 'EUR', 'rate' => '0.0098'],
        ];
        
        foreach ($rates as $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'base_currency' => $rate['base'],
                    'target_currency' => $rate['target'],
                    'date' => $today,
                ],
                [
                    'rate' => $rate['rate'],
                    'source' => 'seeder',
                ]
            );
        }
    }
}

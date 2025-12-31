<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminAccountSeeder::class);
        $this->call(OpeningBalanceCategorySeeder::class);
        $this->call(ExchangeRateSeeder::class);
    }
}

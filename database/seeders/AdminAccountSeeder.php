<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        $adminName = config('auth.admin_name', env('ADMIN_NAME', 'Admin User'));
        $adminEmail = config('auth.admin_email', env('ADMIN_EMAIL', 'admin@example.com'));
        $adminPassword = config('auth.admin_password', env('ADMIN_PASSWORD', 'password'));

        $accountName = config('auth.default_account_name', env('DEFAULT_ACCOUNT_NAME', 'Default Account'));
        $baseCurrency = config('auth.default_account_currency', env('DEFAULT_ACCOUNT_CURRENCY', 'USD'));

        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
            ]
        );

        $account = Account::firstOrCreate(
            ['name' => $accountName],
            [
                'base_currency' => $baseCurrency,
                'description' => 'Seeded default account.',
                'is_active' => true,
            ]
        );

        AccountSettings::firstOrCreate([
            'account_id' => $account->id,
        ]);

        if ($user->accounts()->where('accounts.id', $account->id)->exists()) {
            $user->accounts()->updateExistingPivot($account->id, [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);
        } else {
            $user->accounts()->attach($account->id, [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        $this->callWith(CategorySeeder::class, ['accountId' => $account->id]);
    }
}

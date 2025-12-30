<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoTransactions2025Seeder extends Seeder
{
    public function run(?int $accountId = null, ?int $userId = null): void
    {
        $accounts = $accountId
            ? Account::whereKey($accountId)->get()
            : Account::all();

        if ($accounts->isEmpty()) {
            return;
        }

        $faker = Factory::create();
        $faker->seed(2025);

        $categoryRanges = [
            'Home' => [700, 2500],
            'Food' => [5, 200],
            'Transport' => [10, 250],
            'Health' => [10, 300],
            'Education' => [20, 500],
            'Entertainment' => [10, 200],
            'Shopping' => [10, 400],
            'Savings' => [50, 800],
            'Other' => [5, 150],
        ];

        foreach ($accounts as $account) {
            $creator = $userId
                ? User::find($userId)
                : $account->users()->first();

            if (!$creator) {
                $creator = User::first();
            }

            if (!$creator) {
                continue;
            }

            $categories = $account->categories()->with('subcategories')->get();
            $incomeCategories = $categories->where('type', 'income')->values();
            $expenseCategories = $categories->where('type', 'expense')->values();

            if ($incomeCategories->isEmpty() || $expenseCategories->isEmpty()) {
                continue;
            }

            $homeCategory = $expenseCategories->firstWhere('name', 'Home');
            $savingsCategory = $expenseCategories->firstWhere('name', 'Savings');

            for ($month = 1; $month <= 12; $month++) {
                $monthStart = Carbon::create(2025, $month, 1)->startOfMonth();
                $monthEnd = Carbon::create(2025, $month, 1)->endOfMonth();

                $incomeTotal = $faker->randomFloat(2, 2800, 9000);
                $incomeCount = $faker->numberBetween(2, 3);
                $incomeSplits = $this->splitAmount($incomeTotal, $incomeCount, $faker);

                foreach ($incomeSplits as $amount) {
                    $category = $incomeCategories->random();
                    $subcategory = $this->pickSubcategory($category);
                    $date = $faker->dateTimeBetween($monthStart, $monthEnd);
                    $createdAt = Carbon::instance($date)->setTime(12, 0, 0);

                    Transaction::create([
                        'account_id' => $account->id,
                        'created_by' => $creator->id,
                        'type' => 'income',
                        'amount' => $amount,
                        'currency' => $account->base_currency,
                        'date' => $createdAt->toDateString(),
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory?->id,
                        'description' => $subcategory?->name ?? $category->name,
                        'payment_method' => $faker->randomElement(['bank_transfer', 'card', 'cash']),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                if ($homeCategory) {
                    $rentSubcategory = $this->pickSubcategory($homeCategory, ['Rent', 'Mortgage']);
                    $rentDate = $faker->dateTimeBetween(
                        $monthStart->copy()->addDays(1),
                        $monthStart->copy()->addDays(5)
                    );
                    $rentCreatedAt = Carbon::instance($rentDate)->setTime(9, 0, 0);

                    Transaction::create([
                        'account_id' => $account->id,
                        'created_by' => $creator->id,
                        'type' => 'expense',
                        'amount' => $faker->randomFloat(2, 900, 2000),
                        'currency' => $account->base_currency,
                        'date' => $rentCreatedAt->toDateString(),
                        'category_id' => $homeCategory->id,
                        'subcategory_id' => $rentSubcategory?->id,
                        'description' => $rentSubcategory?->name ?? 'Housing',
                        'payment_method' => 'bank_transfer',
                        'created_at' => $rentCreatedAt,
                        'updated_at' => $rentCreatedAt,
                    ]);

                    $utilitiesSubcategory = $this->pickSubcategory($homeCategory, ['Electricity', 'Water', 'Gas', 'Internet']);
                    $utilitiesDate = $faker->dateTimeBetween(
                        $monthStart->copy()->addDays(10),
                        $monthStart->copy()->addDays(20)
                    );
                    $utilitiesCreatedAt = Carbon::instance($utilitiesDate)->setTime(9, 0, 0);

                    Transaction::create([
                        'account_id' => $account->id,
                        'created_by' => $creator->id,
                        'type' => 'expense',
                        'amount' => $faker->randomFloat(2, 60, 220),
                        'currency' => $account->base_currency,
                        'date' => $utilitiesCreatedAt->toDateString(),
                        'category_id' => $homeCategory->id,
                        'subcategory_id' => $utilitiesSubcategory?->id,
                        'description' => $utilitiesSubcategory?->name ?? 'Utilities',
                        'payment_method' => 'bank_transfer',
                        'created_at' => $utilitiesCreatedAt,
                        'updated_at' => $utilitiesCreatedAt,
                    ]);
                }

                if ($savingsCategory) {
                    $savingsSubcategory = $this->pickSubcategory($savingsCategory);
                    $savingsDate = $faker->dateTimeBetween($monthStart, $monthEnd);
                    $savingsCreatedAt = Carbon::instance($savingsDate)->setTime(18, 0, 0);

                    Transaction::create([
                        'account_id' => $account->id,
                        'created_by' => $creator->id,
                        'type' => 'expense',
                        'amount' => $faker->randomFloat(2, 150, 800),
                        'currency' => $account->base_currency,
                        'date' => $savingsCreatedAt->toDateString(),
                        'category_id' => $savingsCategory->id,
                        'subcategory_id' => $savingsSubcategory?->id,
                        'description' => $savingsSubcategory?->name ?? 'Savings',
                        'payment_method' => 'bank_transfer',
                        'created_at' => $savingsCreatedAt,
                        'updated_at' => $savingsCreatedAt,
                    ]);
                }

                $expenseCount = $faker->numberBetween(18, 35);
                for ($i = 0; $i < $expenseCount; $i++) {
                    $category = $expenseCategories->random();
                    $subcategory = $this->pickSubcategory($category);
                    $range = $categoryRanges[$category->name] ?? [5, 200];
                    $date = $faker->dateTimeBetween($monthStart, $monthEnd);
                    $createdAt = Carbon::instance($date)->setTime(12, 0, 0);

                    Transaction::create([
                        'account_id' => $account->id,
                        'created_by' => $creator->id,
                        'type' => 'expense',
                        'amount' => $faker->randomFloat(2, $range[0], $range[1]),
                        'currency' => $account->base_currency,
                        'date' => $createdAt->toDateString(),
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory?->id,
                        'description' => $subcategory?->name ?? $category->name,
                        'payment_method' => $faker->randomElement(['card', 'cash']),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }
    }

    private function pickSubcategory(?Category $category, array $preferred = []): ?Subcategory
    {
        if (!$category) {
            return null;
        }

        if (!empty($preferred)) {
            foreach ($preferred as $name) {
                $match = $category->subcategories->firstWhere('name', $name);
                if ($match) {
                    return $match;
                }
            }
        }

        return $category->subcategories->isNotEmpty()
            ? $category->subcategories->random()
            : null;
    }

    private function splitAmount(float $total, int $parts, Generator $faker): array
    {
        if ($parts <= 1) {
            return [round($total, 2)];
        }

        $weights = [];
        for ($i = 0; $i < $parts; $i++) {
            $weights[] = $faker->randomFloat(4, 0.7, 1.3);
        }

        $weightTotal = array_sum($weights);
        $amounts = [];
        $remaining = $total;

        for ($i = 0; $i < $parts; $i++) {
            if ($i === $parts - 1) {
                $amounts[] = round($remaining, 2);
                break;
            }

            $amount = round($total * ($weights[$i] / $weightTotal), 2);
            $amounts[] = $amount;
            $remaining = round($remaining - $amount, 2);
        }

        return $amounts;
    }
}

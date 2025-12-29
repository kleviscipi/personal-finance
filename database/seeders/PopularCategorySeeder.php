<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class PopularCategorySeeder extends Seeder
{
    /**
     * Seed popular categories and subcategories.
     */
    public function run(?int $accountId = null): void
    {
        if ($accountId) {
            $account = Account::find($accountId);
            if ($account) {
                $this->seedForAccount($account);
            }
            return;
        }

        Account::query()->each(function (Account $account) {
            $this->seedForAccount($account);
        });
    }

    private function seedForAccount(Account $account): void
    {
        $popular = $this->popularCategories();
        $maxOrder = (int) Category::where('account_id', $account->id)->max('order');

        foreach ($popular as $index => $categoryData) {
            $category = Category::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $categoryData['name'],
                ],
                [
                    'type' => $categoryData['type'],
                    'icon' => $categoryData['icon'] ?? null,
                    'color' => $categoryData['color'] ?? null,
                    'is_system' => true,
                    'order' => $maxOrder + $index + 1,
                ]
            );

            foreach ($categoryData['subcategories'] as $subIndex => $subName) {
                Subcategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $subName,
                    ],
                    [
                        'is_system' => true,
                        'order' => $subIndex,
                    ]
                );
            }
        }
    }

    private function popularCategories(): array
    {
        return [
            [
                'name' => 'Utilities',
                'type' => 'expense',
                'icon' => '🔌',
                'color' => '#38bdf8',
                'subcategories' => [
                    'Electricity',
                    'Water',
                    'Gas',
                    'Internet',
                    'Mobile',
                    'Trash',
                ],
            ],
            [
                'name' => 'Insurance',
                'type' => 'expense',
                'icon' => '🛡️',
                'color' => '#6366f1',
                'subcategories' => [
                    'Health Insurance',
                    'Car Insurance',
                    'Home Insurance',
                    'Life Insurance',
                ],
            ],
            [
                'name' => 'Subscriptions',
                'type' => 'expense',
                'icon' => '📺',
                'color' => '#f97316',
                'subcategories' => [
                    'Streaming',
                    'Music',
                    'Software',
                    'News',
                    'Memberships',
                ],
            ],
            [
                'name' => 'Travel',
                'type' => 'expense',
                'icon' => '✈️',
                'color' => '#10b981',
                'subcategories' => [
                    'Flights',
                    'Hotels',
                    'Car Rental',
                    'Public Transport',
                    'Activities',
                ],
            ],
            [
                'name' => 'Kids',
                'type' => 'expense',
                'icon' => '🧸',
                'color' => '#f43f5e',
                'subcategories' => [
                    'Childcare',
                    'School',
                    'Activities',
                    'Clothing',
                    'Toys',
                ],
            ],
            [
                'name' => 'Personal',
                'type' => 'expense',
                'icon' => '🧴',
                'color' => '#a855f7',
                'subcategories' => [
                    'Haircut',
                    'Skincare',
                    'Laundry',
                    'Pharmacy',
                ],
            ],
            [
                'name' => 'Debt',
                'type' => 'expense',
                'icon' => '💳',
                'color' => '#ef4444',
                'subcategories' => [
                    'Credit Card',
                    'Student Loan',
                    'Personal Loan',
                    'Mortgage Payment',
                ],
            ],
            [
                'name' => 'Taxes',
                'type' => 'expense',
                'icon' => '🧾',
                'color' => '#f59e0b',
                'subcategories' => [
                    'Income Tax',
                    'Property Tax',
                    'Business Tax',
                ],
            ],
            [
                'name' => 'Gifts & Donations',
                'type' => 'expense',
                'icon' => '🎁',
                'color' => '#22c55e',
                'subcategories' => [
                    'Gifts',
                    'Charity',
                    'Special Events',
                ],
            ],
            [
                'name' => 'Investments',
                'type' => 'income',
                'icon' => '📈',
                'color' => '#0ea5e9',
                'subcategories' => [
                    'Dividends',
                    'Capital Gains',
                    'Interest Income',
                ],
            ],
        ];
    }
}

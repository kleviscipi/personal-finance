<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: This seeder can be called in two ways:
     * 1. Without parameters - seeds categories for ALL existing accounts
     * 2. With specific account - seeds categories for that account only
     */
    public function run(?int $accountId = null): void
    {
        if ($accountId) {
            // Seed for specific account
            $account = Account::find($accountId);
            if ($account) {
                $this->seedCategoriesForAccount($account);
            }
        } else {
            // Seed for all accounts
            $accounts = Account::all();
            
            foreach ($accounts as $account) {
                $this->seedCategoriesForAccount($account);
            }
        }
    }
    
    public function seedCategoriesForAccount(Account $account): void
    {
        $defaultCategories = $this->getDefaultCategories();
        
        foreach ($defaultCategories as $categoryData) {
            $category = Category::create([
                'account_id' => $account->id,
                'name' => $categoryData['name'],
                'icon' => $categoryData['icon'] ?? null,
                'color' => $categoryData['color'] ?? null,
                'type' => $categoryData['type'],
                'is_system' => true,
                'order' => $categoryData['order'],
            ]);
            
            if (isset($categoryData['subcategories'])) {
                foreach ($categoryData['subcategories'] as $index => $subName) {
                    Subcategory::create([
                        'category_id' => $category->id,
                        'name' => $subName,
                        'is_system' => true,
                        'order' => $index,
                    ]);
                }
            }
        }
    }
    
    private function getDefaultCategories(): array
    {
        return [
            // Income Categories
            [
                'name' => 'Income',
                'icon' => '💰',
                'color' => '#10b981',
                'type' => 'income',
                'order' => 0,
                'subcategories' => [
                    'Salary',
                    'Freelance',
                    'Investments',
                    'Business',
                    'Other Income',
                ],
            ],
            
            // Expense Categories
            [
                'name' => 'Food',
                'icon' => '🍽️',
                'color' => '#f59e0b',
                'type' => 'expense',
                'order' => 1,
                'subcategories' => [
                    'Groceries',
                    'Restaurant',
                    'Coffee',
                    'Fast Food',
                ],
            ],
            [
                'name' => 'Home',
                'icon' => '🏠',
                'color' => '#3b82f6',
                'type' => 'expense',
                'order' => 2,
                'subcategories' => [
                    'Rent',
                    'Mortgage',
                    'Electricity',
                    'Water',
                    'Gas',
                    'Internet',
                    'Phone',
                    'Maintenance',
                ],
            ],
            [
                'name' => 'Transport',
                'icon' => '🚗',
                'color' => '#8b5cf6',
                'type' => 'expense',
                'order' => 3,
                'subcategories' => [
                    'Fuel',
                    'Public Transport',
                    'Taxi/Uber',
                    'Car Maintenance',
                    'Parking',
                ],
            ],
            [
                'name' => 'Health',
                'icon' => '⚕️',
                'color' => '#ef4444',
                'type' => 'expense',
                'order' => 4,
                'subcategories' => [
                    'Doctor',
                    'Pharmacy',
                    'Insurance',
                    'Gym',
                ],
            ],
            [
                'name' => 'Education',
                'icon' => '📚',
                'color' => '#06b6d4',
                'type' => 'expense',
                'order' => 5,
                'subcategories' => [
                    'Tuition',
                    'Books',
                    'Courses',
                    'School Supplies',
                ],
            ],
            [
                'name' => 'Entertainment',
                'icon' => '🎬',
                'color' => '#ec4899',
                'type' => 'expense',
                'order' => 6,
                'subcategories' => [
                    'Movies',
                    'Streaming',
                    'Games',
                    'Hobbies',
                    'Events',
                ],
            ],
            [
                'name' => 'Shopping',
                'icon' => '🛍️',
                'color' => '#f97316',
                'type' => 'expense',
                'order' => 7,
                'subcategories' => [
                    'Clothing',
                    'Electronics',
                    'Gifts',
                    'Personal Care',
                ],
            ],
            [
                'name' => 'Savings',
                'icon' => '💎',
                'color' => '#14b8a6',
                'type' => 'expense',
                'order' => 8,
                'subcategories' => [
                    'Emergency Fund',
                    'Investments',
                    'Retirement',
                ],
            ],
            [
                'name' => 'Other',
                'icon' => '📌',
                'color' => '#6b7280',
                'type' => 'expense',
                'order' => 9,
                'subcategories' => [
                    'Miscellaneous',
                ],
            ],
        ];
    }
}

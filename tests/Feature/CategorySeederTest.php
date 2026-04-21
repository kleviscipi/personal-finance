<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_is_idempotent_for_the_same_account(): void
    {
        $account = Account::create([
            'name' => 'Household',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        $seeder = app(CategorySeeder::class);

        $seeder->run($account->id);

        $initialCategoryCount = Category::where('account_id', $account->id)->count();
        $initialSubcategoryCount = Subcategory::query()
            ->join('categories', 'categories.id', '=', 'subcategories.category_id')
            ->where('categories.account_id', $account->id)
            ->count();

        $seeder->run($account->id);

        $this->assertSame($initialCategoryCount, Category::where('account_id', $account->id)->count());
        $this->assertSame(
            $initialSubcategoryCount,
            Subcategory::query()
                ->join('categories', 'categories.id', '=', 'subcategories.category_id')
                ->where('categories.account_id', $account->id)
                ->count()
        );

        $duplicateCategories = Category::query()
            ->where('account_id', $account->id)
            ->select('name', 'type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('name', 'type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $duplicateSubcategories = Subcategory::query()
            ->join('categories', 'categories.id', '=', 'subcategories.category_id')
            ->where('categories.account_id', $account->id)
            ->select('subcategories.category_id', 'subcategories.name', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('subcategories.category_id', 'subcategories.name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->assertCount(0, $duplicateCategories);
        $this->assertCount(0, $duplicateSubcategories);
    }

    public function test_category_seeder_restores_soft_deleted_seeded_records_instead_of_creating_new_ones(): void
    {
        $account = Account::create([
            'name' => 'Household',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        $seeder = app(CategorySeeder::class);

        $seeder->run($account->id);

        $category = Category::where('account_id', $account->id)
            ->where('name', 'Food')
            ->where('type', 'expense')
            ->firstOrFail();

        $subcategory = Subcategory::where('category_id', $category->id)
            ->where('name', 'Groceries')
            ->firstOrFail();

        $category->delete();
        $subcategory->delete();

        $seeder->run($account->id);

        $this->assertSame(1, Category::withTrashed()
            ->where('account_id', $account->id)
            ->where('name', 'Food')
            ->where('type', 'expense')
            ->count());

        $this->assertNull(Category::withTrashed()
            ->where('account_id', $account->id)
            ->where('name', 'Food')
            ->where('type', 'expense')
            ->value('deleted_at'));

        $this->assertSame(1, Subcategory::withTrashed()
            ->where('category_id', $category->id)
            ->where('name', 'Groceries')
            ->count());

        $this->assertNull(Subcategory::withTrashed()
            ->where('category_id', $category->id)
            ->where('name', 'Groceries')
            ->value('deleted_at'));
    }
}

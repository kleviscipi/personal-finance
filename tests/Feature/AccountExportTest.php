<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_export_downloads_an_excel_workbook_for_the_active_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Alex Owner',
            'email' => 'alex@example.com',
        ]);
        $member = User::factory()->create([
            'name' => 'Jamie Member',
            'email' => 'jamie@example.com',
        ]);

        $account = Account::create([
            'name' => 'Household Account',
            'base_currency' => 'USD',
            'description' => 'Family finances',
            'is_active' => true,
        ]);

        $user->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now()->subMonths(6),
        ]);
        $member->accounts()->attach($account->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now()->subMonths(2),
        ]);

        $category = Category::create([
            'account_id' => $account->id,
            'name' => 'Groceries',
            'type' => 'expense',
            'icon' => 'GR',
            'color' => '#22c55e',
            'order' => 1,
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Weekly Shop',
            'order' => 1,
        ]);

        Transaction::create([
            'account_id' => $account->id,
            'created_by' => $user->id,
            'type' => 'income',
            'amount' => 2500,
            'currency' => 'USD',
            'date' => '2026-04-02',
            'description' => 'Salary',
            'payment_method' => 'Bank transfer',
        ]);

        Transaction::create([
            'account_id' => $account->id,
            'created_by' => $user->id,
            'type' => 'expense',
            'amount' => 180.50,
            'currency' => 'USD',
            'date' => '2026-04-10',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'description' => 'Household groceries',
            'payment_method' => 'Card',
        ]);

        Budget::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'user_id' => null,
            'amount' => 600,
            'currency' => 'USD',
            'period' => 'monthly',
            'start_date' => '2026-01-01',
        ]);

        SavingsGoal::create([
            'account_id' => $account->id,
            'name' => 'Emergency Fund',
            'target_amount' => 5000,
            'initial_amount' => 1200,
            'currency' => 'USD',
            'tracking_mode' => 'manual',
            'start_date' => '2026-01-01',
            'target_date' => '2026-12-31',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->get(route('accounts.export', ['month' => '2026-04']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="household-account-2026-04-overview.xls"'
        );

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Worksheet ss:Name="Summary"', $content);
        $this->assertStringContainsString('Worksheet ss:Name="Transactions"', $content);
        $this->assertStringContainsString('Worksheet ss:Name="Savings Goals"', $content);
        $this->assertStringContainsString('Household Account', $content);
        $this->assertStringContainsString('April 2026', $content);
        $this->assertStringContainsString('Household groceries', $content);
        $this->assertStringContainsString('Emergency Fund', $content);
        $this->assertStringContainsString('Jamie Member', $content);
    }

    public function test_account_export_redirects_to_account_creation_when_no_account_is_active(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('accounts.export'));

        $response->assertRedirect(route('accounts.create'));
    }
}

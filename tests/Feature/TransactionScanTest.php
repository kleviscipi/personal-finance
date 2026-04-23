<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\TransactionScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

class TransactionScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_scan_a_transaction_draft(): void
    {
        [$user, $account] = $this->makeUserWithAccount();

        $this->mock(TransactionScanService::class, function (MockInterface $mock) {
            $mock->shouldReceive('scanDraft')
                ->once()
                ->andReturn([
                    'draft' => [
                        'type' => 'expense',
                        'amount' => '1200.00',
                        'currency' => 'ALL',
                        'date' => '2026-04-23',
                        'description' => 'Gym membership',
                        'payment_method' => 'card',
                    ],
                    'document' => [
                        'kind' => 'invoice',
                        'merchant' => 'City Gym',
                        'confidence' => 0.88,
                        'warnings' => [],
                        'source_name' => 'invoice.jpg',
                        'base_currency' => 'ALL',
                    ],
                ]);
        });

        $this->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->postJson(route('transactions.scan'), [
                'document' => UploadedFile::fake()->image('invoice.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('draft.amount', '1200.00')
            ->assertJsonPath('document.merchant', 'City Gym');
    }

    public function test_scan_requires_a_supported_file_type(): void
    {
        [$user, $account] = $this->makeUserWithAccount();

        $this->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->postJson(route('transactions.scan'), [
                'document' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('document');
    }

    private function makeUserWithAccount(): array
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Default Account',
            'base_currency' => 'ALL',
            'description' => 'Household account',
            'is_active' => true,
        ]);

        $user->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return [$user, $account];
    }
}

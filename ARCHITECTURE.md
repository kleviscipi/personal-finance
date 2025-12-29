# System Architecture Overview

## Architecture Philosophy

This application follows a **Domain-Driven Design** approach with clear separation of concerns, designed to scale from a personal finance tool to a multi-tenant SaaS platform.

## Core Principles

1. **Backend-First**: All business logic, calculations, and aggregations happen server-side
2. **Multi-Tenancy Ready**: Complete data isolation via account-based architecture
3. **Financial Precision**: DECIMAL types and proper rounding for monetary calculations
4. **Auditability**: Complete transaction history and change tracking
5. **Extensibility**: JSONB fields for future features without schema changes
6. **Security**: Policy-based authorization at every level

## System Layers

```
┌─────────────────────────────────────────────────────────────┐
│                      Presentation Layer                      │
│  (Inertia.js + Vue/React Components + TailwindCSS)          │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                     HTTP Layer (Laravel)                     │
│  - Controllers: Handle HTTP requests/responses               │
│  - Middleware: Authentication, authorization, CORS           │
│  - Request Validation: Form requests and rules               │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                    Application Layer                         │
│  - Services: Business logic and orchestration                │
│    • TransactionService: Transaction CRUD + history          │
│    • BudgetService: Budget management + calculations         │
│    • AnalyticsService: Financial aggregations + reporting    │
│    • CurrencyService: Multi-currency support                 │
│  - Policies: Authorization rules                             │
│    • AccountPolicy: Account access control                   │
│    • TransactionPolicy: Transaction permissions              │
│    • BudgetPolicy: Budget permissions                        │
│    • CategoryPolicy: Category management                     │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                      Domain Layer                            │
│  - Models: Eloquent models with relationships                │
│    • Account, User, Category, Subcategory                    │
│    • Transaction, Budget, TransactionHistory                 │
│  - Domain Logic: Model methods, scopes, accessors            │
│  - Events: Domain events for audit trail                     │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                   Data Persistence Layer                     │
│  - PostgreSQL: Primary data store                            │
│  - Migrations: Version-controlled schema changes             │
│  - Seeders: Default data (categories, subcategories)         │
│  - Query Builder: Optimized queries with proper indexing     │
└─────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AccountController.php
│   │   ├── TransactionController.php
│   │   ├── BudgetController.php
│   │   ├── CategoryController.php
│   │   └── DashboardController.php
│   ├── Middleware/
│   │   └── EnsureUserHasAccountAccess.php (future)
│   └── Requests/
│       ├── StoreTransactionRequest.php (future)
│       └── UpdateTransactionRequest.php (future)
├── Models/
│   ├── Account.php
│   ├── User.php
│   ├── Category.php
│   ├── Subcategory.php
│   ├── Transaction.php
│   ├── Budget.php
│   ├── TransactionHistory.php
│   └── AccountSettings.php
├── Policies/
│   ├── AccountPolicy.php
│   ├── TransactionPolicy.php
│   ├── BudgetPolicy.php
│   └── CategoryPolicy.php
├── Services/
│   ├── TransactionService.php
│   ├── BudgetService.php
│   ├── AnalyticsService.php
│   └── CurrencyService.php
└── Providers/
    └── AppServiceProvider.php

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 2025_12_29_*_create_accounts_table.php
│   ├── 2025_12_29_*_create_account_user_table.php
│   ├── 2025_12_29_*_create_categories_table.php
│   ├── 2025_12_29_*_create_subcategories_table.php
│   ├── 2025_12_29_*_create_transactions_table.php
│   ├── 2025_12_29_*_create_budgets_table.php
│   ├── 2025_12_29_*_create_transaction_histories_table.php
│   └── 2025_12_29_*_create_account_settings_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── CategorySeeder.php

resources/
├── js/
│   ├── Pages/
│   │   ├── Dashboard.vue (future)
│   │   ├── Transactions/
│   │   ├── Budgets/
│   │   ├── Categories/
│   │   └── Settings/
│   └── Components/
│       ├── Layout.vue (future)
│       └── Charts/ (future)
└── css/
    └── app.css

routes/
├── web.php
└── api.php (future)
```

## Data Flow

### Transaction Creation Flow

```
User Request
    ↓
TransactionController::store()
    ↓
Request Validation
    ↓
Authorization (TransactionPolicy::create)
    ↓
TransactionService::createTransaction()
    ↓
Database Transaction Begin
    ↓
Transaction::create() ← Create transaction record
    ↓
TransactionHistory::create() ← Log creation in audit trail
    ↓
Database Transaction Commit
    ↓
Return Transaction Model
    ↓
Response to User
```

### Dashboard Analytics Flow

```
User Request
    ↓
DashboardController::index()
    ↓
Authorization (AccountPolicy::view)
    ↓
AnalyticsService::getDashboardData()
    ↓
├── getMonthlyExpenses() ← Optimized SQL query
├── getMonthlyIncome() ← Optimized SQL query
├── getNetCashFlow() ← Calculation
├── getExpensesByCategory() ← Grouped SQL query
├── getBudgetUsage() ← Complex JOIN query
└── getCategoryTrends() ← Time-series query
    ↓
Aggregate Results
    ↓
Return to Controller
    ↓
Inertia Response with Data
    ↓
Frontend Renders Charts
```

## Multi-Tenancy Architecture

### Account-Based Isolation

Every financial record belongs to an **Account** (workspace):

```php
// Example: All queries are scoped to account
$transactions = Transaction::where('account_id', $currentAccount->id)->get();

// Enforced in policies
public function view(User $user, Transaction $transaction)
{
    return $user->accounts->contains($transaction->account_id);
}
```

### Role-Based Access Control

Users have different roles per account:

- **Owner**: Full control, can manage members
- **Admin**: Can manage all financial data, cannot remove owner
- **Member**: Can create/edit transactions and budgets
- **Viewer**: Read-only access to financial data

### Access Control Flow

```
User makes request
    ↓
Middleware authenticates user
    ↓
Policy checks user's role in account
    ↓
├── Owner/Admin → Full access
├── Member → Create/Update (own records)
└── Viewer → Read only
    ↓
Controller proceeds if authorized
```

## Service Layer Design

### TransactionService

Handles all transaction operations with automatic audit logging:

```php
public function createTransaction(Account $account, User $user, array $data)
{
    DB::transaction(function () {
        // Create transaction
        $transaction = Transaction::create([...]);
        
        // Log in history
        TransactionHistory::create([...]);
        
        return $transaction;
    });
}
```

**Responsibilities:**
- Transaction CRUD operations
- Automatic audit trail creation
- Database transaction management
- Business rule enforcement

### BudgetService

Manages budgets and calculates progress:

```php
public function calculateBudgetProgress(Budget $budget, ?Carbon $date = null)
{
    // Query actual spending
    $spent = Transaction::where('category_id', $budget->category_id)
        ->whereYear('date', $date->year)
        ->whereMonth('date', $date->month)
        ->sum('amount');
    
    // Calculate metrics
    return [
        'spent' => $spent,
        'remaining' => $budget->amount - $spent,
        'percentage' => ($spent / $budget->amount) * 100,
        'is_overspent' => $spent > $budget->amount,
    ];
}
```

**Responsibilities:**
- Budget CRUD operations
- Spending calculations
- Progress tracking
- Overspending detection

### AnalyticsService

Provides financial insights and aggregations:

```php
public function getMonthlyExpensesByCategory(Account $account, $month, $year)
{
    return DB::table('transactions')
        ->join('categories', 'transactions.category_id', '=', 'categories.id')
        ->where('transactions.account_id', $account->id)
        ->where('transactions.type', 'expense')
        ->whereYear('transactions.date', $year)
        ->whereMonth('transactions.date', $month)
        ->groupBy('categories.name')
        ->select('categories.name', DB::raw('SUM(amount) as total'))
        ->get();
}
```

**Responsibilities:**
- Financial aggregations
- Trend analysis
- Dashboard data preparation
- Optimized query execution

### CurrencyService

Handles currency formatting and conversion:

```php
public function formatAmount(string $amount, string $currency): string
{
    $data = $this->getCurrency($currency);
    return $data['symbol'] . number_format((float) $amount, 2);
}
```

**Responsibilities:**
- Currency formatting
- Symbol resolution
- Future: FX conversion with historical rates

## Security Architecture

### Authentication
- Laravel's built-in authentication system
- Session-based by default
- Token-based for API (future)

### Authorization Layers

1. **Middleware**: Route-level protection
2. **Policies**: Model-level permissions
3. **Query Scopes**: Data-level filtering

### Policy Example

```php
class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        // Check if user belongs to the same account
        return $user->accounts->contains($transaction->account_id);
    }
    
    public function update(User $user, Transaction $transaction): bool
    {
        // Check account membership
        if (!$user->accounts->contains($transaction->account_id)) {
            return false;
        }
        
        // Check role
        $pivot = $user->accounts()
            ->where('account_id', $transaction->account_id)
            ->first()
            ->pivot;
            
        return in_array($pivot->role, ['owner', 'admin', 'member']);
    }
}
```

## Performance Optimization

### Database Indexes

Strategic indexes on frequently queried columns:

```php
// Transactions table
$table->index(['account_id', 'date']); // Time-series queries
$table->index(['account_id', 'type', 'date']); // Type filtering
$table->index(['category_id', 'date']); // Category analytics

// Account_user table
$table->unique(['account_id', 'user_id']); // Membership lookup
$table->index('role'); // Role-based queries
```

### Query Optimization

```php
// Eager loading to prevent N+1
$transactions = Transaction::with(['category', 'subcategory', 'creator'])
    ->where('account_id', $accountId)
    ->get();

// Chunk for large datasets
Transaction::where('account_id', $accountId)
    ->chunk(1000, function ($transactions) {
        // Process in batches
    });
```

### Caching Strategy (Future)

```php
// Cache dashboard data
Cache::remember("dashboard.{$accountId}", 300, function () use ($account) {
    return $this->analyticsService->getDashboardData($account);
});
```

## Error Handling

### Database Transactions

All state-changing operations wrapped in DB transactions:

```php
DB::transaction(function () {
    // Multiple database operations
    // Automatic rollback on exception
});
```

### Validation

```php
// Controller validation
$validated = $request->validate([
    'amount' => 'required|numeric|min:0',
    'currency' => 'required|in:USD,EUR,ALL',
    'date' => 'required|date',
    'category_id' => 'required|exists:categories,id',
]);
```

### Exception Handling

```php
try {
    $transaction = $this->transactionService->createTransaction($data);
} catch (\Exception $e) {
    Log::error('Transaction creation failed', [
        'error' => $e->getMessage(),
        'data' => $data,
    ]);
    
    return back()->withErrors(['error' => 'Unable to create transaction']);
}
```

## Scalability Considerations

### Horizontal Scaling
- Stateless application design
- Session storage in database/redis
- File uploads to cloud storage (S3)

### Database Scaling
- Read replicas for analytics queries
- Partitioning by account_id (future)
- Archive old transactions (future)

### Queueing (Future)
```php
// Heavy operations in background
dispatch(new GenerateMonthlyReport($account, $month));
dispatch(new SendBudgetAlert($budget, $overspend));
```

## Future Enhancements

### API Layer
```php
// RESTful API routes
Route::apiResource('accounts.transactions', TransactionController::class);
Route::get('accounts/{account}/analytics/dashboard', AnalyticsController::class);
```

### Real-Time Features
```php
// Broadcasting budget alerts
event(new BudgetThresholdExceeded($budget, $overspend));
```

### Subscription Management
```php
// SaaS features
class Subscription extends Model
{
    public function accounts() { ... }
    public function plan() { ... }
}
```

## Deployment Architecture

```
┌─────────────────┐
│   Load Balancer │
└────────┬────────┘
         │
    ┌────▼─────┬─────────────┐
    │          │             │
┌───▼───┐  ┌───▼───┐    ┌───▼───┐
│ App 1 │  │ App 2 │    │ App N │
└───┬───┘  └───┬───┘    └───┬───┘
    │          │            │
    └──────────┼────────────┘
               │
       ┌───────▼────────┐
       │   PostgreSQL   │
       │   (Primary)    │
       └────────────────┘
               │
       ┌───────▼────────┐
       │   PostgreSQL   │
       │   (Replica)    │
       └────────────────┘
```

## Testing Strategy

### Unit Tests
- Service layer methods
- Model relationships
- Helper functions

### Feature Tests
- Complete user flows
- API endpoints
- Authorization rules

### Database Tests
- Migration integrity
- Seeder functionality
- Query performance

## Documentation Maintenance

This architecture should be reviewed and updated:
- When adding new major features
- After significant refactoring
- During scaling/performance improvements
- Before major releases

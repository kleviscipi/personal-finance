# Personal Finance Web Application

A modern, professional Personal Finance Web Application built with Laravel 11 and PostgreSQL, designed for individuals and families with SaaS-ready architecture.

## 🎯 Vision

Build a YNAB-level structured, Mint-level visibility personal finance system with clean, developer-first Laravel architecture that can scale into a SaaS business.

## 🐳 Quick Start with Docker (Recommended)

The fastest way to get started is using Docker:

```bash
# Clone the repository
git clone https://github.com/kleviscipi/personal-finance.git
cd personal-finance

# Run the setup script
./setup.sh
```

The application will be available at:
- **Web App**: http://localhost
- **Mail Dashboard**: http://localhost:8025 (Mailpit)

See [DOCKER_SETUP.md](DOCKER_SETUP.md) for detailed Docker instructions.

## ✨ Key Features

### Multi-Account & Family Support
- **Workspaces**: Each account represents a personal or family finance space
- **Role-Based Access**: Owner, Admin, Member, Viewer roles
- **Multi-User**: Users can belong to multiple accounts
- **Data Isolation**: Complete tenant isolation ready for SaaS

### Multi-Currency Support
- EUR, USD, GBP, JPY, CHF, CAD, AUD, ALL (Albanian LEK)
- Configurable base currency per account
- Exchange rate management with historical rates
- Automatic currency conversion in analytics
- See [MULTI_CURRENCY_GUIDE.md](MULTI_CURRENCY_GUIDE.md) for detailed usage

### Financial Management
- **Transactions**: Expense, Income, Transfer types
- **Categories & Subcategories**: Pre-seeded + customizable
- **Budgets**: Monthly/yearly with overspending visibility
- **Audit Trail**: Complete transaction history
- **Accurate Decimals**: DECIMAL(19,4) for precise calculations

### Analytics & Reporting
- Monthly expenses by category
- Income tracking
- Net cash flow
- Budget vs actual
- Category trends
- Backend-driven aggregations (no frontend duplication)

## 🏗️ Architecture

### Tech Stack
- **Backend**: Laravel 11+
- **Database**: PostgreSQL
- **Frontend**: Inertia.js + Vue.js/React (to be configured)
- **Styling**: TailwindCSS
- **Authentication**: Laravel Breeze/Fortify
- **Containers**: Docker + Docker Compose

### Database Schema

#### Core Tables
- `users` - User authentication
- `accounts` - Workspace/tenant isolation
- `account_user` - Pivot with roles
- `categories` - Expense/income categories
- `subcategories` - Category subdivisions
- `transactions` - Financial transactions
- `budgets` - Budget definitions
- `transaction_histories` - Audit trail
- `account_settings` - Account preferences

### Domain-Driven Structure
```
app/
├── Models/           # Eloquent models
├── Services/         # Business logic layer
│   ├── TransactionService.php
│   ├── BudgetService.php
│   ├── AnalyticsService.php
│   └── CurrencyService.php
├── Policies/         # Authorization
└── Http/
    └── Controllers/  # Request handlers
```

## 🚀 Getting Started

### Option 1: Docker (Recommended)

```bash
# Run the automated setup
./setup.sh
```

Access the app at http://localhost

### Option 2: Manual Setup

### Prerequisites
- PHP 8.2+
- PostgreSQL 14+
- Composer
- Node.js & NPM

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/kleviscipi/personal-finance.git
cd personal-finance
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install JavaScript dependencies**
```bash
npm install
```

4. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Update database configuration in `.env`**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=personal_finance
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Seed default categories** (when accounts exist)
```bash
php artisan db:seed --class=CategorySeeder
```

8. **Start development server**
```bash
php artisan serve
npm run dev
```

## 📊 Database Schema Overview

### Accounts & Users
- Multi-tenant architecture
- Role-based access control
- Soft deletes for data retention

### Transactions
- Decimal precision for financial accuracy
- Logical date separate from created_at
- JSONB metadata for extensibility
- Multi-currency support
- Proper indexing for performance

### Categories
- System and custom categories
- Hierarchical structure (Category → Subcategory)
- Icon and color support
- Type: expense or income

### Budgets
- Monthly/yearly periods
- Category or subcategory level
- JSONB settings for flexibility
- Date range support

## 🔐 Security & Authorization

### Policies
- AccountPolicy: Account access control
- TransactionPolicy: Transaction operations
- BudgetPolicy: Budget management
- CategoryPolicy: Category management

### Data Isolation
- All queries scoped to account_id
- Authorization enforced at policy level
- No global user data leakage

## 🎨 Frontend (To Be Completed)

Planned pages:
- `/login` - Authentication
- `/dashboard` - Overview with analytics
- `/transactions` - Transaction management
- `/categories` - Category management
- `/budgets` - Budget planning
- `/settings` - Account settings
- `/family` - Member management

## 📈 Aggregations & Analytics

All calculations performed server-side:
- Monthly expenses by category
- Income vs expenses
- Budget usage and remaining
- Category trends over time
- Net cash flow

Services use optimized SQL queries with proper indexing.

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
```

## 🛠️ Development

### Code Style
```bash
# Run Laravel Pint for code formatting
./vendor/bin/pint
```

### Database
```bash
# Reset and re-migrate
php artisan migrate:fresh

# Seed categories for existing accounts
php artisan db:seed --class=CategorySeeder
```

## 📝 License

This project is open-sourced software.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Support

For support, please open an issue in the GitHub repository.

---

Built with ❤️ using Laravel 11 and PostgreSQL

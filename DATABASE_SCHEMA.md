# Database Schema Documentation

## Overview

This document describes the complete database schema for the Personal Finance Web Application. The schema is designed for PostgreSQL with multi-tenancy, audit trails, and financial precision.

## Entity Relationship Diagram

```
┌─────────────┐       ┌──────────────┐       ┌─────────────────┐
│    Users    │───────│ Account_User │───────│    Accounts     │
└─────────────┘       └──────────────┘       └─────────────────┘
                                                      │
                                                      │
                      ┌───────────────────────────────┼──────────────┐
                      │                               │              │
                      ▼                               ▼              ▼
              ┌──────────────┐              ┌──────────────┐  ┌──────────────┐
              │  Categories  │              │ Transactions │  │   Budgets    │
              └──────────────┘              └──────────────┘  └──────────────┘
                      │                               │
                      ▼                               ▼
              ┌──────────────┐              ┌──────────────────────┐
              │Subcategories │              │Transaction_Histories │
              └──────────────┘              └──────────────────────┘
```

## Tables

### users

Standard Laravel authentication table.

| Column             | Type           | Constraints          | Description                    |
|--------------------|----------------|---------------------|--------------------------------|
| id                 | BIGSERIAL      | PRIMARY KEY         | User ID                        |
| name               | VARCHAR(255)   | NOT NULL            | User's full name               |
| email              | VARCHAR(255)   | NOT NULL, UNIQUE    | Email address                  |
| email_verified_at  | TIMESTAMP      | NULL                | Email verification timestamp   |
| password           | VARCHAR(255)   | NOT NULL            | Hashed password                |
| remember_token     | VARCHAR(100)   | NULL                | Remember me token              |
| created_at         | TIMESTAMP      | NOT NULL            | Creation timestamp             |
| updated_at         | TIMESTAMP      | NOT NULL            | Last update timestamp          |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `email`

---

### accounts

Represents a workspace for personal or family finances.

| Column        | Type          | Constraints              | Description                         |
|---------------|---------------|-------------------------|-------------------------------------|
| id            | BIGSERIAL     | PRIMARY KEY             | Account ID                          |
| name          | VARCHAR(255)  | NOT NULL                | Account name                        |
| base_currency | VARCHAR(3)    | NOT NULL, DEFAULT 'USD' | Base currency code (USD, EUR, ALL)  |
| description   | TEXT          | NULL                    | Account description                 |
| is_active     | BOOLEAN       | NOT NULL, DEFAULT TRUE  | Whether account is active           |
| created_at    | TIMESTAMP     | NOT NULL                | Creation timestamp                  |
| updated_at    | TIMESTAMP     | NOT NULL                | Last update timestamp               |
| deleted_at    | TIMESTAMP     | NULL                    | Soft delete timestamp               |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `is_active`

---

### account_user

Pivot table for many-to-many relationship between users and accounts with roles.

| Column      | Type          | Constraints                           | Description                    |
|-------------|---------------|--------------------------------------|--------------------------------|
| id          | BIGSERIAL     | PRIMARY KEY                          | Pivot ID                       |
| account_id  | BIGINT        | NOT NULL, FOREIGN KEY → accounts     | Reference to account           |
| user_id     | BIGINT        | NOT NULL, FOREIGN KEY → users        | Reference to user              |
| role        | ENUM          | NOT NULL, DEFAULT 'member'           | owner, admin, member, viewer   |
| is_active   | BOOLEAN       | NOT NULL, DEFAULT TRUE               | Whether membership is active   |
| invited_at  | TIMESTAMP     | NULL                                 | Invitation timestamp           |
| joined_at   | TIMESTAMP     | NULL                                 | Join timestamp                 |
| created_at  | TIMESTAMP     | NOT NULL                             | Creation timestamp             |
| updated_at  | TIMESTAMP     | NOT NULL                             | Last update timestamp          |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `(account_id, user_id)`
- INDEX on `role`

**Foreign Keys:**
- `account_id` REFERENCES `accounts(id)` ON DELETE CASCADE
- `user_id` REFERENCES `users(id)` ON DELETE CASCADE

---

### categories

Categories for organizing transactions (e.g., Food, Transport, Income).

| Column      | Type          | Constraints                           | Description                    |
|-------------|---------------|--------------------------------------|--------------------------------|
| id          | BIGSERIAL     | PRIMARY KEY                          | Category ID                    |
| account_id  | BIGINT        | NOT NULL, FOREIGN KEY → accounts     | Reference to account           |
| name        | VARCHAR(255)  | NOT NULL                             | Category name                  |
| icon        | VARCHAR(255)  | NULL                                 | Category icon/emoji            |
| color       | VARCHAR(7)    | NULL                                 | Category color (hex)           |
| type        | ENUM          | NOT NULL, DEFAULT 'expense'          | expense or income              |
| is_system   | BOOLEAN       | NOT NULL, DEFAULT FALSE              | Whether it's a system category |
| order       | INTEGER       | NOT NULL, DEFAULT 0                  | Display order                  |
| created_at  | TIMESTAMP     | NOT NULL                             | Creation timestamp             |
| updated_at  | TIMESTAMP     | NOT NULL                             | Last update timestamp          |
| deleted_at  | TIMESTAMP     | NULL                                 | Soft delete timestamp          |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `(account_id, type)`

**Foreign Keys:**
- `account_id` REFERENCES `accounts(id)` ON DELETE CASCADE

---

### subcategories

Subcategories for further transaction organization (e.g., Groceries under Food).

| Column      | Type          | Constraints                           | Description                         |
|-------------|---------------|--------------------------------------|-------------------------------------|
| id          | BIGSERIAL     | PRIMARY KEY                          | Subcategory ID                      |
| category_id | BIGINT        | NOT NULL, FOREIGN KEY → categories   | Reference to category               |
| name        | VARCHAR(255)  | NOT NULL                             | Subcategory name                    |
| is_system   | BOOLEAN       | NOT NULL, DEFAULT FALSE              | Whether it's a system subcategory   |
| order       | INTEGER       | NOT NULL, DEFAULT 0                  | Display order                       |
| created_at  | TIMESTAMP     | NOT NULL                             | Creation timestamp                  |
| updated_at  | TIMESTAMP     | NOT NULL                             | Last update timestamp               |
| deleted_at  | TIMESTAMP     | NULL                                 | Soft delete timestamp               |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `category_id`

**Foreign Keys:**
- `category_id` REFERENCES `categories(id)` ON DELETE CASCADE

---

### transactions

Core table for all financial transactions.

| Column          | Type          | Constraints                           | Description                       |
|-----------------|---------------|--------------------------------------|-----------------------------------|
| id              | BIGSERIAL     | PRIMARY KEY                          | Transaction ID                    |
| account_id      | BIGINT        | NOT NULL, FOREIGN KEY → accounts     | Reference to account              |
| created_by      | BIGINT        | NOT NULL, FOREIGN KEY → users        | User who created transaction      |
| type            | ENUM          | NOT NULL, DEFAULT 'expense'          | expense, income, or transfer      |
| amount          | DECIMAL(19,4) | NOT NULL                             | Transaction amount                |
| currency        | VARCHAR(3)    | NOT NULL                             | Currency code                     |
| date            | DATE          | NOT NULL                             | Logical transaction date          |
| category_id     | BIGINT        | NULL, FOREIGN KEY → categories       | Reference to category             |
| subcategory_id  | BIGINT        | NULL, FOREIGN KEY → subcategories    | Reference to subcategory          |
| description     | TEXT          | NULL                                 | Transaction description           |
| payment_method  | VARCHAR(255)  | NULL                                 | Payment method used               |
| metadata        | JSONB         | NULL                                 | Additional metadata               |
| created_at      | TIMESTAMP     | NOT NULL                             | Creation timestamp                |
| updated_at      | TIMESTAMP     | NOT NULL                             | Last update timestamp             |
| deleted_at      | TIMESTAMP     | NULL                                 | Soft delete timestamp             |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `(account_id, date)`
- INDEX on `(account_id, type, date)`
- INDEX on `(category_id, date)`
- INDEX on `created_by`

**Foreign Keys:**
- `account_id` REFERENCES `accounts(id)` ON DELETE CASCADE
- `created_by` REFERENCES `users(id)` ON DELETE RESTRICT
- `category_id` REFERENCES `categories(id)` ON DELETE SET NULL
- `subcategory_id` REFERENCES `subcategories(id)` ON DELETE SET NULL

**Notes:**
- `date` is the logical transaction date, separate from `created_at`
- `amount` uses DECIMAL(19,4) for financial precision
- Soft deletes maintain data integrity for reporting

---

### budgets

Budget definitions for spending limits.

| Column          | Type          | Constraints                           | Description                    |
|-----------------|---------------|--------------------------------------|--------------------------------|
| id              | BIGSERIAL     | PRIMARY KEY                          | Budget ID                      |
| account_id      | BIGINT        | NOT NULL, FOREIGN KEY → accounts     | Reference to account           |
| category_id     | BIGINT        | NULL, FOREIGN KEY → categories       | Reference to category          |
| subcategory_id  | BIGINT        | NULL, FOREIGN KEY → subcategories    | Reference to subcategory       |
| amount          | DECIMAL(19,4) | NOT NULL                             | Budget amount                  |
| currency        | VARCHAR(3)    | NOT NULL                             | Currency code                  |
| period          | ENUM          | NOT NULL, DEFAULT 'monthly'          | monthly or yearly              |
| start_date      | DATE          | NOT NULL                             | Budget start date              |
| end_date        | DATE          | NULL                                 | Budget end date (optional)     |
| settings        | JSONB         | NULL                                 | Additional settings            |
| created_at      | TIMESTAMP     | NOT NULL                             | Creation timestamp             |
| updated_at      | TIMESTAMP     | NOT NULL                             | Last update timestamp          |
| deleted_at      | TIMESTAMP     | NULL                                 | Soft delete timestamp          |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `(account_id, start_date, end_date)`
- INDEX on `(category_id, start_date)`

**Foreign Keys:**
- `account_id` REFERENCES `accounts(id)` ON DELETE CASCADE
- `category_id` REFERENCES `categories(id)` ON DELETE CASCADE
- `subcategory_id` REFERENCES `subcategories(id)` ON DELETE CASCADE

**Notes:**
- Can be at account, category, or subcategory level
- `settings` JSONB allows for carry-over and other custom configurations

---

### transaction_histories

Audit trail for transaction changes.

| Column         | Type          | Constraints                           | Description                    |
|----------------|---------------|--------------------------------------|--------------------------------|
| id             | BIGSERIAL     | PRIMARY KEY                          | History ID                     |
| transaction_id | BIGINT        | NOT NULL, FOREIGN KEY → transactions | Reference to transaction       |
| changed_by     | BIGINT        | NOT NULL, FOREIGN KEY → users        | User who made change           |
| action         | VARCHAR(255)  | NOT NULL                             | Action type (created, updated) |
| old_values     | JSONB         | NULL                                 | Previous values                |
| new_values     | JSONB         | NULL                                 | New values                     |
| created_at     | TIMESTAMP     | NOT NULL                             | Change timestamp               |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `(transaction_id, created_at)`
- INDEX on `changed_by`

**Foreign Keys:**
- `transaction_id` REFERENCES `transactions(id)` ON DELETE CASCADE
- `changed_by` REFERENCES `users(id)` ON DELETE RESTRICT

**Notes:**
- No `updated_at` column (immutable history)
- Stores complete before/after snapshots in JSONB

---

### account_settings

Settings and preferences per account.

| Column                 | Type          | Constraints                       | Description                    |
|------------------------|---------------|----------------------------------|--------------------------------|
| id                     | BIGSERIAL     | PRIMARY KEY                      | Setting ID                     |
| account_id             | BIGINT        | NOT NULL, UNIQUE, FK → accounts  | Reference to account           |
| locale                 | VARCHAR(5)    | NOT NULL, DEFAULT 'en'           | Locale code                    |
| timezone               | VARCHAR(255)  | NOT NULL, DEFAULT 'UTC'          | Timezone                       |
| date_format            | VARCHAR(255)  | NOT NULL, DEFAULT 'Y-m-d'        | Date format preference         |
| time_format            | VARCHAR(255)  | NOT NULL, DEFAULT 'H:i'          | Time format preference         |
| notifications_enabled  | BOOLEAN       | NOT NULL, DEFAULT TRUE           | Enable notifications           |
| preferences            | JSONB         | NULL                             | Additional preferences         |
| created_at             | TIMESTAMP     | NOT NULL                         | Creation timestamp             |
| updated_at             | TIMESTAMP     | NOT NULL                         | Last update timestamp          |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `account_id`

**Foreign Keys:**
- `account_id` REFERENCES `accounts(id)` ON DELETE CASCADE

---

## Data Types

### DECIMAL(19,4)
Used for all monetary amounts to ensure precise financial calculations:
- 19 total digits
- 4 decimal places
- Can represent up to 999,999,999,999,999.9999

### JSONB
PostgreSQL's binary JSON type, used for:
- Flexible metadata in transactions
- Budget settings (carry-over, alerts, etc.)
- Account preferences
- Audit trail snapshots

### ENUM Types
- **role**: 'owner', 'admin', 'member', 'viewer'
- **transaction type**: 'expense', 'income', 'transfer'
- **category type**: 'expense', 'income'
- **budget period**: 'monthly', 'yearly'

## Design Decisions

### Multi-Tenancy
- All financial data includes `account_id`
- Enforced at application level via policies
- Foreign key cascades ensure data integrity

### Soft Deletes
- Categories, subcategories, transactions, budgets use soft deletes
- Maintains referential integrity for historical reports
- Can be permanently deleted if needed

### Audit Trail
- `transaction_histories` provides complete audit log
- Immutable records (no UPDATE, only INSERT)
- Stores full before/after state in JSONB

### Currency Support
- Currency stored as 3-character code (ISO 4217)
- No hardcoded currency assumptions
- Conversion layer ready for FX implementation

### Performance
- Strategic indexes on frequently queried columns
- Composite indexes for common query patterns
- Date indexes for time-series queries

## Migration Order

Migrations must run in this order due to foreign key dependencies:

1. `create_users_table` (Laravel default)
2. `create_accounts_table`
3. `create_account_user_table`
4. `create_categories_table`
5. `create_subcategories_table`
6. `create_transactions_table`
7. `create_budgets_table`
8. `create_transaction_histories_table`
9. `create_account_settings_table`

## Future Enhancements

- **Exchange rates table**: Historical FX rates for multi-currency conversion
- **Recurring transactions**: Template and schedule for automated transactions
- **Attachments table**: Receipt/document storage
- **Tags table**: Flexible tagging system beyond categories
- **Subscription tracking**: SaaS billing and limits

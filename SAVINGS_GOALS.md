# Savings Goals

This document explains how Savings Goals work in this app: how data is stored, how progress is calculated, and how projections are generated.

## Overview

Savings Goals let users define a target amount and a target date, then track progress toward that target. Progress is calculated automatically from transactions, or manually when set to “manual” tracking. The goals page also provides “what-if” projections to estimate completion dates based on different monthly contributions.

## Data Model

Savings goals are stored in the `savings_goals` table.

Key fields:
- `account_id`: Goal owner (account-level).
- `name`: Display name for the goal.
- `target_amount`: Total target to reach.
- `initial_amount`: Starting balance already saved.
- `currency`: Goal currency.
- `tracking_mode`: How contributions are calculated.
  - `net_savings` (default): Income minus expenses across the account.
  - `category`: Expenses in a specific category.
  - `subcategory`: Expenses in a specific subcategory.
  - `manual`: No transaction-based contribution; progress is only initial amount.
- `category_id` / `subcategory_id`: Optional category links used when tracking by category or subcategory.
- `start_date`: Start of the goal tracking window.
- `target_date`: Goal deadline.
- `settings`: JSON for future extensions.

## Progress Calculation

Progress is computed in `SavingsGoalService::calculateProgress()` and returned per goal.

Steps:
1. Determine the contribution window: `start_date` to “now”.
2. Compute **contributed** amount based on `tracking_mode`:
   - `net_savings`: total income minus total expense for the window.
   - `category`: sum of expenses in the category.
   - `subcategory`: sum of expenses in the subcategory.
   - `manual`: 0 (no automatic contribution).
3. `current_amount = initial_amount + contributed`.
4. `remaining = target_amount - current_amount`.
5. `percentage = (current_amount / target_amount) * 100`.
6. `is_complete` is true when `current_amount >= target_amount`.

## Projections (“What‑If”)

Projections are computed in `SavingsGoalService::calculateProjection()`:

- **Average monthly contribution**: average of the last N months (default 3) based on the same tracking mode.
- **Monthly used**: either the average above or a user‑provided monthly amount.
- **Projected completion date**: estimated by dividing `remaining` by `monthly_used` and adding that many months.
- **Required monthly**: the monthly amount required to finish by the goal’s `target_date`.

If the monthly contribution is 0 or not available, projected date is `null`.

## UI Behavior

- **Index** (`/savings-goals`):
  - Displays progress bars, remaining amount, and tracking details.
  - Includes a “What‑if projection” panel per goal to test monthly contributions.
- **Create/Edit**:
  - Supports tracking mode selection.
  - Category/subcategory inputs are required only when the tracking mode needs them.

## Permissions

Savings goals are account-scoped. Any user with account access can view them. Updates/deletes require a valid account role (owner/admin/member). Authorization is enforced by `SavingsGoalPolicy`.

## Files & Locations

- Model: `app/Models/SavingsGoal.php`
- Service: `app/Services/SavingsGoalService.php`
- Controller: `app/Http/Controllers/SavingsGoalController.php`
- Policy: `app/Policies/SavingsGoalPolicy.php`
- Routes: `routes/web.php`
- UI: `resources/js/Pages/SavingsGoals/Index.vue`, `Create.vue`, `Edit.vue`
- Migration: `database/migrations/2025_12_29_124863_create_savings_goals_table.php`

## Notes

- Projections are for planning and do not store data.
- For `manual` mode, contributions are not calculated from transactions.
- For best results, keep transactions up to date and categorized.

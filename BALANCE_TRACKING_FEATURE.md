# Balance & Savings Tracking - Feature Documentation

## Overview

This feature adds comprehensive balance and savings tracking to the Personal Finance dashboard, answering the question: "How can we show savings (what remains) or the balance?"

## Problem Solved

**User's Request:**
> "The dashboard shows the net worth but there is monthly showed. How we can show savings i mean what remains, or the balance."

**Solution:**
- Track cumulative balance (net worth) over time
- Show monthly savings separately from balance
- Visualize balance growth with charts
- Display savings rate percentage

## Features Implemented

### 1. Total Balance Card

**Location:** Dashboard - Stat Cards Row
**Shows:** Cumulative net worth from ALL transactions (lifetime)

**Calculation:**
```
Total Balance = Sum(All Income) - Sum(All Expenses)
```

**Visual Indicator:**
- Indigo background (positive balance)
- Red background (negative balance)
- Shows "Net Worth" subtitle

### 2. Monthly Savings Card

**Location:** Dashboard - Stat Cards Row
**Shows:** Current month savings and savings rate

**Calculation:**
```
Monthly Savings = Current Month Income - Current Month Expenses
Savings Rate = (Monthly Savings / Current Month Income) × 100
```

**Display:**
- Main: Savings amount (e.g., "$1,500")
- Subtitle: Savings rate (e.g., "25% rate")
- Blue background (positive) / Orange (negative)

### 3. Balance & Savings Trend Chart

**Location:** Dashboard - Full Width Chart
**Shows:** 12-month history of balance and savings

**Two Lines:**
1. **Total Balance (Purple)** - Cumulative balance over time
2. **Monthly Savings (Green)** - Savings each month

**Features:**
- Smooth bezier curves
- Gradient fill under lines
- Currency-formatted tooltips
- Currency-formatted Y-axis
- Interactive hover states

## Data Structure

### Balance History Array

Each month contains:
```javascript
{
    month: '2024-01',          // Month identifier
    income: 5000.00,           // Month income
    expenses: 3500.00,         // Month expenses
    savings: 1500.00,          // Monthly savings (income - expenses)
    balance: 15000.00,         // Cumulative balance up to this month
}
```

### Total Balance

Single value showing current net worth:
```javascript
total_balance: 15000.00  // Current cumulative balance
```

### Current Month Savings

```javascript
{
    amount: 1500.00,     // This month's savings
    rate: 30.00,         // Savings rate percentage
    income: 5000.00,     // This month's income
    expenses: 3500.00,   // This month's expenses
}
```

## Dashboard Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ Dashboard                                   [Add Transaction]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────┐ │
│  │ Monthly  │ │ Monthly  │ │ Monthly  │ │  Total   │ │Active│ │
│  │ Income   │ │ Expenses │ │ Savings  │ │ Balance  │ │Budget│ │
│  │ $5,000   │ │ $3,500   │ │ $1,500   │ │ $15,000  │ │  5   │ │
│  │  (green) │ │  (red)   │ │ 30% rate │ │Net Worth │ │      │ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────┘ │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ Balance & Savings Trend (12 Months)                         ││
│  │                                                              ││
│  │     Balance Line (Purple) ──────────────                    ││
│  │                                  ╱                           ││
│  │                             ╱                                ││
│  │                        ╱                                     ││
│  │                   ╱                                          ││
│  │              ╱                                               ││
│  │         ╱                                                    ││
│  │    Savings Line (Green) ════════════                        ││
│  │                                                              ││
│  │  Jan  Feb  Mar  Apr  May  Jun  Jul  Aug  Sep  Oct  Nov  Dec││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                   │
│  ┌───────────────────┐ ┌───────────────────┐                    │
│  │ Expenses by      │ │ Budget Usage     │                    │
│  │ Category (Pie)   │ │ (Progress Bars)  │                    │
│  └───────────────────┘ └───────────────────┘                    │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Use Cases

### 1. Track Net Worth Growth
**User Goal:** See total wealth accumulation
**Solution:** Total Balance card shows cumulative value
**Example:** Started with $0, now have $50,000 saved

### 2. Monitor Monthly Savings
**User Goal:** Know how much was saved this month
**Solution:** Monthly Savings card with rate percentage
**Example:** Saved $2,500 this month (40% of income)

### 3. Visualize Financial Progress
**User Goal:** See balance trends over time
**Solution:** Balance Trend Chart shows growth pattern
**Example:** Balance growing steadily from $10k to $50k

### 4. Identify Savings Patterns
**User Goal:** Find high/low savings months
**Solution:** Savings line shows monthly variations
**Example:** Summer months show lower savings

### 5. Understand Relationship
**User Goal:** See how savings affect balance
**Solution:** Two lines show correlation
**Example:** Months with high savings = steep balance growth

## Technical Implementation

### Backend (AnalyticsService)

**getTotalBalance()**
```php
// Sums all income and expenses
// Returns: cumulative balance as string
SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) - 
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END)
FROM transactions
WHERE account_id = ?
```

**getBalanceHistory()**
```php
// Gets 12-month history
// Returns: array of monthly data with cumulative balance
1. Get starting balance (before 12 months)
2. Get monthly aggregates (last 12 months)
3. Calculate cumulative balance month-by-month
4. Return array with savings and balance for each month
```

**getCurrentMonthSavings()**
```php
// Gets current month data
// Returns: savings amount, rate, income, expenses
1. Get current month income
2. Get current month expenses
3. Calculate savings = income - expenses
4. Calculate rate = savings / income × 100
```

### Frontend (Dashboard.vue)

**Computed Properties:**
```javascript
balanceHistory - Maps analytics.balance_history
balanceTrendChartData - Creates Chart.js dataset
balanceTrendOptions - Chart configuration
```

**Chart Configuration:**
- Responsive and maintains aspect ratio
- Two Y-axes support
- Currency formatting in tooltips
- Currency formatting on axis
- Smooth curves with tension
- Gradient fills

## Color Palette

| Element | Color | Hex | Usage |
|---------|-------|-----|-------|
| Monthly Income | Green | #10b981 | Positive money in |
| Monthly Expenses | Red | #ef4444 | Money out |
| Monthly Savings | Blue | #0ea5e9 | Positive: savings |
| Monthly Savings (neg) | Orange | #f97316 | Negative: deficit |
| Total Balance | Indigo | #6366f1 | Cumulative wealth |
| Total Balance (neg) | Red | #ef4444 | Debt/negative |
| Active Budgets | Purple | #a855f7 | Budget tracking |

## Example Scenarios

### Scenario 1: Consistent Saver
```
Month 1: Income $5k, Expenses $3k → Savings $2k → Balance $2k
Month 2: Income $5k, Expenses $3k → Savings $2k → Balance $4k
Month 3: Income $5k, Expenses $3k → Savings $2k → Balance $6k
```
**Dashboard Shows:**
- Total Balance: $6,000
- Monthly Savings: $2,000 (40% rate)
- Balance Chart: Steadily rising line
- Savings Chart: Consistent $2k bars

### Scenario 2: Variable Savings
```
Month 1: Income $5k, Expenses $3k → Savings $2k → Balance $2k
Month 2: Income $5k, Expenses $4.5k → Savings $0.5k → Balance $2.5k
Month 3: Income $6k, Expenses $3k → Savings $3k → Balance $5.5k
```
**Dashboard Shows:**
- Total Balance: $5,500
- Monthly Savings: $3,000 (50% rate)
- Balance Chart: Growing but with plateau
- Savings Chart: Variable heights

### Scenario 3: Spending Down Savings
```
Month 1: Starting Balance $10k
Month 2: Income $5k, Expenses $7k → Savings -$2k → Balance $8k
Month 3: Income $5k, Expenses $6k → Savings -$1k → Balance $7k
```
**Dashboard Shows:**
- Total Balance: $7,000
- Monthly Savings: -$1,000 (orange, negative)
- Balance Chart: Declining line
- Savings Chart: Below zero

## Benefits

### For Users
✅ Clear visibility into financial health
✅ Understand savings patterns
✅ Track net worth growth
✅ Identify problem months
✅ Set improvement goals
✅ Visual motivation to save

### For the Application
✅ Comprehensive financial tracking
✅ Professional analytics
✅ Competitive with YNAB/Mint
✅ Data-driven insights
✅ Retention through value

## Future Enhancements

### Planned Features
- **Savings Goals:** Set and track savings targets
- **Projections:** Forecast future balance based on trends
- **Comparison:** Compare to previous periods
- **Alerts:** Notify when savings rate drops
- **Reports:** Export balance history to PDF/CSV
- **Categories:** Balance by category
- **Time Ranges:** Toggle 3/6/12/24 month views

### Advanced Analytics
- Savings momentum (acceleration/deceleration)
- Best/worst months analysis
- Seasonal patterns detection
- Recommendations engine
- Budget vs actual balance impact

## Migration & Compatibility

### Backward Compatible
- Existing data automatically calculated
- No database migrations required
- Works with empty accounts
- Graceful handling of edge cases

### Performance
- Efficient SQL queries
- Single query for history
- Client-side chart rendering
- Minimal server load

## Support & Documentation

### User Guide
Located in application help section:
- What is Total Balance?
- How is Savings calculated?
- Understanding the Balance Trend
- Tips for improving savings rate

### Developer Documentation
- AnalyticsService API reference
- Chart component usage
- Data structure specifications
- Extension guidelines

---

**Version:** 1.0.0
**Date:** 2025-12-29
**Status:** Production Ready ✅

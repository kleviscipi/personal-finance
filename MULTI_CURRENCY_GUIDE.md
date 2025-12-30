# Multi-Currency Support Guide

## Overview

The Personal Finance application provides comprehensive multi-currency support, allowing you to:

- Track transactions in multiple currencies
- Set a base currency for each account
- Automatically convert amounts using exchange rates
- Manage historical exchange rates
- View analytics in your account's base currency

## Supported Currencies

The application currently supports the following currencies:

- **USD** - US Dollar ($)
- **EUR** - Euro (€)
- **GBP** - British Pound (£)
- **JPY** - Japanese Yen (¥)
- **CHF** - Swiss Franc
- **CAD** - Canadian Dollar (C$)
- **AUD** - Australian Dollar (A$)
- **ALL** - Albanian Lek (L)

## How It Works

### Base Currency

Each account has a **base currency** that is used for:
- Displaying totals and analytics
- Budget planning
- Financial reports

You can set your account's base currency in **Settings**.

### Transaction Currencies

When creating a transaction, you can select any supported currency. The transaction will be stored in its original currency, but when displayed in analytics or totals, it will be automatically converted to your account's base currency using exchange rates.

### Exchange Rates

Exchange rates are stored in the database and can be:
- **Manually entered** by administrators
- **Automatically fetched** from external APIs (future feature)
- **Historical** - rates are date-specific for accurate historical conversion

#### Managing Exchange Rates

1. Navigate to the Exchange Rates management page
2. Enter the base currency, target currency, rate, and date
3. The system will use these rates for currency conversion

**Example:**
```
Base: USD
Target: EUR
Rate: 0.92
Date: 2024-12-30
```

This means 1 USD = 0.92 EUR on December 30, 2024.

### Conversion Logic

When converting amounts, the system:

1. **Same Currency**: If the transaction currency matches the account's base currency, no conversion is needed
2. **With Exchange Rate**: If an exchange rate exists for the transaction date, it's used for conversion
3. **Fallback to Recent Rate**: If no rate exists for the transaction date, the most recent available rate is used
4. **No Rate Available**: If no rate is found, the original amount is displayed (a warning may be shown)

## Setting Up Multi-Currency

### 1. Set Your Account Base Currency

1. Go to **Settings**
2. Select your preferred **Base Currency**
3. Click **Save Settings**

**Note:** Changing your base currency will trigger a recalculation job to update cached amounts.

### 2. Add Exchange Rates

Before using multiple currencies, add exchange rates:

1. Run the exchange rate seeder for default rates:
```bash
php artisan db:seed --class=ExchangeRateSeeder
```

2. Or manually add rates through the Exchange Rates management interface

### 3. Create Multi-Currency Transactions

When creating a transaction:

1. Click **Add Transaction**
2. Enter the amount
3. Select the **currency** from the dropdown (defaults to your account's base currency)
4. Complete other fields and save

## Best Practices

### 1. Keep Exchange Rates Updated

For accurate financial tracking:
- Update exchange rates regularly (daily or weekly)
- Use historical rates that match your transaction dates
- Consider using an automated API for daily rate updates (future feature)

### 2. Consistent Base Currency

- Choose a base currency that aligns with your primary financial planning
- Avoid changing base currency frequently as it triggers recalculations

### 3. Foreign Currency Transactions

When recording a transaction in a foreign currency:
- Use the actual amount in the foreign currency
- Ensure an exchange rate exists for that date
- The system will handle the conversion automatically

## API Integration (Future Feature)

In future versions, the application will support:
- Automatic exchange rate fetching from APIs like:
  - European Central Bank (ECB)
  - Open Exchange Rates
  - Currency Layer
- Scheduled daily rate updates
- Rate change notifications

## Technical Details

### Database Schema

#### Accounts Table
```sql
base_currency VARCHAR(3) DEFAULT 'USD'
```

#### Transactions Table
```sql
amount DECIMAL(19,4)
currency VARCHAR(3)
```

#### Budgets Table
```sql
amount DECIMAL(19,4)
currency VARCHAR(3)
```

#### Exchange Rates Table
```sql
base_currency VARCHAR(3)
target_currency VARCHAR(3)
rate DECIMAL(19,8)
date DATE
source VARCHAR(255)
UNIQUE(base_currency, target_currency, date)
```

### Currency Service

The `CurrencyService` provides methods for:

- `getSupportedCurrencies()` - Get all supported currencies
- `getCurrency($code)` - Get currency details by code
- `formatAmount($amount, $currency)` - Format amount with currency symbol
- `convert($amount, $from, $to, $date)` - Convert between currencies
- `getExchangeRate($from, $to, $date)` - Get exchange rate for a specific date
- `storeExchangeRate($base, $target, $rate, $date, $source)` - Store/update a rate

### Usage Example

```php
use App\Services\CurrencyService;

$currencyService = app(CurrencyService::class);

// Convert 100 USD to EUR
$converted = $currencyService->convert(
    amount: '100.00',
    fromCurrency: 'USD',
    toCurrency: 'EUR',
    date: '2024-12-30'
);

// Format amount with currency symbol
$formatted = $currencyService->formatAmount('100.00', 'EUR');
// Output: "€100.00"
```

## Troubleshooting

### Issue: Amounts not converting properly

**Solution:**
1. Check if exchange rates exist for the transaction date
2. Verify the exchange rate is in the correct direction (base → target)
3. Check the exchange rate value is correct

### Issue: Missing currency in dropdown

**Solution:**
The currency must be added to the `$supportedCurrencies` array in `CurrencyService.php`

### Issue: Incorrect totals in analytics

**Solution:**
1. Ensure all required exchange rates are present
2. Check if base currency conversion is working
3. Look for missing exchange rate warnings in logs

## Future Enhancements

Planned improvements for multi-currency support:

- [ ] Automatic exchange rate fetching from APIs
- [ ] Currency trend charts
- [ ] Exchange rate alerts for significant changes
- [ ] Support for cryptocurrencies
- [ ] Bulk exchange rate imports
- [ ] Exchange rate history visualization
- [ ] Multi-currency budget comparison
- [ ] Currency exposure reports

## Support

For questions or issues with multi-currency support:
1. Check this documentation
2. Review the exchange rates in your database
3. Check application logs for conversion errors
4. Open an issue on GitHub with details about the currency pair and date

---

**Last Updated:** December 30, 2024

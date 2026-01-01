# API Documentation

## Overview

This document outlines the RESTful API structure for the Personal Finance application. While the current implementation uses Inertia.js for the frontend, this API structure provides a foundation for future API-based integrations.

## Implemented Mobile API (v1)

These endpoints are now available for the native iOS client and use Sanctum bearer tokens.

### Base URL
```
/api/v1
```

### Authentication
```
POST /auth/register
POST /auth/login
POST /auth/logout
GET  /auth/me
```

### Account Scoping
For account-scoped resources (categories, transactions, budgets, savings goals), pass the active account via either:
- `X-Account-Id: {id}` header, or
- `account_id` query/body parameter

### Core Resources
```
GET    /accounts
POST   /accounts
GET    /accounts/{accountId}
PATCH  /accounts/{accountId}
DELETE /accounts/{accountId}

GET    /categories
POST   /categories
GET    /categories/{categoryId}
PATCH  /categories/{categoryId}
DELETE /categories/{categoryId}
POST   /categories/{categoryId}/subcategories
PATCH  /categories/{categoryId}/subcategories/{subcategoryId}
DELETE /categories/{categoryId}/subcategories/{subcategoryId}

GET    /transactions
POST   /transactions
GET    /transactions/{transactionId}
PATCH  /transactions/{transactionId}
DELETE /transactions/{transactionId}

GET    /dashboard
GET    /statistics

GET    /budgets
POST   /budgets
GET    /budgets/{budgetId}
PATCH  /budgets/{budgetId}
DELETE /budgets/{budgetId}

GET    /savings-goals
POST   /savings-goals
GET    /savings-goals/{savingsGoalId}
PATCH  /savings-goals/{savingsGoalId}
DELETE /savings-goals/{savingsGoalId}
```

## Authentication

All API requests require authentication. The application uses Laravel's built-in session-based authentication for web requests and can be extended to support token-based authentication (Laravel Sanctum) for API clients.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Base URL

```
https://api.personalfinance.app/api/v1
```

## Common Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    // Resource data
  },
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Endpoints

### Accounts

#### List Accounts
```
GET /accounts
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Family Budget",
      "base_currency": "USD",
      "description": "Main family account",
      "is_active": true,
      "created_at": "2025-01-01T00:00:00Z",
      "role": "owner"
    }
  ]
}
```

#### Get Account
```
GET /accounts/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Family Budget",
    "base_currency": "USD",
    "description": "Main family account",
    "is_active": true,
    "members_count": 3,
    "created_at": "2025-01-01T00:00:00Z"
  }
}
```

#### Create Account
```
POST /accounts
```

**Request:**
```json
{
  "name": "Personal Budget",
  "base_currency": "USD",
  "description": "My personal finances"
}
```

#### Update Account
```
PUT /accounts/{id}
```

#### Delete Account
```
DELETE /accounts/{id}
```

---

### Transactions

#### List Transactions
```
GET /accounts/{accountId}/transactions
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)
- `type` - Filter by type: expense, income, transfer
- `category_id` - Filter by category
- `date_from` - Start date (YYYY-MM-DD)
- `date_to` - End date (YYYY-MM-DD)
- `sort` - Sort field (date, amount, created_at)
- `order` - Sort order (asc, desc)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_id": 1,
      "type": "expense",
      "amount": "50.0000",
      "currency": "USD",
      "date": "2025-01-15",
      "category": {
        "id": 2,
        "name": "Food",
        "icon": "🍽️",
        "color": "#f59e0b"
      },
      "subcategory": {
        "id": 5,
        "name": "Groceries"
      },
      "description": "Weekly groceries",
      "payment_method": "Credit Card",
      "created_by": {
        "id": 1,
        "name": "John Doe"
      },
      "created_at": "2025-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150,
    "per_page": 15,
    "last_page": 10
  }
}
```

#### Get Transaction
```
GET /accounts/{accountId}/transactions/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "account_id": 1,
    "type": "expense",
    "amount": "50.0000",
    "currency": "USD",
    "date": "2025-01-15",
    "category_id": 2,
    "subcategory_id": 5,
    "description": "Weekly groceries",
    "payment_method": "Credit Card",
    "metadata": null,
    "created_by": 1,
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-01-15T10:30:00Z",
    "history": [
      {
        "id": 1,
        "action": "created",
        "changed_by": {
          "id": 1,
          "name": "John Doe"
        },
        "created_at": "2025-01-15T10:30:00Z"
      }
    ]
  }
}
```

#### Create Transaction
```
POST /accounts/{accountId}/transactions
```

**Request:**
```json
{
  "type": "expense",
  "amount": "50.00",
  "currency": "USD",
  "date": "2025-01-15",
  "category_id": 2,
  "subcategory_id": 5,
  "description": "Weekly groceries",
  "payment_method": "Credit Card",
  "metadata": {
    "location": "Walmart",
    "tags": ["groceries", "weekly"]
  }
}
```

**Validation Rules:**
- `type` - required, in:expense,income,transfer
- `amount` - required, numeric, min:0
- `currency` - required, in:USD,EUR,ALL
- `date` - required, date
- `category_id` - required, exists:categories,id
- `subcategory_id` - nullable, exists:subcategories,id
- `description` - nullable, string, max:1000
- `payment_method` - nullable, string, max:255
- `metadata` - nullable, json

#### Update Transaction
```
PUT /accounts/{accountId}/transactions/{id}
```

#### Delete Transaction
```
DELETE /accounts/{accountId}/transactions/{id}
```

---

### Categories

#### List Categories
```
GET /accounts/{accountId}/categories
```

**Query Parameters:**
- `type` - Filter by type: expense, income

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Food",
      "icon": "🍽️",
      "color": "#f59e0b",
      "type": "expense",
      "is_system": true,
      "order": 1,
      "subcategories": [
        {
          "id": 5,
          "name": "Groceries",
          "is_system": true,
          "order": 0
        },
        {
          "id": 6,
          "name": "Restaurant",
          "is_system": true,
          "order": 1
        }
      ]
    }
  ]
}
```

#### Get Category
```
GET /accounts/{accountId}/categories/{id}
```

#### Create Category
```
POST /accounts/{accountId}/categories
```

**Request:**
```json
{
  "name": "Investments",
  "icon": "📈",
  "color": "#10b981",
  "type": "income",
  "order": 10
}
```

#### Update Category
```
PUT /accounts/{accountId}/categories/{id}
```

**Note:** System categories cannot be updated.

#### Delete Category
```
DELETE /accounts/{accountId}/categories/{id}
```

**Note:** System categories cannot be deleted.

---

### Budgets

#### List Budgets
```
GET /accounts/{accountId}/budgets
```

**Query Parameters:**
- `period` - Filter by period: monthly, yearly
- `active` - Filter active budgets (true/false)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_id": 1,
      "category": {
        "id": 2,
        "name": "Food",
        "icon": "🍽️"
      },
      "subcategory": null,
      "amount": "500.0000",
      "currency": "USD",
      "period": "monthly",
      "start_date": "2025-01-01",
      "end_date": null,
      "progress": {
        "spent": "320.5000",
        "remaining": "179.5000",
        "percentage": 64.1,
        "is_overspent": false
      },
      "created_at": "2025-01-01T00:00:00Z"
    }
  ]
}
```

#### Get Budget
```
GET /accounts/{accountId}/budgets/{id}
```

#### Create Budget
```
POST /accounts/{accountId}/budgets
```

**Request:**
```json
{
  "category_id": 2,
  "subcategory_id": null,
  "amount": "500.00",
  "currency": "USD",
  "period": "monthly",
  "start_date": "2025-01-01",
  "end_date": null,
  "settings": {
    "alert_at_percentage": 80,
    "carry_over": true
  }
}
```

#### Update Budget
```
PUT /accounts/{accountId}/budgets/{id}
```

#### Delete Budget
```
DELETE /accounts/{accountId}/budgets/{id}
```

---

### Analytics

#### Dashboard Summary
```
GET /accounts/{accountId}/analytics/dashboard
```

**Query Parameters:**
- `month` - Month (MM format, default: current)
- `year` - Year (YYYY format, default: current)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_month_expenses": "1250.50",
    "current_month_income": "3000.00",
    "net_cash_flow": "1749.50",
    "expenses_by_category": [
      {
        "category": "Food",
        "icon": "🍽️",
        "color": "#f59e0b",
        "total": "450.00"
      },
      {
        "category": "Transport",
        "icon": "🚗",
        "color": "#8b5cf6",
        "total": "320.50"
      }
    ],
    "budget_usage": [
      {
        "id": 1,
        "category": "Food",
        "budget": "500.00",
        "spent": "450.00",
        "remaining": "50.00",
        "percentage": 90.0,
        "status": "warning"
      }
    ],
    "category_trends": {
      "Food": [
        {"month": "2024-08", "total": "420.00"},
        {"month": "2024-09", "total": "380.00"},
        {"month": "2024-10", "total": "450.00"}
      ]
    }
  }
}
```

#### Monthly Expenses by Category
```
GET /accounts/{accountId}/analytics/expenses-by-category
```

**Query Parameters:**
- `month` - Month (MM)
- `year` - Year (YYYY)

#### Category Trends
```
GET /accounts/{accountId}/analytics/category-trends
```

**Query Parameters:**
- `months` - Number of months to include (default: 6)

---

### Account Members

#### List Members
```
GET /accounts/{accountId}/members
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "owner",
      "is_active": true,
      "joined_at": "2025-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "admin",
      "is_active": true,
      "joined_at": "2025-01-02T00:00:00Z"
    }
  ]
}
```

#### Invite Member
```
POST /accounts/{accountId}/members/invite
```

**Request:**
```json
{
  "email": "newmember@example.com",
  "role": "member"
}
```

#### Update Member Role
```
PUT /accounts/{accountId}/members/{userId}
```

**Request:**
```json
{
  "role": "admin"
}
```

#### Remove Member
```
DELETE /accounts/{accountId}/members/{userId}
```

---

## Status Codes

- `200 OK` - Successful GET, PUT, PATCH
- `201 Created` - Successful POST
- `204 No Content` - Successful DELETE
- `400 Bad Request` - Invalid request format
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Rate Limiting

API requests are rate-limited to:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated users

Rate limit information is included in response headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640000000
```

## Pagination

List endpoints support pagination with the following parameters:
- `page` - Page number (starts at 1)
- `per_page` - Items per page (default: 15, max: 100)

Pagination metadata is included in the response:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "total": 150,
    "per_page": 15,
    "last_page": 10,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "/api/v1/transactions?page=1",
    "last": "/api/v1/transactions?page=10",
    "prev": null,
    "next": "/api/v1/transactions?page=2"
  }
}
```

## Filtering and Sorting

Most list endpoints support filtering and sorting:

**Filtering:**
```
GET /transactions?type=expense&category_id=2&date_from=2025-01-01
```

**Sorting:**
```
GET /transactions?sort=date&order=desc
```

## Webhook Events (Future)

Future webhook support for:
- `transaction.created`
- `transaction.updated`
- `transaction.deleted`
- `budget.threshold_reached`
- `budget.overspent`

## SDK Support (Future)

Official SDKs planned for:
- JavaScript/TypeScript
- Python
- PHP
- Ruby

## Versioning

API versioning is done via URL path:
- Current: `/api/v1`
- Future: `/api/v2`

Breaking changes will result in a new version. Non-breaking changes will be added to existing versions.

## Support

For API support and questions:
- Documentation: https://docs.personalfinance.app
- Email: api@personalfinance.app
- GitHub Issues: https://github.com/kleviscipi/personal-finance/issues

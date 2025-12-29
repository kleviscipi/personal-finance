# Frontend Implementation Guide

## Overview

The Personal Finance application features a modern, responsive frontend built with **Vue 3**, **Inertia.js**, and **TailwindCSS**. This guide covers the frontend architecture, components, and development workflow.

## Technology Stack

### Core Framework
- **Vue 3** (Composition API) - Progressive JavaScript framework
- **Inertia.js** - Modern monolith approach (no API needed)
- **TailwindCSS** - Utility-first CSS framework
- **Vite** - Lightning-fast build tool

### Additional Libraries
- **Chart.js** - Data visualization
- **vue-chartjs** - Vue wrapper for Chart.js
- **@heroicons/vue** - SVG icons
- **@tailwindcss/forms** - Beautiful form styles
- **Ziggy** - Laravel route helper for JavaScript

## Project Structure

```
resources/
├── css/
│   └── app.css                 # TailwindCSS imports
├── js/
│   ├── app.js                  # Main entry point
│   ├── bootstrap.js            # Axios configuration
│   ├── Layouts/
│   │   └── AppLayout.vue       # Main application layout
│   ├── Pages/
│   │   ├── Welcome.vue         # Landing page
│   │   ├── Dashboard.vue       # Analytics dashboard
│   │   ├── Transactions/
│   │   │   └── Index.vue       # Transaction list
│   │   ├── Budgets/
│   │   │   └── Index.vue       # Budget pages
│   │   ├── Categories/
│   │   │   └── Index.vue       # Category pages
│   │   └── Auth/
│   │       └── Login.vue       # Authentication
│   └── Components/             # Reusable components (future)
└── views/
    └── app.blade.php           # Root Inertia template
```

## Pages

### 1. Welcome Page (`/`)

**Purpose**: Marketing landing page

**Features**:
- Hero section with gradient background
- Feature cards
- Call-to-action buttons
- Responsive navigation

**Route**: `Route::get('/', ...)`

### 2. Dashboard (`/dashboard`)

**Purpose**: Main analytics and overview

**Features**:
- **4 Stat Cards**: Income, Expenses, Cash Flow, Active Budgets
- **Doughnut Chart**: Expenses by category (Chart.js)
- **Budget Progress Bars**: Visual budget usage
- **Recent Transactions**: Last 10 transactions
- **Quick Actions**: Add transaction button

**Props**:
```javascript
{
  auth: { user: User },
  currentAccount: Account,
  analytics: {
    current_month_income: string,
    current_month_expenses: string,
    net_cash_flow: string,
    expenses_by_category: Array,
    budget_usage: Array
  },
  recentTransactions: Array
}
```

**Route**: `GET /dashboard`

### 3. Transactions (`/transactions`)

**Purpose**: Manage all transactions

**Features**:
- **List View**: Paginated transaction list
- **Filters**: Type, category, date range
- **Actions**: Edit, delete
- **Empty State**: CTA when no transactions
- **Pagination**: Laravel pagination with links
- **Icons**: Transaction type visual indicators

**Props**:
```javascript
{
  transactions: PaginatedData,
  categories: Array,
  filters: {
    type: string,
    category_id: number,
    date_from: date,
    date_to: date
  }
}
```

**Route**: `GET /transactions`

### 4. Budgets (`/budgets`)

**Status**: Stub implementation

**Route**: `GET /budgets`

### 5. Categories (`/categories`)

**Status**: Stub implementation

**Route**: `GET /categories`

### 6. Auth Pages

**Login** (`/login`): Basic login page stub
**Register** (`/register`): Registration stub

## Layouts

### AppLayout.vue

Main application layout used by authenticated pages.

**Features**:
- Top navigation bar
- Logo and branding
- Navigation links with active state
- Account selector dropdown
- User menu dropdown
- Responsive design

**Props**:
```javascript
{
  auth: { user: User },
  currentAccount: Account
}
```

**Usage**:
```vue
<template>
  <AppLayout :auth="auth" :current-account="currentAccount">
    <!-- Page content -->
  </AppLayout>
</template>
```

## Styling

### TailwindCSS Configuration

**Custom Colors**:
```javascript
colors: {
  primary: {
    50: '#f0f9ff',   // Lightest
    500: '#0ea5e9',  // Main brand color
    950: '#082f49',  // Darkest
  }
}
```

**Font**: Inter (from Bunny Fonts)

### Color Usage

**Transaction Types**:
- Income: Green (`bg-green-100`, `text-green-600`)
- Expense: Red (`bg-red-100`, `text-red-600`)
- Transfer: Blue (`bg-blue-100`, `text-blue-600`)

**UI Elements**:
- Background: `bg-gray-50`
- Cards: `bg-white shadow rounded-lg`
- Primary buttons: `bg-primary-600 hover:bg-primary-700`
- Text hierarchy: `text-gray-900`, `text-gray-600`, `text-gray-500`

## Data Flow

### Inertia.js Flow

```
1. User clicks link/button
   ↓
2. Inertia intercepts request
   ↓
3. Makes XHR request to server
   ↓
4. Laravel controller returns Inertia::render()
   ↓
5. Inertia swaps component without page reload
   ↓
6. Preserves scroll position and form state
```

### Props Sharing

**Global Props** (defined in `HandleInertiaRequests`):
```javascript
{
  auth: { user: User },
  flash: { message, error },
  currentAccount: Account
}
```

**Page-Specific Props**:
Passed from controller via `Inertia::render($component, $props)`

## Charts

### Implementation (Chart.js + vue-chartjs)

```vue
<script setup>
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const chartData = {
  labels: ['Food', 'Transport', 'Shopping'],
  datasets: [{
    data: [300, 150, 200],
    backgroundColor: ['#f59e0b', '#8b5cf6', '#ef4444']
  }]
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' }
  }
};
</script>

<template>
  <Doughnut :data="chartData" :options="chartOptions" />
</template>
```

## Utility Functions

### Currency Formatting

```javascript
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currentAccount?.base_currency || 'USD',
  }).format(parseFloat(amount || 0));
};
```

### Date Formatting

```javascript
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
};
```

## Development Workflow

### Local Development

```bash
# Start Docker containers
docker-compose up -d

# Watch for changes (HMR enabled)
docker-compose exec app npm run dev

# Build for production
docker-compose exec app npm run build
```

### Hot Module Replacement (HMR)

Vite HMR is configured to work with Docker:

```javascript
// vite.config.js
server: {
  host: '0.0.0.0',
  port: 5173,
  hmr: {
    host: 'localhost',
  },
}
```

Access HMR dev server: http://localhost:5173

### Adding New Pages

1. **Create Vue component**:
```bash
touch resources/js/Pages/MyPage.vue
```

2. **Define route**:
```php
Route::get('/my-page', function () {
    return Inertia::render('MyPage', [
        'data' => $data
    ]);
});
```

3. **Access page**: Navigate to `/my-page`

## Responsive Design

All components use Tailwind's responsive utilities:

```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
  <!-- Responsive grid: 1 col mobile, 2 tablet, 4 desktop -->
</div>
```

**Breakpoints**:
- `sm:` - 640px
- `md:` - 768px
- `lg:` - 1024px
- `xl:` - 1280px
- `2xl:` - 1536px

## Form Handling

### Example Form with Inertia

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  amount: '',
  description: '',
});

const submit = () => {
  form.post(route('transactions.store'), {
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.amount" type="number" />
    <input v-model="form.description" type="text" />
    <button type="submit" :disabled="form.processing">
      Save
    </button>
    <div v-if="form.errors.amount">{{ form.errors.amount }}</div>
  </form>
</template>
```

## Navigation

### Using Inertia Links

```vue
<Link :href="route('dashboard')" class="...">
  Dashboard
</Link>
```

### Programmatic Navigation

```javascript
import { router } from '@inertiajs/vue3';

router.visit(route('transactions.index'));
router.get(url, data, options);
router.post(url, data, options);
```

## Icons

Using Heroicons:

```vue
<script setup>
import { HomeIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
</script>

<template>
  <HomeIcon class="h-6 w-6" />
</template>
```

## Performance Optimization

1. **Code Splitting**: Automatic with Vite
2. **Lazy Loading**: Use dynamic imports
3. **Asset Optimization**: Vite handles automatically
4. **Inertia Caching**: Preserves state between navigations

## Browser Support

- Chrome/Edge: Last 2 versions
- Firefox: Last 2 versions
- Safari: Last 2 versions
- Mobile: iOS Safari, Chrome Android

## Testing (Future)

Recommended setup:
- **Vitest** - Unit testing
- **@testing-library/vue** - Component testing
- **Cypress** - E2E testing

## Common Patterns

### Loading States

```vue
<button :disabled="form.processing">
  <span v-if="form.processing">Loading...</span>
  <span v-else>Submit</span>
</button>
```

### Error Handling

```vue
<div v-if="form.errors.field" class="text-red-600">
  {{ form.errors.field }}
</div>
```

### Empty States

```vue
<div v-if="items.length === 0" class="text-center py-8">
  <p class="text-gray-500">No items found</p>
  <Link :href="route('items.create')" class="...">
    Create your first item
  </Link>
</div>
```

## Deployment

### Production Build

```bash
npm run build
```

This creates optimized assets in `public/build/`.

### Asset Versioning

Vite automatically handles cache busting with hashed filenames.

## Troubleshooting

**Issue**: HMR not working
**Solution**: Check Docker port forwarding, restart Vite

**Issue**: Styles not loading
**Solution**: Run `npm run build`, clear browser cache

**Issue**: Routes not found
**Solution**: Run `php artisan route:cache`, check Ziggy

## Next Steps

1. **Complete CRUD Forms**: Add create/edit modals for transactions
2. **Implement Budgets**: Full budget management interface
3. **Category Management**: CRUD for categories with icon picker
4. **User Settings**: Profile and preferences
5. **Notifications**: Toast notifications for actions
6. **Dark Mode**: Implement dark theme
7. **PWA**: Progressive Web App features
8. **Testing**: Add comprehensive test suite

## Resources

- [Vue 3 Docs](https://vuejs.org/)
- [Inertia.js Docs](https://inertiajs.com/)
- [TailwindCSS Docs](https://tailwindcss.com/)
- [Chart.js Docs](https://www.chartjs.org/)
- [Vite Docs](https://vitejs.dev/)

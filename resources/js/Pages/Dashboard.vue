<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <div class="space-y-6 relative">
            <Link
                :href="route('transactions.create')"
                class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-sky-600 text-white shadow-[0_18px_36px_-16px_rgba(0,122,255,0.8)] transition hover:bg-sky-700 active:scale-95 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 sm:bottom-8 sm:right-8"
                aria-label="Add transaction"
                title="Add transaction"
            >
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </Link>
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Dashboard
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4"></div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Income Card -->
                <div class="pf-card overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-green-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Monthly Income
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ formatCurrency(analytics.current_month_income) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses Card -->
                <div class="pf-card overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-red-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Monthly Expenses
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ formatCurrency(analytics.current_month_expenses) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Savings Card -->
                <div class="pf-card overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="['rounded-md p-3', parseFloat(analytics.current_month_savings?.amount || 0) >= 0 ? 'bg-blue-500' : 'bg-orange-500']">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Monthly Savings
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ formatCurrency(analytics.current_month_savings?.amount || 0) }}
                                    </dd>
                                    <dd class="text-xs text-gray-500">
                                        {{ analytics.current_month_savings?.rate || 0 }}% rate
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Balance Card -->
                <div class="pf-card overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="['rounded-md p-3', parseFloat(analytics.total_balance || 0) >= 0 ? 'bg-indigo-500' : 'bg-red-500']">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Total Balance
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ formatCurrency(analytics.total_balance) }}
                                    </dd>
                                    <dd class="text-xs text-gray-500">
                                        Net Worth
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budgets Card -->
                <div class="pf-card overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-purple-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Active Budgets
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ analytics.budget_usage?.length || 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <!-- Expenses by Category -->
                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Expenses by Category
                        </h3>
                        <div class="h-64">
                            <Doughnut :data="expenseChartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Budget Usage -->
                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Budget Usage
                        </h3>
                        <div class="space-y-4">
                            <div v-for="budget in analytics.budget_usage?.slice(0, 5)" :key="budget.id" class="relative">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">{{ budget.category }}</span>
                                    <span class="text-sm text-gray-500">{{ formatCurrency(budget.spent) }} / {{ formatCurrency(budget.budget) }}</span>
                                </div>
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                    <div 
                                        :style="{ width: calculatePercentage(budget.spent, budget.budget) + '%' }" 
                                        :class="[
                                            'shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center',
                                            parseFloat(budget.spent) > parseFloat(budget.budget) ? 'bg-red-500' : parseFloat(budget.spent) > parseFloat(budget.budget) * 0.8 ? 'bg-yellow-500' : 'bg-green-500'
                                        ]"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="!analytics.budget_usage || analytics.budget_usage.length === 0" class="text-center py-8 text-gray-500">
                                <p>No budgets set for this month</p>
                                <Link :href="route('budgets.create')" class="text-sky-600 hover:text-sky-500 text-sm mt-2 inline-block">
                                    Create your first budget
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Trend Chart (Full Width) -->
            <div class="pf-card overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Balance & Savings Trend (12 Months)
                    </h3>
                    <div class="h-80">
                        <Line :data="balanceTrendChartData" :options="balanceTrendOptions" />
                    </div>
                </div>
            </div>

            <!-- Cash Flow & Income vs Expenses -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Cash Flow Trend
                        </h3>
                        <div class="h-64">
                            <Line :data="cashFlowChartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Income vs Expenses
                        </h3>
                        <div class="h-64">
                            <Bar :data="incomeExpenseChartData" :options="barOptions" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Categories, Top Subcategories & Budget Variance -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Top Categories (Last 30 Days)
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="category in analytics.top_categories || []"
                                :key="category.category"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: category.color || '#6b7280' }"
                                    ></span>
                                    <span class="text-sm text-gray-700">
                                        {{ category.category }}
                                    </span>
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ formatCurrency(category.total) }}
                                    <span class="text-xs text-gray-500">
                                        ({{ category.percentage }}%)
                                    </span>
                                </div>
                            </div>
                            <div
                                v-if="!analytics.top_categories || analytics.top_categories.length === 0"
                                class="text-sm text-gray-500"
                            >
                                No category data yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Top Subcategories (Last 30 Days)
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="subcategory in analytics.top_subcategories || []"
                                :key="subcategory.label || subcategory.subcategory"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: subcategory.color || '#6b7280' }"
                                    ></span>
                                    <span class="text-sm text-gray-700">
                                        {{ subcategory.label || subcategory.subcategory }}
                                    </span>
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ formatCurrency(subcategory.total) }}
                                    <span class="text-xs text-gray-500">
                                        ({{ subcategory.percentage }}%)
                                    </span>
                                </div>
                            </div>
                            <div
                                v-if="!analytics.top_subcategories || analytics.top_subcategories.length === 0"
                                class="text-sm text-gray-500"
                            >
                                No subcategory data yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Budget Variance
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="budget in analytics.budget_variance || []"
                                :key="budget.id"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: budget.color || '#6b7280' }"
                                    ></span>
                                    <span class="text-sm text-gray-700">
                                        {{ budget.category || 'Budget' }}
                                    </span>
                                </div>
                                <div
                                    :class="[
                                        'text-sm font-medium',
                                        parseFloat(budget.variance) > 0 ? 'text-red-600' : 'text-green-600',
                                    ]"
                                >
                                    {{ formatCurrency(budget.variance) }}
                                </div>
                            </div>
                            <div
                                v-if="!analytics.budget_variance || analytics.budget_variance.length === 0"
                                class="text-sm text-gray-500"
                            >
                                No budget variance data yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast, Savings Rate, Category Spikes -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Savings Rate
                        </h3>
                        <div class="text-3xl font-semibold text-gray-900">
                            {{ analytics.savings_rate?.rate || 0 }}%
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Income vs expenses this month.
                        </p>
                    </div>
                </div>

                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            30 / 90 Day Forecast
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">30 days</span>
                                <span class="font-medium text-gray-900">
                                    {{ formatCurrency(analytics.forecast?.forecast_30?.net || 0) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">90 days</span>
                                <span class="font-medium text-gray-900">
                                    {{ formatCurrency(analytics.forecast?.forecast_90?.net || 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pf-card overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Category Spikes
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="spike in analytics.category_spikes || []"
                                :key="spike.category"
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-gray-700">
                                    {{ spike.category }}
                                </span>
                                <span class="text-red-600">
                                    +{{ spike.delta_percent }}%
                                </span>
                            </div>
                            <div
                                v-if="!analytics.category_spikes || analytics.category_spikes.length === 0"
                                class="text-sm text-gray-500"
                            >
                                No spikes detected.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Change -->
            <div class="pf-card overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Balance Change (Net by Month)
                    </h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                        <div
                            v-for="row in analytics.monthly_summary || []"
                            :key="row.month"
                            class="rounded-lg border border-gray-200 p-3"
                        >
                            <div class="text-xs text-gray-500">{{ row.month }}</div>
                            <div
                                :class="[
                                    'text-sm font-medium',
                                    parseFloat(row.net) >= 0 ? 'text-green-600' : 'text-red-600',
                                ]"
                            >
                                {{ formatCurrency(row.net) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="pf-card overflow-hidden">
                <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Recent Transactions
                    </h3>
                    <Link :href="route('transactions.index')" class="text-sm font-medium text-sky-600 hover:text-sky-500">
                        View all
                    </Link>
                </div>
                <div class="border-t border-gray-200">
                    <ul role="list" class="divide-y divide-gray-200">
                        <li v-for="transaction in recentTransactions" :key="transaction.id" class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
                                        :class="transaction.category?.color ? '' : (transaction.type === 'income' ? 'bg-green-100' : 'bg-red-100')"
                                        :style="transaction.category?.color ? { backgroundColor: transaction.category.color } : {}"
                                    >
                                        <span :class="['text-lg', transaction.category?.color ? 'text-white' : (transaction.type === 'income' ? 'text-green-600' : 'text-red-600')]">
                                            {{ transaction.category?.icon || '💰' }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ transaction.description || transaction.category?.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ transaction.category?.name }}
                                            <span v-if="transaction.subcategory"> • {{ transaction.subcategory.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div :class="['text-sm font-medium', transaction.type === 'income' ? 'text-green-600' : 'text-gray-900']">
                                        {{ transaction.type === 'income' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ formatDate(transaction.date) }}
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li v-if="!recentTransactions || recentTransactions.length === 0" class="px-4 py-8 text-center">
                            <p class="text-gray-500">No transactions yet</p>
                            <Link :href="route('transactions.create')" class="text-sky-600 hover:text-sky-500 text-sm mt-2 inline-block">
                                Add your first transaction
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import { Doughnut, Line, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
} from 'chart.js';

ChartJS.register(
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
);

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    analytics: Object,
    recentTransactions: Array,
});

const expenseChartData = computed(() => {
    const expenses = props.analytics.expenses_by_category || [];
    return {
        labels: expenses.map(e => e.category),
        datasets: [{
            data: expenses.map(e => parseFloat(e.total)),
            backgroundColor: expenses.map(e => e.color || '#6b7280'),
        }],
    };
});

const monthlySummary = computed(() => props.analytics.monthly_summary || []);

const cashFlowChartData = computed(() => {
    return {
        labels: monthlySummary.value.map((row) => row.month),
        datasets: [
            {
                label: 'Net',
                data: monthlySummary.value.map((row) => parseFloat(row.net)),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.2)',
                tension: 0.35,
            },
        ],
    };
});

const incomeExpenseChartData = computed(() => {
    return {
        labels: monthlySummary.value.map((row) => row.month),
        datasets: [
            {
                label: 'Income',
                data: monthlySummary.value.map((row) => parseFloat(row.income)),
                backgroundColor: '#10b981',
            },
            {
                label: 'Expenses',
                data: monthlySummary.value.map((row) => parseFloat(row.expenses)),
                backgroundColor: '#ef4444',
            },
        ],
    };
});

const balanceHistory = computed(() => props.analytics.balance_history || []);

const balanceTrendChartData = computed(() => {
    return {
        labels: balanceHistory.value.map((row) => row.month),
        datasets: [
            {
                label: 'Total Balance',
                data: balanceHistory.value.map((row) => parseFloat(row.balance)),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.35,
                fill: true,
                yAxisID: 'y',
            },
            {
                label: 'Monthly Savings',
                data: balanceHistory.value.map((row) => parseFloat(row.savings)),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.35,
                fill: true,
                yAxisID: 'y1',
            },
        ],
    };
});

const balanceTrendOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'bottom',
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    label += new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: props.currentAccount?.base_currency || 'USD',
                    }).format(context.parsed.y);
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            type: 'linear',
            position: 'left',
            ticks: {
                callback: function(value) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: props.currentAccount?.base_currency || 'USD',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    }).format(value);
                }
            }
        },
        y1: {
            type: 'linear',
            position: 'right',
            grid: {
                drawOnChartArea: false,
            },
            ticks: {
                callback: function(value) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: props.currentAccount?.base_currency || 'USD',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    }).format(value);
                }
            }
        },
    },
};


const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
    scales: {
        x: {
            stacked: true,
        },
        y: {
            stacked: true,
        },
    },
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.currentAccount?.base_currency || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(parseFloat(amount || 0));
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const calculatePercentage = (spent, budget) => {
    const percentage = (parseFloat(spent) / parseFloat(budget)) * 100;
    return Math.min(percentage, 100);
};
</script>

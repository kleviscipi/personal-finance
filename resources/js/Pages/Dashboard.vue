<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <div class="space-y-6">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Dashboard
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <Link :href="route('transactions.create')" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Transaction
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Income Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
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
                <div class="bg-white overflow-hidden shadow rounded-lg">
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

                <!-- Net Cash Flow Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="['rounded-md p-3', parseFloat(analytics.net_cash_flow) >= 0 ? 'bg-blue-500' : 'bg-orange-500']">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Net Cash Flow
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ formatCurrency(analytics.net_cash_flow) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budgets Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
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
                <div class="bg-white overflow-hidden shadow rounded-lg">
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
                <div class="bg-white overflow-hidden shadow rounded-lg">
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
                                <Link :href="route('budgets.create')" class="text-primary-600 hover:text-primary-500 text-sm mt-2 inline-block">
                                    Create your first budget
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Recent Transactions
                    </h3>
                    <Link :href="route('transactions.index')" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        View all
                    </Link>
                </div>
                <div class="border-t border-gray-200">
                    <ul role="list" class="divide-y divide-gray-200">
                        <li v-for="transaction in recentTransactions" :key="transaction.id" class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div :class="['flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center', transaction.type === 'income' ? 'bg-green-100' : 'bg-red-100']">
                                        <span :class="['text-lg', transaction.type === 'income' ? 'text-green-600' : 'text-red-600']">
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
                            <Link :href="route('transactions.create')" class="text-primary-600 hover:text-primary-500 text-sm mt-2 inline-block">
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
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

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
            backgroundColor: [
                '#f59e0b',
                '#8b5cf6',
                '#ef4444',
                '#06b6d4',
                '#ec4899',
                '#f97316',
                '#14b8a6',
                '#6b7280',
            ],
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.currentAccount?.base_currency || 'USD',
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

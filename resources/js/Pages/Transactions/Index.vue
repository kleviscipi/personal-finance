<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <div class="space-y-6">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Transactions
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <Link :href="route('transactions.create')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Transaction
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select v-model="filters.type" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            <option value="">All Types</option>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select v-model="filters.category_id" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" v-model="filters.date_from" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" v-model="filters.date_to" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <ul role="list" class="divide-y divide-gray-200">
                    <li v-for="transaction in transactions.data" :key="transaction.id" class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <div :class="['flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center', 
                                    transaction.type === 'income' ? 'bg-green-100' : 
                                    transaction.type === 'transfer' ? 'bg-blue-100' : 'bg-red-100']">
                                    <span :class="['text-2xl', 
                                        transaction.type === 'income' ? 'text-green-600' : 
                                        transaction.type === 'transfer' ? 'text-blue-600' : 'text-red-600']">
                                        {{ transaction.category?.icon || '💰' }}
                                    </span>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ transaction.description || transaction.category?.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ transaction.category?.name }}
                                        <span v-if="transaction.subcategory"> • {{ transaction.subcategory.name }}</span>
                                        <span v-if="transaction.payment_method" class="ml-2">• {{ transaction.payment_method }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <div :class="['text-sm font-semibold', 
                                        transaction.type === 'income' ? 'text-green-600' : 
                                        transaction.type === 'transfer' ? 'text-blue-600' : 'text-gray-900']">
                                        {{ transaction.type === 'income' ? '+' : transaction.type === 'transfer' ? '↔' : '-' }}{{ formatCurrency(transaction.amount) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ formatDate(transaction.date) }}
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <Link :href="route('transactions.edit', transaction.id)" class="text-primary-600 hover:text-primary-900">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button @click="deleteTransaction(transaction.id)" class="text-red-600 hover:text-red-900">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li v-if="!transactions.data || transactions.data.length === 0" class="px-4 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new transaction.</p>
                        <div class="mt-6">
                            <Link :href="route('transactions.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Transaction
                            </Link>
                        </div>
                    </li>
                </ul>

                <!-- Pagination -->
                <div v-if="transactions.data && transactions.data.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link v-if="transactions.prev_page_url" :href="transactions.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </Link>
                        <Link v-if="transactions.next_page_url" :href="transactions.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ transactions.from }}</span>
                                to
                                <span class="font-medium">{{ transactions.to }}</span>
                                of
                                <span class="font-medium">{{ transactions.total }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <Link v-for="link in transactions.links" :key="link.label" :href="link.url" :class="[
                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                    link.active ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                    !link.url ? 'cursor-not-allowed opacity-50' : ''
                                ]" v-html="link.label">
                                </Link>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    transactions: Object,
    categories: Array,
    filters: Object,
});

const filters = ref({
    type: props.filters?.type || '',
    category_id: props.filters?.category_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('transactions.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
};

const deleteTransaction = (id) => {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(route('transactions.destroy', id));
    }
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
</script>

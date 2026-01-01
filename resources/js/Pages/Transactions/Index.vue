<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Transactions" />
        <div class="space-y-6">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Transactions
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <Link :href="route('transactions.create')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Transaction
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="pf-card p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
                        @click="showAdvanced = !showAdvanced"
                    >
                        <span>Advanced filters</span>
                        <svg
                            class="h-4 w-4 transition-transform"
                            :class="showAdvanced ? 'rotate-180' : ''"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Search</label>
                        <input
                            type="text"
                            v-model="filters.q"
                            @keyup.enter="applyFilters"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Description, category, user..."
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select v-model="filters.type" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Types</option>
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select v-model="filters.category_id" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div v-show="showAdvanced" class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subcategory</label>
                        <select v-model="filters.subcategory_id" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" :disabled="!availableSubcategories.length">
                            <option value="">All Subcategories</option>
                            <option v-for="subcategory in availableSubcategories" :key="subcategory.id" :value="subcategory.id">
                                {{ subcategory.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment method</label>
                        <select v-model="filters.payment_method" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Methods</option>
                            <option v-for="option in paymentOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created by</label>
                        <select v-model="filters.created_by" @change="applyFilters" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Any user</option>
                            <option v-for="user in accountUsers || []" :key="user.id" :value="user.id">
                                {{ user.name || user.email || user.id }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" v-model="filters.date_from" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" v-model="filters.date_to" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Min amount</label>
                        <input type="number" step="0.01" v-model="filters.amount_min" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Max amount</label>
                        <input type="number" step="0.01" v-model="filters.amount_max" @change="applyFilters" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Tags</label>
                        <TagInput
                            v-model="filters.tag_list"
                            :suggestions="tags"
                            placeholder="Filter by tags"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                        @click="resetFilters"
                    >
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 111.64 5.64L3 21v-4m0-5h4" />
                        </svg>
                        Reset
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                        @click="applyFilters"
                    >
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Apply
                    </button>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="pf-card overflow-hidden">
                <ul role="list" class="divide-y divide-gray-200">
                    <li v-for="transaction in transactions.data" :key="transaction.id" class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1">
                                <div
                                    class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center"
                                    :class="transaction.category?.color ? '' : [
                                        transaction.type === 'income' ? 'bg-green-100' :
                                        transaction.type === 'transfer' ? 'bg-blue-100' : 'bg-red-100'
                                    ]"
                                    :style="transaction.category?.color ? { backgroundColor: transaction.category.color } : {}"
                                >
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
                                        <span v-if="transaction.creator" class="ml-2">• by {{ transaction.creator.name || transaction.creator.email || 'Unknown' }}</span>
                                        <span v-if="transaction.latest_history" class="ml-2">
                                            • last {{ formatHistoryAction(transaction.latest_history.action) }}
                                            by {{ transaction.latest_history.user?.name || transaction.latest_history.user?.email || 'Unknown' }}
                                            on {{ formatDateTime(transaction.latest_history.created_at) }}
                                        </span>
                                    </div>
                                    <div v-if="transaction.tags && transaction.tags.length" class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="tag in transaction.tags"
                                            :key="tag.id"
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600"
                                        >
                                            {{ tag.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <div :class="['text-sm font-semibold', 
                                        transaction.type === 'income' ? 'text-green-600' : 
                                        transaction.type === 'transfer' ? 'text-blue-600' : 'text-gray-900']">
                                        {{ transaction.type === 'income' ? '+' : transaction.type === 'transfer' ? '↔' : '-' }}{{ formatCurrency(transaction.amount, transaction.currency) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ formatDate(transaction.date) }}
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <Link :href="route('transactions.edit', transaction.id)" class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button @click="requestDeleteTransaction(transaction)" class="text-red-600 hover:text-red-900">
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
                            <Link :href="route('transactions.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Transaction
                            </Link>
                        </div>
                    </li>
                </ul>

                <!-- Pagination -->
                <div v-if="transactions.data && transactions.data.length > 0" class="bg-white/60 px-4 py-3 flex items-center justify-between border-t border-slate-200/60 sm:px-6">
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
                                <template v-for="link in transactions.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        ]"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium cursor-not-allowed opacity-50',
                                            link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500',
                                        ]"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="confirmingDelete"
            title="Delete transaction?"
            :message="deleteMessage"
            confirm-text="Delete"
            @close="closeDeleteModal"
            @confirm="confirmDeleteTransaction"
        />
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import TagInput from '@/Components/TagInput.vue';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    transactions: Object,
    categories: Array,
    tags: Array,
    accountUsers: Array,
    filters: Object,
});

const normalizeArrayFilter = (value) => {
    if (Array.isArray(value)) {
        return value;
    }
    if (value === null || value === undefined || value === '') {
        return [];
    }
    return [value];
};

const tagNameById = computed(() => {
    const map = new Map();
    (props.tags || []).forEach((tag) => {
        map.set(Number(tag.id), tag.name);
    });
    return map;
});

const resolveTagListFromIds = (values) => {
    return normalizeArrayFilter(values)
        .map((id) => tagNameById.value.get(Number(id)))
        .filter((name) => name);
};

const filters = ref({
    q: props.filters?.q || '',
    type: props.filters?.type || '',
    category_id: props.filters?.category_id || '',
    subcategory_id: props.filters?.subcategory_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    amount_min: props.filters?.amount_min || '',
    amount_max: props.filters?.amount_max || '',
    payment_method: props.filters?.payment_method || '',
    created_by: props.filters?.created_by || '',
    tag_ids: normalizeArrayFilter(props.filters?.tag_ids),
    tag_list: resolveTagListFromIds(props.filters?.tag_ids),
});

const showAdvanced = ref(false);

const paymentOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'mobile_wallet', label: 'Mobile wallet' },
    { value: 'opening_balance', label: 'Opening balance' },
    { value: 'other', label: 'Other' },
];

const availableSubcategories = computed(() => {
    const categoryId = Number(filters.value.category_id || 0);
    if (!categoryId) {
        return [];
    }
    const category = (props.categories || []).find((item) => item.id === categoryId);
    return category?.subcategories || [];
});

const applyFilters = () => {
    const tagIds = (filters.value.tag_list || [])
        .map((name) =>
            (props.tags || []).find(
                (tag) => tag.name.toLowerCase() === name.toLowerCase(),
            )?.id,
        )
        .filter((id) => id !== undefined && id !== null);

    router.get(route('transactions.index'), {
        ...filters.value,
        tag_ids: tagIds,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(
    () => filters.value.category_id,
    () => {
        if (!availableSubcategories.value.length) {
            filters.value.subcategory_id = '';
            return;
        }

        const subcategoryId = Number(filters.value.subcategory_id || 0);
        const exists = availableSubcategories.value.some(
            (subcategory) => subcategory.id === subcategoryId,
        );
        if (!exists) {
            filters.value.subcategory_id = '';
        }
    },
);

const resetFilters = () => {
    filters.value = {
        q: '',
        type: '',
        category_id: '',
        subcategory_id: '',
        date_from: '',
        date_to: '',
        amount_min: '',
        amount_max: '',
        payment_method: '',
        created_by: '',
        tag_ids: [],
        tag_list: [],
    };
    applyFilters();
};

const confirmingDelete = ref(false);
const pendingTransaction = ref(null);

const requestDeleteTransaction = (transaction) => {
    pendingTransaction.value = transaction;
    confirmingDelete.value = true;
};

const closeDeleteModal = () => {
    confirmingDelete.value = false;
    pendingTransaction.value = null;
};

const confirmDeleteTransaction = () => {
    if (!pendingTransaction.value) {
        return;
    }

    router.delete(route('transactions.destroy', pendingTransaction.value.id), {
        onFinish: () => closeDeleteModal(),
    });
};

const deleteMessage = computed(() => {
    const transaction = pendingTransaction.value;
    if (!transaction) {
        return 'Delete this transaction?';
    }
    const label = transaction.description || transaction.category?.name || 'this transaction';
    return `Delete ${label}?`;
});

const formatCurrency = (amount, currency) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || props.currentAccount?.base_currency || 'USD',
    }).format(parseFloat(amount || 0));
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const formatHistoryAction = (action) => {
    if (action === 'created') {
        return 'created';
    }
    if (action === 'updated') {
        return 'updated';
    }
    if (action === 'deleted') {
        return 'deleted';
    }
    return action || 'updated';
};
</script>

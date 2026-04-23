<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    recurringTransactions: Array,
    dueCount: Number,
});

const page = usePage();

const flashMessage = computed(() => page.props.flash?.message || null);
const confirmingDelete = ref(false);
const pendingRecurring = ref(null);

const requestDeleteRecurring = (recurring) => {
    pendingRecurring.value = recurring;
    confirmingDelete.value = true;
};

const closeDeleteModal = () => {
    confirmingDelete.value = false;
    pendingRecurring.value = null;
};

const confirmDeleteRecurring = () => {
    if (!pendingRecurring.value) {
        return;
    }

    router.delete(route('recurring-transactions.destroy', pendingRecurring.value.id), {
        onFinish: () => closeDeleteModal(),
    });
};

const runDueTransactions = () => {
    router.post(route('recurring-transactions.run-due'));
};

const formatCurrency = (amount, currency) => {
    const numeric = parseFloat(amount || 0);
    if (Number.isNaN(numeric)) {
        return `${amount} ${currency || ''}`.trim();
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(numeric);
};

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatDateTime = (value) => {
    if (!value) {
        return 'Never';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const capitalize = (value) => {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
};

const formatSchedule = (recurring) => {
    const interval = parseInt(recurring.interval || 1, 10);
    const frequency = recurring.frequency || 'monthly';

    if (interval === 1) {
        return capitalize(frequency);
    }

    const singular = {
        daily: 'day',
        weekly: 'week',
        monthly: 'month',
        yearly: 'year',
    };

    return `Every ${interval} ${singular[frequency] || frequency}${interval > 1 ? 's' : ''}`;
};

const deleteMessage = computed(() => {
    const recurring = pendingRecurring.value;
    if (!recurring) {
        return 'Delete this recurring transaction?';
    }

    const label = recurring.description || recurring.category?.name || 'this recurring transaction';

    return `Delete ${label}?`;
});
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Recurring Transactions" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Recurring Transactions
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Automate regular income, bills, subscriptions, and other repeating entries.
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="!dueCount"
                        @click="runDueTransactions"
                    >
                        Run Due Now
                    </button>
                    <Link
                        :href="route('recurring-transactions.create')"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Create Recurring
                    </Link>
                </div>
            </div>

            <div
                v-if="flashMessage"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
            >
                {{ flashMessage }}
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_22rem]">
                <div class="pf-card">
                    <div v-if="!recurringTransactions || recurringTransactions.length === 0" class="px-6 py-12 text-center text-sm text-gray-600">
                        <p>No recurring transactions yet.</p>
                        <Link
                            :href="route('recurring-transactions.create')"
                            class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Create Recurring
                        </Link>
                    </div>
                    <div v-else class="divide-y divide-slate-200">
                        <div
                            v-for="recurring in recurringTransactions"
                            :key="recurring.id"
                            class="flex flex-col gap-4 px-6 py-5 xl:flex-row xl:items-center xl:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900">
                                        {{ recurring.description || recurring.category?.name || 'Recurring transaction' }}
                                    </h3>
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="recurring.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ recurring.is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-slate-600">
                                    {{ capitalize(recurring.type) }}
                                    <span v-if="recurring.user"> • for {{ recurring.user.name || recurring.user.email || `User ${recurring.user.id}` }}</span>
                                    <span v-if="recurring.category"> • {{ recurring.category.name }}</span>
                                    <span v-if="recurring.subcategory"> • {{ recurring.subcategory.name }}</span>
                                    <span v-if="recurring.payment_method"> • {{ recurring.payment_method }}</span>
                                </div>
                                <div v-if="recurring.tags?.length" class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="tag in recurring.tags"
                                        :key="tag.id"
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid gap-4 text-sm text-slate-600 sm:grid-cols-2 xl:w-[30rem]">
                                <div>
                                    <div class="font-medium text-slate-900">
                                        {{ formatCurrency(recurring.amount, recurring.currency) }}
                                    </div>
                                    <div>{{ formatSchedule(recurring) }}</div>
                                </div>
                                <div>
                                    <div class="font-medium text-slate-900">
                                        Next run {{ formatDate(recurring.next_run_date) }}
                                    </div>
                                    <div>
                                        Ends {{ recurring.end_date ? formatDate(recurring.end_date) : 'never' }}
                                    </div>
                                </div>
                                <div class="sm:col-span-2 flex items-center justify-between gap-3">
                                    <div class="text-xs text-slate-500">
                                        Last generated {{ formatDateTime(recurring.last_generated_at) }}
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <Link
                                            :href="route('recurring-transactions.edit', recurring.id)"
                                            class="text-sm font-medium text-sky-600 hover:text-sky-700"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-red-600 hover:text-red-700"
                                            @click="requestDeleteRecurring(recurring)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="pf-card p-5">
                        <div class="text-sm font-semibold text-slate-900">
                            Due Today
                        </div>
                        <div class="mt-2 text-3xl font-bold text-slate-900">
                            {{ dueCount || 0 }}
                        </div>
                        <p class="mt-2 text-sm text-slate-600">
                            Templates that should generate transactions now or are already overdue.
                        </p>
                    </div>

                    <div class="pf-card p-5">
                        <div class="text-sm font-semibold text-slate-900">
                            How It Works
                        </div>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li>Create a recurring template once.</li>
                            <li>Run Due Now creates all overdue transactions up to today.</li>
                            <li>A daily scheduled command also processes due items automatically.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirmingDelete"
            title="Delete recurring transaction?"
            :message="deleteMessage"
            confirm-text="Delete"
            @close="closeDeleteModal"
            @confirm="confirmDeleteRecurring"
        />
    </AppLayout>
</template>

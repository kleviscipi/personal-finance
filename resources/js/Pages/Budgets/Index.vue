<script setup>
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    auth: Object,
    currentAccount: Object,
    budgets: Array,
});

const formatAmount = (amount, currency) => {
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
        return '';
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

const confirmingDelete = ref(false);
const pendingBudget = ref(null);

const requestDeleteBudget = (budget) => {
    pendingBudget.value = budget;
    confirmingDelete.value = true;
};

const closeDeleteModal = () => {
    confirmingDelete.value = false;
    pendingBudget.value = null;
};

const confirmDeleteBudget = () => {
    if (!pendingBudget.value) {
        return;
    }

    router.delete(route('budgets.destroy', pendingBudget.value.id), {
        onFinish: () => closeDeleteModal(),
    });
};

const deleteMessage = computed(() => {
    const budget = pendingBudget.value;
    if (!budget) {
        return 'Delete this budget?';
    }
    const name = budget.category?.name || 'this budget';
    const sub = budget.subcategory?.name ? ` • ${budget.subcategory.name}` : '';
    return `Delete ${name}${sub}?`;
});
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Budgets" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Budgets</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Track spending limits by category or subcategory.
                    </p>
                </div>
                <Link
                    :href="route('budgets.create')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Create Budget
                </Link>
            </div>

            <div class="pf-card">
                <div class="divide-y divide-gray-200">
                    <div
                        v-if="!budgets || budgets.length === 0"
                        class="px-6 py-12 text-center text-sm text-gray-600"
                    >
                        <p>No budgets yet. Create your first budget to start tracking targets.</p>
                        <Link
                            :href="route('budgets.create')"
                            class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Create Budget
                        </Link>
                    </div>
                    <div
                        v-for="budget in budgets"
                        :key="budget.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ budget.category?.name || 'Overall Budget' }}
                                <span v-if="budget.subcategory" class="text-gray-500">
                                    • {{ budget.subcategory.name }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ budget.period }} • {{ formatDate(budget.start_date) }}
                                <span v-if="budget.end_date">
                                    – {{ formatDate(budget.end_date) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-gray-900">
                                {{ formatAmount(budget.amount, budget.currency) }}
                            </span>
                            <Link
                                :href="route('budgets.edit', budget.id)"
                                class="text-sm text-sky-600 hover:text-sky-700"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                class="text-sm text-red-600 hover:text-red-700"
                                @click="requestDeleteBudget(budget)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="confirmingDelete"
            title="Delete budget?"
            :message="deleteMessage"
            confirm-text="Delete"
            @close="closeDeleteModal"
            @confirm="confirmDeleteBudget"
        />
    </AppLayout>
</template>

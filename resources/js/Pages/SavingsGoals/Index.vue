<script setup>
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    goals: Array,
});

const formatCurrency = (value, currency) => {
    const numeric = parseFloat(value || 0);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || props.currentAccount?.base_currency || 'USD',
    }).format(Number.isNaN(numeric) ? 0 : numeric);
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

const trackingLabel = (goal) => {
    if (goal.tracking_mode === 'manual') {
        return 'Manual only';
    }
    if (goal.tracking_mode === 'subcategory') {
        return goal.subcategory?.name ? `Subcategory: ${goal.subcategory.name}` : 'Subcategory';
    }
    if (goal.tracking_mode === 'category') {
        return goal.category?.name ? `Category: ${goal.category.name}` : 'Category';
    }
    return 'Net savings';
};

const ownerLabel = (goal) => {
    if (goal.user) {
        return `Personal: ${goal.user.name || goal.user.email || goal.user.id}`;
    }
    return 'Account-wide';
};

const monthlyInputs = reactive({});

const projectedCompletion = (goal) => {
    const remaining = Math.max(0, parseFloat(goal.progress?.remaining || 0));
    const fallback = parseFloat(goal.projection?.average_monthly || 0);
    const monthly = parseFloat(monthlyInputs[goal.id] ?? fallback);

    if (!monthly || monthly <= 0) {
        return null;
    }
    const monthsNeeded = Math.max(1, Math.ceil(remaining / monthly));
    const date = new Date();
    date.setMonth(date.getMonth() + monthsNeeded);
    return date.toISOString().slice(0, 10);
};

const requiredMonthly = (goal) => {
    const remaining = Math.max(0, parseFloat(goal.progress?.remaining || 0));
    if (!goal.target_date || remaining <= 0) {
        return 0;
    }
    const target = new Date(goal.target_date);
    const now = new Date();
    const diffDays = Math.ceil((target - now) / (1000 * 60 * 60 * 24));
    if (diffDays <= 0) {
        return null;
    }
    const months = Math.max(1, Math.ceil(diffDays / 30));
    return remaining / months;
};

const progressTone = (percentage) => {
    const value = parseFloat(percentage || 0);
    if (value >= 100) {
        return 'from-emerald-500 to-emerald-400';
    }
    if (value >= 60) {
        return 'from-sky-500 to-sky-400';
    }
    return 'from-amber-500 to-amber-400';
};

const confirmingDelete = ref(false);
const pendingGoal = ref(null);

const requestDelete = (goal) => {
    pendingGoal.value = goal;
    confirmingDelete.value = true;
};

const closeDelete = () => {
    confirmingDelete.value = false;
    pendingGoal.value = null;
};

const confirmDelete = () => {
    if (!pendingGoal.value) {
        return;
    }
    router.delete(route('savings-goals.destroy', pendingGoal.value.id), {
        onFinish: () => closeDelete(),
    });
};

const deleteMessage = computed(() => {
    if (!pendingGoal.value) {
        return 'Delete this savings goal?';
    }
    return `Delete ${pendingGoal.value.name}?`;
});
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Savings Goals" />

        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Savings Goals</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Track progress and test what-if scenarios without leaving the dashboard.
                    </p>
                </div>
                <Link
                    :href="route('savings-goals.create')"
                    class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800"
                >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Goal
                </Link>
            </div>

            <div v-if="!goals || goals.length === 0" class="pf-card p-10 text-center">
                <p class="text-sm text-slate-500">No savings goals yet. Create your first goal to start tracking.</p>
                <Link
                    :href="route('savings-goals.create')"
                    class="mt-4 inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800"
                >
                    Create Goal
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    v-for="goal in goals"
                    :key="goal.id"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ goal.name }}</div>
                            <div class="mt-1 text-sm text-slate-500">
                                Target {{ formatDate(goal.target_date) }} • {{ trackingLabel(goal) }}
                                <span class="ml-2 text-xs text-slate-400">
                                    {{ ownerLabel(goal) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('savings-goals.edit', goal.id)"
                                class="rounded-md border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 hover:border-slate-300"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                class="rounded-md border border-rose-200 px-3 py-1 text-xs font-medium text-rose-600 hover:border-rose-300"
                                @click="requestDelete(goal)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center justify-between text-sm text-slate-600">
                            <span>
                                {{ formatCurrency(goal.progress?.current_amount, goal.currency) }}
                                <span class="text-slate-400">/</span>
                                {{ formatCurrency(goal.target_amount, goal.currency) }}
                            </span>
                            <span>{{ Number.parseFloat(goal.progress?.percentage || 0).toFixed(0) }}%</span>
                        </div>
                        <div class="mt-2 h-2 w-full rounded-full bg-slate-100">
                            <div
                                class="h-2 rounded-full bg-gradient-to-r"
                                :class="progressTone(goal.progress?.percentage)"
                                :style="{ width: Math.min(100, goal.progress?.percentage || 0) + '%' }"
                            ></div>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            {{ formatCurrency(goal.progress?.remaining, goal.currency) }} remaining
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <div class="text-sm font-medium text-slate-700">What-if projection</div>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Monthly contribution
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="monthlyInputs[goal.id]"
                                    :placeholder="goal.projection?.average_monthly || 0"
                                    class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                >
                            </div>
                            <div class="space-y-2 text-sm text-slate-600">
                                <div class="flex items-center justify-between">
                                    <span>Projected finish</span>
                                    <span class="font-medium text-slate-800">
                                        {{ projectedCompletion(goal) ? formatDate(projectedCompletion(goal)) : '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Monthly needed</span>
                                    <span class="font-medium text-slate-800">
                                        {{
                                            requiredMonthly(goal) === null
                                                ? '—'
                                                : formatCurrency(requiredMonthly(goal), goal.currency)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-slate-500">
                            Based on your tracking mode. Adjust the monthly amount to explore scenarios.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirmingDelete"
            title="Delete savings goal?"
            :message="deleteMessage"
            confirm-text="Delete"
            @close="closeDelete"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>

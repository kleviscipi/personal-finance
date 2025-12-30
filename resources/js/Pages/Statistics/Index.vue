<script setup>
import { computed, reactive, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
);

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    analytics: Object,
    filters: Object,
});

const filters = reactive({
    start: props.filters?.start || '',
    end: props.filters?.end || '',
});

watch(
    () => props.filters,
    (value) => {
        filters.start = value?.start || '';
        filters.end = value?.end || '';
    },
);

const monthlySummary = computed(() => props.analytics?.monthly_summary || []);

const cashFlowChart = computed(() => ({
    labels: monthlySummary.value.map((row) => row.month),
    datasets: [
        {
            label: 'Net',
            data: monthlySummary.value.map((row) => parseFloat(row.net)),
            borderColor: '#0ea5e9',
            backgroundColor: 'rgba(14,165,233,0.2)',
            tension: 0.35,
        },
    ],
}));

const incomeExpenseChart = computed(() => ({
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
}));

const transferChart = computed(() => ({
    labels: monthlySummary.value.map((row) => row.month),
    datasets: [
        {
            label: 'Transfers',
            data: monthlySummary.value.map((row) => parseFloat(row.transfers || 0)),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.2)',
            tension: 0.35,
        },
    ],
}));

const categoryMixChart = computed(() => ({
    labels: props.analytics?.category_mix?.months || [],
    datasets: (props.analytics?.category_mix?.series || []).map((series) => ({
        label: series.category,
        data: series.values.map((value) => parseFloat(value)),
        backgroundColor: series.color || '#94a3b8',
        borderWidth: 0,
        fill: true,
    })),
}));

const subcategoryMixChart = computed(() => ({
    labels: props.analytics?.subcategory_mix?.months || [],
    datasets: (props.analytics?.subcategory_mix?.series || []).map((series) => ({
        label: series.label || series.subcategory,
        data: series.values.map((value) => parseFloat(value)),
        backgroundColor: series.color || '#94a3b8',
        borderWidth: 0,
        fill: true,
    })),
}));

const expenseShareChart = computed(() => ({
    labels: props.analytics?.expense_share?.months || [],
    datasets: (props.analytics?.expense_share?.series || []).map((series) => ({
        label: series.category,
        data: series.values.map((value) => parseFloat(value)),
        backgroundColor: series.color || '#94a3b8',
        borderWidth: 0,
    })),
}));

const medianExpenseChart = computed(() => {
    const months = monthlySummary.value.map((row) => row.month);
    const median = parseFloat(props.analytics?.median_expense || 0);

    return {
        labels: months,
        datasets: [
            {
                type: 'bar',
                label: 'Monthly Expenses',
                data: monthlySummary.value.map((row) => parseFloat(row.expenses)),
                backgroundColor: '#f97316',
            },
            {
                type: 'line',
                label: 'Median',
                data: months.map(() => median),
                borderColor: '#0ea5e9',
                borderDash: [6, 4],
                pointRadius: 0,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
    },
};

const barOptions = {
    ...chartOptions,
    scales: {
        x: { stacked: true },
        y: { stacked: true },
    },
};

const shareOptions = {
    ...barOptions,
    scales: {
        x: { stacked: true },
        y: {
            stacked: true,
            min: 0,
            max: 100,
            ticks: {
                callback: (value) => `${value}%`,
            },
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

const applyFilters = () => {
    router.get(route('statistics.index'), filters, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Statistics" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Statistics</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Explore trends and totals across custom date ranges.
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <InputLabel for="start" value="Start date" />
                        <TextInput
                            id="start"
                            type="date"
                            class="mt-1 block"
                            v-model="filters.start"
                            @change="applyFilters"
                        />
                    </div>
                    <div>
                        <InputLabel for="end" value="End date" />
                        <TextInput
                            id="end"
                            type="date"
                            class="mt-1 block"
                            v-model="filters.end"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Statistics exclude opening balance adjustments from income and expense totals.
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-6">
                <div class="pf-card p-6 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-500">Total income</div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-xl font-semibold text-slate-900 truncate">
                        {{ formatCurrency(analytics?.totals?.income || 0) }}
                    </div>
                </div>
                <div class="pf-card p-6 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-500">Total expenses</div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-xl font-semibold text-slate-900 truncate">
                        {{ formatCurrency(analytics?.totals?.expenses || 0) }}
                    </div>
                </div>
                <div class="pf-card p-6 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-500">Total transfers</div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-50 text-sky-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 17h16M7 10l-3-3 3-3M17 20l3-3-3-3" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-xl font-semibold text-slate-900 truncate">
                        {{ formatCurrency(analytics?.totals?.transfers || 0) }}
                    </div>
                </div>
                <div class="pf-card p-6 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-500">Opening balance</div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-xl font-semibold text-slate-900 truncate">
                        {{ formatCurrency(analytics?.totals?.opening_balance || 0) }}
                    </div>
                </div>
                <div class="pf-card p-6 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-500">Net balance</div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-xl font-semibold text-slate-900 truncate">
                        {{ formatCurrency(analytics?.totals?.net || 0) }}
                    </div>
                </div>
                <div class="pf-card p-6 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-500">Net incl. opening balance</div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-50 text-teal-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2 text-xl font-semibold text-slate-900 truncate">
                        {{ formatCurrency((analytics?.totals?.net || 0) + (analytics?.totals?.opening_balance || 0)) }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="pf-card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">
                        Cash Flow Trend
                    </h3>
                    <div class="h-64">
                        <Line :data="cashFlowChart" :options="chartOptions" />
                    </div>
                </div>

                <div class="pf-card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">
                        Income vs Expenses
                    </h3>
                    <div class="h-64">
                        <Bar :data="incomeExpenseChart" :options="barOptions" />
                    </div>
                </div>
            </div>

            <div class="pf-card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">
                    Transfers Over Time
                </h3>
                <div class="h-64">
                    <Line :data="transferChart" :options="chartOptions" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="pf-card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">
                        Top Categories
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="category in analytics?.top_categories || []"
                            :key="category.category"
                            class="flex items-center justify-between rounded-xl border border-slate-200/70 bg-white/70 px-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: category.color || '#94a3b8' }"
                                ></span>
                                <span class="text-sm text-slate-700">
                                    {{ category.category }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-slate-900">
                                {{ formatCurrency(category.total) }}
                            </span>
                        </div>
                        <div
                            v-if="!analytics?.top_categories || analytics.top_categories.length === 0"
                            class="text-sm text-slate-500"
                        >
                            No category data for this range.
                        </div>
                    </div>
                </div>

                <div class="pf-card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">
                        Top Subcategories
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="subcategory in analytics?.top_subcategories || []"
                            :key="subcategory.label || subcategory.subcategory"
                            class="flex items-center justify-between rounded-xl border border-slate-200/70 bg-white/70 px-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: subcategory.color || '#94a3b8' }"
                                ></span>
                                <span class="text-sm text-slate-700">
                                    {{ subcategory.label || subcategory.subcategory }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-slate-900">
                                {{ formatCurrency(subcategory.total) }}
                            </span>
                        </div>
                        <div
                            v-if="!analytics?.top_subcategories || analytics.top_subcategories.length === 0"
                            class="text-sm text-slate-500"
                        >
                            No subcategory data for this range.
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="pf-card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">
                        Category Mix by Month
                    </h3>
                    <div class="h-64">
                        <Bar :data="categoryMixChart" :options="barOptions" />
                    </div>
                </div>

                <div class="pf-card p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">
                        Subcategory Mix by Month
                    </h3>
                    <div class="h-64">
                        <Bar :data="subcategoryMixChart" :options="barOptions" />
                    </div>
                </div>
            </div>

            <div class="pf-card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">
                    Expense Share Over Time
                </h3>
                <div class="h-64">
                    <Bar :data="expenseShareChart" :options="shareOptions" />
                </div>
            </div>

            <div class="pf-card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">
                    Median vs Actual Expenses
                </h3>
                <div class="h-64">
                    <Bar :data="medianExpenseChart" :options="chartOptions" />
                </div>
            </div>

            <div class="pf-card p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">
                    Monthly Spend Heatmap
                </h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div
                        v-for="month in analytics?.expense_by_month || []"
                        :key="month.month"
                        class="rounded-xl border border-slate-200/70 px-3 py-4 text-sm text-slate-700"
                        :style="{
                            backgroundColor: `rgba(14, 165, 233, ${Math.min(0.15 + (month.total / (analytics?.totals?.expenses || 1)) * 1.5, 0.35)})`,
                        }"
                    >
                        <div class="text-xs text-slate-500">{{ month.month }}</div>
                        <div class="mt-2 font-medium">
                            {{ formatCurrency(month.total) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

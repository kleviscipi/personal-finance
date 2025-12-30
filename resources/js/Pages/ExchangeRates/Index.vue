<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    baseCurrency: String,
    syncBase: String,
    symbols: Array,
    syncSource: String,
    supportedCurrencies: Array,
    latestDate: String,
    rangeStart: String,
    rates: Array,
});

const settingsForm = useForm({
    sync_base: props.syncBase || props.baseCurrency || 'ALL',
    sync_symbols: props.symbols?.length ? props.symbols.join(', ') : '',
});

const syncForm = useForm({
    date: new Date().toISOString().slice(0, 10),
});

const submitSync = () => {
    syncForm.post(route('exchange-rates.sync'), {
        preserveScroll: true,
    });
};

const submitSettings = () => {
    settingsForm.patch(route('exchange-rates.settings'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Exchange Rates" />

        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Exchange Rates</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Manage base currency conversions for analytics and budgets.
                    </p>
                </div>
                <Link
                    :href="route('settings')"
                    class="text-sm font-medium text-slate-600 hover:text-slate-900"
                >
                    Back to settings
                </Link>
            </div>

            <div class="pf-card p-6 space-y-4">
                <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                    <div>
                        <span class="text-slate-500">Base currency</span>
                        <span class="ml-2 font-semibold text-slate-900">{{ baseCurrency }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Sync base</span>
                        <span class="ml-2 font-semibold text-slate-900">{{ syncBase }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Symbols</span>
                        <span class="ml-2 font-semibold text-slate-900">
                            {{ symbols?.length ? symbols.join(', ') : '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500">Latest rate date</span>
                        <span class="ml-2 font-semibold text-slate-900">
                            {{ latestDate || 'No rates yet' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500">Showing</span>
                        <span class="ml-2 font-semibold text-slate-900">
                            Last 10 days (since {{ rangeStart }})
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500">Sync source</span>
                        <span class="ml-2 font-semibold text-slate-900">
                            {{ syncSource === 'account' ? 'Account settings' : 'Environment defaults' }}
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitSettings" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="sync_base" value="Sync base" />
                        <select
                            id="sync_base"
                            v-model="settingsForm.sync_base"
                            class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="code in supportedCurrencies" :key="code" :value="code">
                                {{ code }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="sync_symbols" value="Sync symbols (comma separated)" />
                        <TextInput
                            id="sync_symbols"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="settingsForm.sync_symbols"
                            placeholder="USD, EUR"
                        />
                    </div>
                    <div class="sm:col-span-3 flex items-center gap-3">
                        <PrimaryButton
                            class="w-full justify-center sm:w-auto"
                            :class="{ 'opacity-25': settingsForm.processing }"
                            :disabled="settingsForm.processing"
                        >
                            Save Sync Settings
                        </PrimaryButton>
                        <span v-if="settingsForm.recentlySuccessful" class="text-xs text-emerald-600">
                            Saved.
                        </span>
                    </div>
                </form>

                <form @submit.prevent="submitSync" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div>
                        <InputLabel for="date" value="Sync date" />
                        <TextInput
                            id="date"
                            type="date"
                            class="mt-1 block"
                            v-model="syncForm.date"
                        />
                    </div>
                    <PrimaryButton
                        class="w-full justify-center sm:w-auto"
                        :class="{ 'opacity-25': syncForm.processing }"
                        :disabled="syncForm.processing"
                    >
                        Sync Rates
                    </PrimaryButton>
                    <span v-if="syncForm.recentlySuccessful" class="text-xs text-emerald-600">
                        Synced.
                    </span>
                </form>
            </div>

            <div class="pf-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200/60">
                    <h3 class="text-lg font-semibold text-slate-900">Rates (last 10 days)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Pair
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Rate
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="rate in rates" :key="`${rate.from_currency}-${rate.to_currency}`">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                    {{ rate.from_currency }} / {{ rate.to_currency }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ rate.rate }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ rate.rate_date || '—' }}
                                </td>
                            </tr>
                            <tr v-if="!rates || rates.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-sm text-slate-500">
                                    No exchange rates synced yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

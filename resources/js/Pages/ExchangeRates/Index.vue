<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    exchangeRates: Object,
    currencies: Object,
    filters: Object,
});

const currencyOptions = Object.values(props.currencies || {});

const showAddModal = ref(false);

const form = useForm({
    base_currency: '',
    target_currency: '',
    rate: '',
    date: new Date().toISOString().slice(0, 10),
    source: 'manual',
});

const filterForm = useForm({
    base_currency: props.filters?.base_currency || '',
    target_currency: props.filters?.target_currency || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    filterForm.get(route('exchange-rates.index'), {
        preserveState: true,
    });
};

const submit = () => {
    form.post(route('exchange-rates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddModal.value = false;
        },
    });
};

const deleteRate = (id) => {
    if (confirm('Are you sure you want to delete this exchange rate?')) {
        useForm({}).delete(route('exchange-rates.destroy', id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Exchange Rates" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Exchange Rates
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Manage currency exchange rates for accurate multi-currency tracking.
                    </p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Exchange Rate
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="pf-card p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Base Currency</label>
                        <select
                            v-model="filterForm.base_currency"
                            @change="applyFilters"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        >
                            <option value="">All Currencies</option>
                            <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                                {{ currency.code }} - {{ currency.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Target Currency</label>
                        <select
                            v-model="filterForm.target_currency"
                            @change="applyFilters"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        >
                            <option value="">All Currencies</option>
                            <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                                {{ currency.code }} - {{ currency.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <input
                            type="date"
                            v-model="filterForm.date_from"
                            @change="applyFilters"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <input
                            type="date"
                            v-model="filterForm.date_to"
                            @change="applyFilters"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Exchange Rates Table -->
            <div class="pf-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    From
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    To
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rate
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Source
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="rate in exchangeRates.data" :key="rate.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(rate.date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ rate.base_currency }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ rate.target_currency }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ parseFloat(rate.rate).toFixed(8) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ rate.source }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        @click="deleteRate(rate.id)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!exchangeRates.data || exchangeRates.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No exchange rates found. Add your first exchange rate to enable multi-currency support.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="exchangeRates.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="exchangeRates.prev_page_url"
                                :href="exchangeRates.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="exchangeRates.next_page_url"
                                :href="exchangeRates.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ exchangeRates.from || 0 }}</span>
                                    to
                                    <span class="font-medium">{{ exchangeRates.to || 0 }}</span>
                                    of
                                    <span class="font-medium">{{ exchangeRates.total || 0 }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link
                                        v-for="(link, index) in exchangeRates.links"
                                        :key="index"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            link.active
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === exchangeRates.links.length - 1 ? 'rounded-r-md' : '',
                                        ]"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Exchange Rate Modal -->
        <div v-if="showAddModal" class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submit">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Exchange Rate</h3>

                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="base_currency" value="Base Currency" />
                                    <select
                                        id="base_currency"
                                        v-model="form.base_currency"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">Select base currency</option>
                                        <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                                            {{ currency.code }} - {{ currency.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.base_currency" />
                                </div>

                                <div>
                                    <InputLabel for="target_currency" value="Target Currency" />
                                    <select
                                        id="target_currency"
                                        v-model="form.target_currency"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">Select target currency</option>
                                        <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                                            {{ currency.code }} - {{ currency.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.target_currency" />
                                </div>

                                <div>
                                    <InputLabel for="rate" value="Exchange Rate" />
                                    <TextInput
                                        id="rate"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.rate"
                                        required
                                        placeholder="e.g., 0.92"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        How many {{ form.target_currency || 'target' }} per 1 {{ form.base_currency || 'base' }}
                                    </p>
                                    <InputError class="mt-2" :message="form.errors.rate" />
                                </div>

                                <div>
                                    <InputLabel for="date" value="Date" />
                                    <TextInput
                                        id="date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="form.date"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.date" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <PrimaryButton
                                type="submit"
                                class="w-full sm:w-auto sm:ml-3"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Add Exchange Rate
                            </PrimaryButton>
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

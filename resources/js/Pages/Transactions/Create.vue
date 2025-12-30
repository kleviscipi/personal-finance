<script setup>
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    categories: Array,
    currencies: Object,
});

const currencyOptions = computed(() => {
    const currencies = Object.values(props.currencies || {});
    if (!currencies.length) {
        const fallback = props.currentAccount?.base_currency || 'USD';
        return [{ code: fallback, label: fallback }];
    }

    return currencies
        .map((currency) => ({
            code: currency.code,
            label: `${currency.code} - ${currency.name}`,
        }))
        .sort((a, b) => a.code.localeCompare(b.code));
});

const form = useForm({
    type: 'expense',
    amount: '',
    currency: props.currentAccount?.base_currency || 'USD',
    date: new Date().toISOString().slice(0, 10),
    category_id: '',
    subcategory_id: '',
    description: '',
    payment_method: 'cash',
});

const paymentOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'mobile_wallet', label: 'Mobile wallet' },
    { value: 'opening_balance', label: 'Opening balance' },
    { value: 'other', label: 'Other' },
];

const availableCategories = computed(() => {
    if (!props.categories) {
        return [];
    }

    if (!form.type || form.type === 'transfer') {
        return props.categories;
    }

    return props.categories.filter((category) => category.type === form.type);
});

const selectedCategory = computed(() => {
    const categoryId = Number(form.category_id || 0);
    return props.categories?.find((category) => category.id === categoryId);
});

const availableSubcategories = computed(() => {
    return selectedCategory.value?.subcategories || [];
});

watch(
    () => form.category_id,
    () => {
        if (!availableSubcategories.value.length) {
            form.subcategory_id = '';
            return;
        }

        const subcategoryId = Number(form.subcategory_id || 0);
        const exists = availableSubcategories.value.some(
            (subcategory) => subcategory.id === subcategoryId,
        );
        if (!exists) {
            form.subcategory_id = '';
        }
    },
);

const submit = () => {
    form.post(route('transactions.store'));
};

const formatAmountInput = () => {
    if (form.amount === null || form.amount === undefined || form.amount === '') {
        return;
    }

    const cleaned = form.amount
        .toString()
        .replace(/,/g, '.')
        .replace(/[^0-9.]/g, '');

    if (!cleaned) {
        form.amount = '';
        return;
    }

    const numeric = parseFloat(cleaned);
    if (Number.isNaN(numeric)) {
        return;
    }

    form.amount = numeric.toFixed(2);
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Add Transaction" />

        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Add Transaction
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Record income, expenses, or transfers with clear context.
                    </p>
                </div>
                <Link
                    :href="route('transactions.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back to transactions
                </Link>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-8">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-2">
                            <InputLabel for="type" value="Type" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                                <option value="transfer">Transfer</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <div class="lg:col-span-2">
                            <InputLabel for="amount" value="Amount" />
                            <TextInput
                                id="amount"
                                type="text"
                                inputmode="decimal"
                                class="mt-1 block w-full"
                                v-model="form.amount"
                                required
                                @blur="formatAmountInput"
                            />
                            <InputError class="mt-2" :message="form.errors.amount" />
                        </div>

                        <div class="lg:col-span-2">
                            <InputLabel for="currency" value="Currency" />
                            <select
                                id="currency"
                                v-model="form.currency"
                                class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="currency in currencyOptions"
                                    :key="currency.code"
                                    :value="currency.code"
                                >
                                    {{ currency.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.currency" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-2">
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

                        <div class="lg:col-span-2">
                            <InputLabel for="payment_method" value="Payment method" />
                            <select
                                id="payment_method"
                                v-model="form.payment_method"
                                class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="option in paymentOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.payment_method"
                            />
                        </div>

                        <div class="lg:col-span-2">
                            <InputLabel for="description" value="Description" />
                            <TextInput
                                id="description"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.description"
                                placeholder="Optional notes"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.description"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-3">
                            <InputLabel for="category_id" value="Category" />
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select a category</option>
                                <option
                                    v-for="category in availableCategories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.category_id"
                            />
                        </div>

                        <div class="lg:col-span-3">
                            <InputLabel for="subcategory_id" value="Subcategory" />
                            <select
                                id="subcategory_id"
                                v-model="form.subcategory_id"
                                class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="!availableSubcategories.length"
                            >
                                <option value="">Select a subcategory</option>
                                <option
                                    v-for="subcategory in availableSubcategories"
                                    :key="subcategory.id"
                                    :value="subcategory.id"
                                >
                                    {{ subcategory.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.subcategory_id"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="route('transactions.index')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton
                        class="w-full justify-center sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Create Transaction
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

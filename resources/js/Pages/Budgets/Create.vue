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
});

const currencyOptions = [
    { code: 'USD', label: 'USD - US Dollar' },
    { code: 'EUR', label: 'EUR - Euro' },
    { code: 'ALL', label: 'ALL - Albanian Lek' },
];

const form = useForm({
    category_id: '',
    subcategory_id: '',
    amount: '',
    currency: props.currentAccount?.base_currency || 'USD',
    period: 'monthly',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
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
    form.post(route('budgets.store'));
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
        <Head title="Create Budget" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Create Budget
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Define a spending target for the selected period.
                    </p>
                </div>
                <Link
                    :href="route('budgets.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back to budgets
                </Link>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="category_id" value="Category" />
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All categories</option>
                                <option
                                    v-for="category in categories"
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

                        <div>
                            <InputLabel for="subcategory_id" value="Subcategory" />
                            <select
                                id="subcategory_id"
                                v-model="form.subcategory_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="!availableSubcategories.length"
                            >
                                <option value="">All subcategories</option>
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

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
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

                        <div>
                            <InputLabel for="currency" value="Currency" />
                            <select
                                id="currency"
                                v-model="form.currency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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

                        <div>
                            <InputLabel for="period" value="Period" />
                            <select
                                id="period"
                                v-model="form.period"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.period" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="start_date" value="Start date" />
                            <TextInput
                                id="start_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.start_date"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.start_date"
                            />
                        </div>

                        <div>
                            <InputLabel for="end_date" value="End date" />
                            <TextInput
                                id="end_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.end_date"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.end_date"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Link
                        :href="route('budgets.index')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Create Budget
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

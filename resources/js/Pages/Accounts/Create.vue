<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    currencies: Object,
});

const currencyOptions = Object.values(props.currencies || {});

const form = useForm({
    name: '',
    base_currency: currencyOptions[0]?.code || 'USD',
    description: '',
    opening_balance: '',
    opening_balance_date: new Date().toISOString().slice(0, 10),
});

const submit = () => {
    form.post(route('accounts.store'));
};

const formatAmountInput = () => {
    if (form.opening_balance === null || form.opening_balance === undefined || form.opening_balance === '') {
        return;
    }

    const cleaned = form.opening_balance
        .toString()
        .replace(/,/g, '.')
        .replace(/[^0-9.]/g, '');

    if (!cleaned) {
        form.opening_balance = '';
        return;
    }

    const numeric = parseFloat(cleaned);
    if (Number.isNaN(numeric)) {
        return;
    }

    form.opening_balance = numeric.toFixed(2);
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Create Account" />

        <div class="max-w-2xl mx-auto">
            <div class="pf-card">
                <div class="px-6 py-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">
                        Create your account
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Set up your primary finance account to start tracking
                        budgets and transactions.
                    </p>
                </div>

                <form @submit.prevent="submit" class="px-6 py-6 space-y-6">
                    <div>
                        <InputLabel for="name" value="Account name" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="organization"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="base_currency" value="Base currency" />
                        <select
                            id="base_currency"
                            v-model="form.base_currency"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option
                                v-for="currency in currencyOptions"
                                :key="currency.code"
                                :value="currency.code"
                            >
                                {{ currency.name }} ({{ currency.code }})
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.base_currency"
                        />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional notes about this account"
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Opening balance
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Record your starting balance to keep reports accurate.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="opening_balance" value="Amount" />
                            <TextInput
                                id="opening_balance"
                                type="text"
                                inputmode="decimal"
                                class="mt-1 block w-full"
                                v-model="form.opening_balance"
                                @blur="formatAmountInput"
                                placeholder="0.00"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.opening_balance"
                            />
                        </div>

                        <div>
                            <InputLabel for="opening_balance_date" value="Date" />
                            <TextInput
                                id="opening_balance_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.opening_balance_date"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.opening_balance_date"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end">
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Create Account
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

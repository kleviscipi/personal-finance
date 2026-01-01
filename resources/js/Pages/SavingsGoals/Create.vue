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
    accountUsers: Array,
    currentUserId: Number,
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
    name: '',
    target_amount: '',
    initial_amount: '0.00',
    currency: props.currentAccount?.base_currency || 'USD',
    tracking_mode: 'net_savings',
    category_id: '',
    subcategory_id: '',
    user_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    target_date: '',
});

const selectedCategory = computed(() => {
    const categoryId = Number(form.category_id || 0);
    return props.categories?.find((category) => category.id === categoryId);
});

const availableSubcategories = computed(() => {
    return selectedCategory.value?.subcategories || [];
});

watch(
    () => form.tracking_mode,
    () => {
        if (form.tracking_mode === 'category') {
            form.subcategory_id = '';
            return;
        }
        if (form.tracking_mode !== 'subcategory') {
            form.category_id = '';
            form.subcategory_id = '';
        }
    },
);

watch(
    () => form.subcategory_id,
    (value) => {
        if (value) {
            form.tracking_mode = 'subcategory';
        }
    },
);

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
    form.post(route('savings-goals.store'));
};

const formatUserLabel = (user) => {
    if (!user) {
        return '';
    }
    if (props.currentUserId && user.id === props.currentUserId) {
        return `You (${user.name || user.email || user.id})`;
    }
    return user.name || user.email || `User ${user.id}`;
};

const formatAmountInput = (field) => {
    const value = form[field];
    if (value === null || value === undefined || value === '') {
        return;
    }

    const cleaned = value
        .toString()
        .replace(/,/g, '.')
        .replace(/[^0-9.]/g, '');

    if (!cleaned) {
        form[field] = '';
        return;
    }

    const numeric = parseFloat(cleaned);
    if (Number.isNaN(numeric)) {
        return;
    }

    form[field] = numeric.toFixed(2);
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Create Savings Goal" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create Savings Goal</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Set a target and track your progress with projections.
                    </p>
                </div>
                <Link
                    :href="route('savings-goals.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back to goals
                </Link>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="user_id" value="Applies to" />
                            <select
                                id="user_id"
                                v-model="form.user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Account (all users)</option>
                                <option
                                    v-for="user in accountUsers || []"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ formatUserLabel(user) }}
                                </option>
                            </select>
                            <p class="mt-2 text-xs text-gray-500">
                                Account-wide goals track everyone. Personal goals track a single user.
                            </p>
                            <InputError class="mt-2" :message="form.errors.user_id" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="name" value="Goal name" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            placeholder="Emergency fund"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <InputLabel for="target_amount" value="Target amount" />
                            <TextInput
                                id="target_amount"
                                type="text"
                                inputmode="decimal"
                                class="mt-1 block w-full"
                                v-model="form.target_amount"
                                required
                                @blur="formatAmountInput('target_amount')"
                            />
                            <InputError class="mt-2" :message="form.errors.target_amount" />
                        </div>
                        <div>
                            <InputLabel for="initial_amount" value="Starting amount" />
                            <TextInput
                                id="initial_amount"
                                type="text"
                                inputmode="decimal"
                                class="mt-1 block w-full"
                                v-model="form.initial_amount"
                                @blur="formatAmountInput('initial_amount')"
                            />
                            <InputError class="mt-2" :message="form.errors.initial_amount" />
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
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <InputLabel for="tracking_mode" value="Tracking mode" />
                            <select
                                id="tracking_mode"
                                v-model="form.tracking_mode"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="net_savings">Net savings (income - expenses)</option>
                                <option value="category">Category spending</option>
                                <option value="subcategory">Subcategory spending</option>
                                <option value="manual">Manual only</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.tracking_mode" />
                        </div>
                        <div>
                            <InputLabel for="category_id" value="Category" />
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                :disabled="form.tracking_mode !== 'category' && form.tracking_mode !== 'subcategory'"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                            >
                                <option value="">Select a category</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.category_id" />
                        </div>
                        <div>
                            <InputLabel for="subcategory_id" value="Subcategory" />
                            <select
                                id="subcategory_id"
                                v-model="form.subcategory_id"
                                :disabled="!availableSubcategories.length"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
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
                            <InputError class="mt-2" :message="form.errors.subcategory_id" />
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
                            <InputError class="mt-2" :message="form.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="target_date" value="Target date" />
                            <TextInput
                                id="target_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.target_date"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.target_date" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Link
                        :href="route('savings-goals.index')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Create Goal
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

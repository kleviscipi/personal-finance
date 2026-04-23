<script setup>
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TagInput from '@/Components/TagInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    recurringTransaction: Object,
    categories: Array,
    currencies: Object,
    tags: Array,
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

const normalizeAmount = (value) => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const numeric = parseFloat(value.toString().replace(/,/g, '.'));
    if (Number.isNaN(numeric)) {
        return '';
    }

    return numeric.toFixed(2);
};

const normalizeDate = (value) => {
    if (!value) {
        return '';
    }

    return value.toString().includes('T') ? value.toString().slice(0, 10) : value.toString();
};

const form = useForm({
    type: props.recurringTransaction?.type || 'expense',
    amount: normalizeAmount(props.recurringTransaction?.amount),
    currency: props.recurringTransaction?.currency || props.currentAccount?.base_currency || 'USD',
    frequency: props.recurringTransaction?.frequency || 'monthly',
    interval: props.recurringTransaction?.interval || 1,
    next_run_date: normalizeDate(props.recurringTransaction?.next_run_date),
    end_date: normalizeDate(props.recurringTransaction?.end_date),
    category_id: props.recurringTransaction?.category_id || '',
    subcategory_id: props.recurringTransaction?.subcategory_id || '',
    description: props.recurringTransaction?.description || '',
    payment_method: props.recurringTransaction?.payment_method || 'card',
    is_active: !!props.recurringTransaction?.is_active,
    tag_ids: props.recurringTransaction?.tags?.map((tag) => tag.id) || [],
    tag_names: '',
    tag_list: (props.recurringTransaction?.tags || []).map((tag) => tag.name),
});

const paymentOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'mobile_wallet', label: 'Mobile wallet' },
    { value: 'other', label: 'Other' },
];

const frequencyOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
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

const availableSubcategories = computed(() => selectedCategory.value?.subcategories || []);

watch(
    () => form.type,
    () => {
        const categoryId = Number(form.category_id || 0);
        if (!categoryId) {
            return;
        }

        const stillValid = availableCategories.value.some((category) => category.id === categoryId);
        if (!stillValid) {
            form.category_id = '';
            form.subcategory_id = '';
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
        const exists = availableSubcategories.value.some((subcategory) => subcategory.id === subcategoryId);
        if (!exists) {
            form.subcategory_id = '';
        }
    },
);

const parseTagList = (values, tags) => {
    const normalized = (values || [])
        .map((name) => (name || '').toString().trim())
        .filter((name) => name);

    const tagMap = new Map((tags || []).map((tag) => [tag.name.toLowerCase(), tag]));
    const tagIds = [];
    const tagNames = [];

    normalized.forEach((name) => {
        const match = tagMap.get(name.toLowerCase());
        if (match) {
            tagIds.push(match.id);
        } else {
            tagNames.push(name);
        }
    });

    return {
        tagIds: Array.from(new Set(tagIds)),
        tagNames: Array.from(new Set(tagNames)),
        tagList: Array.from(new Set(normalized)),
    };
};

const formatAmountInput = () => {
    if (form.amount === null || form.amount === undefined || form.amount === '') {
        return;
    }

    const cleaned = form.amount.toString().replace(/,/g, '.').replace(/[^0-9.]/g, '');
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

const submit = () => {
    const { tagIds, tagNames, tagList } = parseTagList(form.tag_list, props.tags || []);
    form.tag_ids = tagIds;
    form.tag_names = tagNames.join(', ');
    form.tag_list = tagList;
    form.put(route('recurring-transactions.update', props.recurringTransaction.id));
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Edit Recurring Transaction" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Edit Recurring Transaction
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Update the schedule or the transaction details for future generated entries.
                    </p>
                </div>
                <Link
                    :href="route('recurring-transactions.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back to recurring
                </Link>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-8">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        Last generated: {{ recurringTransaction.last_generated_at ? new Date(recurringTransaction.last_generated_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Never' }}
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-2">
                            <InputLabel for="type" value="Type" />
                            <select id="type" v-model="form.type" class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500">
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
                            <select id="currency" v-model="form.currency" class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                                    {{ currency.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.currency" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-2">
                            <InputLabel for="frequency" value="Frequency" />
                            <select id="frequency" v-model="form.frequency" class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="option in frequencyOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.frequency" />
                        </div>

                        <div class="lg:col-span-1">
                            <InputLabel for="interval" value="Every" />
                            <TextInput
                                id="interval"
                                type="number"
                                min="1"
                                max="365"
                                class="mt-1 block w-full"
                                v-model="form.interval"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.interval" />
                        </div>

                        <div class="lg:col-span-2">
                            <InputLabel for="next_run_date" value="Next run date" />
                            <TextInput
                                id="next_run_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.next_run_date"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.next_run_date" />
                        </div>

                        <div class="lg:col-span-1">
                            <InputLabel for="end_date" value="End date" />
                            <TextInput
                                id="end_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.end_date"
                            />
                            <InputError class="mt-2" :message="form.errors.end_date" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-2">
                            <InputLabel for="payment_method" value="Payment method" />
                            <select id="payment_method" v-model="form.payment_method" class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="option in paymentOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.payment_method" />
                        </div>

                        <div class="lg:col-span-4">
                            <InputLabel for="description" value="Description" />
                            <TextInput
                                id="description"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.description"
                                placeholder="Optional notes for generated transactions"
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-3">
                            <InputLabel for="category_id" value="Category" />
                            <select id="category_id" v-model="form.category_id" class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a category</option>
                                <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.category_id" />
                        </div>

                        <div class="lg:col-span-3">
                            <InputLabel for="subcategory_id" value="Subcategory" />
                            <select id="subcategory_id" v-model="form.subcategory_id" class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500" :disabled="!availableSubcategories.length">
                                <option value="">Select a subcategory</option>
                                <option v-for="subcategory in availableSubcategories" :key="subcategory.id" :value="subcategory.id">
                                    {{ subcategory.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.subcategory_id" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-5">
                            <InputLabel for="tag_list" value="Tags" />
                            <TagInput
                                v-model="form.tag_list"
                                :suggestions="tags"
                                placeholder="Type and press Enter to add tags"
                            />
                            <p class="mt-2 text-xs text-gray-500">
                                Tags will be copied onto every generated transaction.
                            </p>
                            <InputError class="mt-2" :message="form.errors.tag_ids || form.errors.tag_names" />
                        </div>

                        <div class="lg:col-span-1">
                            <InputLabel for="is_active" value="Status" />
                            <label class="mt-3 flex items-center gap-3 text-sm text-slate-700">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                Active
                            </label>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Link
                        :href="route('recurring-transactions.index')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

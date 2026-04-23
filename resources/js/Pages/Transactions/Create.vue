<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
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
    categories: Array,
    currencies: Object,
    tags: Array,
    invoiceScanEnabled: Boolean,
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
    tag_ids: [],
    tag_names: '',
    tag_list: [],
});

const scanFile = ref(null);
const scanError = ref('');
const scanResult = ref(null);
const isScanning = ref(false);

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
    const { tagIds, tagNames, tagList } = parseTagList(form.tag_list, props.tags || []);
    form.tag_ids = tagIds;
    form.tag_names = tagNames.join(', ');
    form.tag_list = tagList;
    form.post(route('transactions.store'));
};

const handleScanFileChange = (event) => {
    scanFile.value = event.target.files?.[0] || null;
    scanError.value = '';
};

const applyScannedDraft = (draft) => {
    if (draft.type) {
        form.type = draft.type;
    }
    if (draft.amount) {
        form.amount = draft.amount;
        formatAmountInput();
    }
    if (draft.currency) {
        form.currency = draft.currency;
    }
    if (draft.date) {
        form.date = draft.date;
    }
    if (draft.description) {
        form.description = draft.description;
    }
    if (draft.payment_method) {
        form.payment_method = draft.payment_method;
    }
};

const scanDocument = async () => {
    if (!scanFile.value) {
        scanError.value = 'Choose an invoice or receipt image/PDF first.';
        return;
    }

    scanError.value = '';
    scanResult.value = null;
    isScanning.value = true;

    const payload = new FormData();
    payload.append('document', scanFile.value);

    try {
        const response = await axios.post(route('transactions.scan'), payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        applyScannedDraft(response.data?.draft || {});
        scanResult.value = response.data?.document || null;
    } catch (error) {
        scanError.value = error.response?.data?.message
            || error.response?.data?.errors?.document?.[0]
            || 'Invoice scan failed. Please try again.';
    } finally {
        isScanning.value = false;
    }
};

const parseTagList = (values, tags) => {
    const normalized = (values || [])
        .map((name) => (name || '').toString().trim())
        .filter((name) => name);

    const tagMap = new Map(
        (tags || []).map((tag) => [tag.name.toLowerCase(), tag]),
    );

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

    const uniqueIds = Array.from(new Set(tagIds));
    const uniqueNames = Array.from(new Set(tagNames));
    const tagList = Array.from(new Set(normalized));

    return {
        tagIds: uniqueIds,
        tagNames: uniqueNames,
        tagList,
    };
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
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <h3 class="text-base font-semibold text-slate-900">
                                    Import from invoice or receipt
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    Upload an image or PDF and the app will prefill a draft transaction. Review every field before saving.
                                </p>
                            </div>
                            <div class="flex w-full flex-col gap-3 lg:w-auto lg:min-w-[28rem]">
                                <input
                                    id="scan_document"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf"
                                    class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white"
                                    :disabled="isScanning || !invoiceScanEnabled"
                                    @change="handleScanFileChange"
                                />
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="isScanning || !invoiceScanEnabled"
                                    @click="scanDocument"
                                >
                                    {{ isScanning ? 'Scanning document...' : 'Scan document' }}
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!invoiceScanEnabled"
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                        >
                            Invoice scan is unavailable until the AI parser is configured on the server.
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900"
                        >
                            The scan fills a draft only. Nothing is saved until you press <span class="font-semibold">Create Transaction</span>.
                        </div>

                        <div
                            v-if="scanError"
                            class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"
                        >
                            {{ scanError }}
                        </div>

                        <div
                            v-if="scanResult"
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-950"
                        >
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                <div class="font-medium">
                                    Draft applied from {{ scanResult.source_name }}
                                </div>
                                <div class="text-xs uppercase tracking-wide text-emerald-700">
                                    Confidence {{ Math.round((scanResult.confidence || 0) * 100) }}%
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-emerald-800">
                                <span class="rounded-full bg-white/70 px-2.5 py-1">
                                    {{ scanResult.kind || 'unknown document' }}
                                </span>
                                <span
                                    v-if="scanResult.merchant"
                                    class="rounded-full bg-white/70 px-2.5 py-1"
                                >
                                    {{ scanResult.merchant }}
                                </span>
                            </div>
                            <ul
                                v-if="scanResult.warnings?.length"
                                class="mt-3 space-y-1 text-xs text-emerald-900"
                            >
                                <li
                                    v-for="warning in scanResult.warnings"
                                    :key="warning"
                                >
                                    {{ warning }}
                                </li>
                            </ul>
                        </div>
                    </div>

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

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-6">
                        <div class="lg:col-span-6">
                            <InputLabel for="tag_list" value="Tags" />
                            <TagInput
                                v-model="form.tag_list"
                                :suggestions="tags"
                                placeholder="Type and press Enter to add tags"
                            />
                            <p class="mt-2 text-xs text-gray-500">
                                Start typing to see suggestions. Press Enter or comma to add new tags.
                            </p>
                            <InputError class="mt-2" :message="form.errors.tag_ids || form.errors.tag_names" />
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

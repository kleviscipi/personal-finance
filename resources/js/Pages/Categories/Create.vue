<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    auth: Object,
    currentAccount: Object,
});

const form = useForm({
    name: '',
    type: 'expense',
    icon: '',
    color: '#4f46e5',
});

const submit = () => {
    form.post(route('categories.store'));
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Create Category" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Create Category
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Define a category to organize transactions.
                    </p>
                </div>
                <Link
                    :href="route('categories.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    Back to categories
                </Link>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-6">
                    <div>
                        <InputLabel for="name" value="Category name" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="type" value="Type" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.type" />
                        </div>

                        <div>
                            <InputLabel for="icon" value="Icon" />
                        <TextInput
                            id="icon"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.icon"
                            placeholder="$"
                        />
                            <InputError class="mt-2" :message="form.errors.icon" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="color" value="Color" />
                            <input
                                id="color"
                                type="color"
                                v-model="form.color"
                                class="mt-1 h-10 w-full rounded-md border border-gray-300"
                            />
                            <InputError class="mt-2" :message="form.errors.color" />
                        </div>

                        <div class="text-sm text-gray-600">
                            Subcategories can be added after creating the
                            category.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <Link
                        :href="route('categories.index')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Create Category
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

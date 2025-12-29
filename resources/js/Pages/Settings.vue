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
    account: Object,
    settings: Object,
    currencies: Object,
});

const currencyOptions = Object.values(props.currencies || {});

const form = useForm({
    name: props.account?.name || '',
    description: props.account?.description || '',
    base_currency: props.account?.base_currency || currencyOptions[0]?.code || 'USD',
    locale: props.settings?.locale || 'en',
    timezone: props.settings?.timezone || 'UTC',
    date_format: props.settings?.date_format || 'Y-m-d',
    time_format: props.settings?.time_format || 'H:i',
    notifications_enabled: props.settings?.notifications_enabled ?? true,
});

const submit = () => {
    form.patch(route('settings.update'));
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Settings" />

        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Settings</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Manage account preferences and formatting defaults.
                </p>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Account
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Update your workspace name and base currency.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Account name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
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
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Locale & formatting
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Set defaults for date, time, and locale.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="locale" value="Locale" />
                            <TextInput
                                id="locale"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.locale"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.locale" />
                        </div>

                        <div>
                            <InputLabel for="timezone" value="Timezone" />
                            <TextInput
                                id="timezone"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.timezone"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.timezone" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="date_format" value="Date format" />
                            <TextInput
                                id="date_format"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.date_format"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.date_format"
                            />
                        </div>

                        <div>
                            <InputLabel for="time_format" value="Time format" />
                            <TextInput
                                id="time_format"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.time_format"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.time_format"
                            />
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Notifications
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Control alerts and monthly summaries.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <input
                            id="notifications_enabled"
                            type="checkbox"
                            v-model="form.notifications_enabled"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="notifications_enabled" class="text-sm text-gray-700">
                            Enable notifications
                        </label>
                        <InputError
                            class="mt-2"
                            :message="form.errors.notifications_enabled"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Save Settings
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

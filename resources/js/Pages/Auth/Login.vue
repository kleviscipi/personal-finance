<script setup>
import { onMounted, ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    recaptchaSiteKey: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    recaptcha_token: '',
});

const recaptchaEl = ref(null);
const recaptchaWidgetId = ref(null);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            if (recaptchaWidgetId.value !== null && window.grecaptcha) {
                window.grecaptcha.reset(recaptchaWidgetId.value);
                form.recaptcha_token = '';
            }
        },
    });
};

const renderRecaptcha = () => {
    if (!props.recaptchaSiteKey || !recaptchaEl.value) {
        return;
    }

    if (!window.grecaptcha || !window.grecaptcha.render) {
        setTimeout(renderRecaptcha, 200);
        return;
    }

    recaptchaWidgetId.value = window.grecaptcha.render(recaptchaEl.value, {
        sitekey: props.recaptchaSiteKey,
        callback: (token) => {
            form.recaptcha_token = token;
        },
        'expired-callback': () => {
            form.recaptcha_token = '';
        },
    });
};

onMounted(() => {
    renderRecaptcha();
});
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-slate-600">
                        Remember me
                    </span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-sky-600 hover:text-sky-700"
                >
                    Forgot password?
                </Link>
            </div>

            <div v-if="recaptchaSiteKey" class="mt-4">
                <div ref="recaptchaEl"></div>
                <InputError class="mt-2" :message="form.errors.recaptcha_token" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Log in
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>

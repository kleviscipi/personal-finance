<script setup>
import { onMounted, ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    recaptcha_token: '',
});

const props = defineProps({
    recaptchaSiteKey: {
        type: String,
        default: null,
    },
});

const recaptchaEl = ref(null);
const recaptchaWidgetId = ref(null);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
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
        <Head title="Register" />

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
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
                Register
            </PrimaryButton>

            <div class="text-center text-sm text-slate-600">
                Already registered?
                <Link
                    :href="route('login')"
                    class="font-medium text-sky-600 hover:text-sky-700"
                >
                    Log in
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    invite: Object,
});

const page = usePage();
const isSignedIn = computed(() => !!page.props.auth?.user);

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

const acceptInvite = () => {
    form.post(route('invites.accept', props.invite.token));
};
</script>

<template>
    <GuestLayout>
        <Head title="Accept Invite" />

        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    Join {{ invite.account?.name }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    You were invited as a {{ invite.role }} to {{ invite.email }}.
                </p>
            </div>

            <div v-if="isSignedIn" class="text-sm text-gray-600">
                You're signed in as {{ page.props.auth?.user?.email }}. Confirm to
                accept this invitation.
            </div>

            <form @submit.prevent="acceptInvite" class="space-y-4">
                <div v-if="!isSignedIn">
                    <InputLabel for="name" value="Full name" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div v-if="!isSignedIn">
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

                <div v-if="!isSignedIn">
                    <InputLabel for="password_confirmation" value="Confirm password" />
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

                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Accept Invite
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>

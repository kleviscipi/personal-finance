<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    members: Array,
});

const form = useForm({
    email: '',
    role: 'member',
});

const submit = () => {
    form.post(route('family.store'), {
        onSuccess: () => form.reset('email'),
    });
};

const updateMember = (member) => {
    router.patch(route('family.update', member.id), {
        role: member.role,
        is_active: member.is_active,
    });
};

const confirmingDelete = ref(false);
const pendingMember = ref(null);

const requestRemoveMember = (member) => {
    pendingMember.value = member;
    confirmingDelete.value = true;
};

const closeDeleteModal = () => {
    confirmingDelete.value = false;
    pendingMember.value = null;
};

const confirmRemoveMember = () => {
    if (!pendingMember.value) {
        return;
    }

    router.delete(route('family.destroy', pendingMember.value.id), {
        onFinish: () => closeDeleteModal(),
    });
};

const deleteMessage = computed(() => {
    const member = pendingMember.value;
    if (!member) {
        return 'Remove this member from the account?';
    }
    return `Remove ${member.name || member.email}?`;
});
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Family & Members" />

        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Family & Members</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Invite members by email and assign a role.
                </p>
            </div>

            <form @submit.prevent="submit" class="pf-card">
                <div class="px-6 py-6 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <InputLabel for="email" value="Email address" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="role" value="Role" />
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="owner">Owner</option>
                                <option value="admin">Admin</option>
                                <option value="member">Member</option>
                                <option value="viewer">Viewer</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Add Member
                    </PrimaryButton>
                </div>
            </form>

            <div class="pf-card">
                <div class="divide-y divide-gray-200">
                    <div
                        v-if="!members || members.length === 0"
                        class="px-6 py-12 text-center text-sm text-gray-600"
                    >
                        No members yet. Add users to collaborate.
                    </div>
                    <div
                        v-for="member in members"
                        :key="member.id"
                        class="px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ member.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ member.email }}
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <select
                                v-model="member.role"
                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="updateMember(member)"
                            >
                                <option value="owner">Owner</option>
                                <option value="admin">Admin</option>
                                <option value="member">Member</option>
                                <option value="viewer">Viewer</option>
                            </select>
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input
                                    type="checkbox"
                                    v-model="member.is_active"
                                    @change="updateMember(member)"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active
                            </label>
                            <button
                                type="button"
                                class="text-sm text-red-600 hover:text-red-700"
                                @click="requestRemoveMember(member)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="confirmingDelete"
            title="Remove member?"
            :message="deleteMessage"
            confirm-text="Remove"
            @close="closeDeleteModal"
            @confirm="confirmRemoveMember"
        />
    </AppLayout>
</template>

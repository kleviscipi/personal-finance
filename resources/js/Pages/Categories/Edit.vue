<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    category: Object,
});

const form = useForm({
    name: props.category?.name || '',
    type: props.category?.type || 'expense',
    icon: props.category?.icon || '',
    color: props.category?.color || '#4f46e5',
});

const subcategoryForm = useForm({
    name: '',
});

const subcategories = ref([]);
const draggingIndex = ref(null);

const syncSubcategories = () => {
    subcategories.value = (props.category?.subcategories || []).map((subcategory) => ({
        ...subcategory,
        editName: subcategory.name,
    }));
};

syncSubcategories();

watch(
    () => props.category?.subcategories,
    () => {
        syncSubcategories();
    },
);

const submit = () => {
    form.put(route('categories.update', props.category.id));
};

const createSubcategory = () => {
    subcategoryForm.post(
        route('categories.subcategories.store', props.category.id),
        {
            onSuccess: () => subcategoryForm.reset('name'),
        },
    );
};

const updateSubcategory = (subcategory) => {
    if (!subcategory.editName) {
        return;
    }

    router.patch(
        route('categories.subcategories.update', [props.category.id, subcategory.id]),
        { name: subcategory.editName },
    );
};

const deleteSubcategory = (subcategoryId) => {
    if (confirm('Delete this subcategory?')) {
        router.delete(
            route('categories.subcategories.destroy', [props.category.id, subcategoryId]),
        );
    }
};

const onDragStart = (index) => {
    draggingIndex.value = index;
};

const onDrop = (index) => {
    if (draggingIndex.value === null) {
        return;
    }

    const updated = [...subcategories.value];
    const [moved] = updated.splice(draggingIndex.value, 1);
    updated.splice(index, 0, moved);
    subcategories.value = updated;
    draggingIndex.value = null;

    router.patch(route('categories.subcategories.reorder', props.category.id), {
        order: subcategories.value.map((subcategory) => subcategory.id),
    });
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Edit Category" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Edit Category
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Update category details and visual styling.
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
                            Subcategories are managed below.
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
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>

            <div class="pf-card">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Subcategories
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Add or rename subcategories under this category.
                    </p>
                </div>

                <div class="px-6 py-6 space-y-4">
                    <form
                        class="flex flex-col gap-3 sm:flex-row sm:items-end"
                        @submit.prevent="createSubcategory"
                    >
                        <div class="flex-1">
                            <InputLabel for="subcategory_name" value="Name" />
                            <TextInput
                                id="subcategory_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="subcategoryForm.name"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="subcategoryForm.errors.name"
                            />
                        </div>

                        <PrimaryButton
                            class="w-full justify-center sm:w-auto"
                            :class="{ 'opacity-25': subcategoryForm.processing }"
                            :disabled="subcategoryForm.processing"
                        >
                            Add Subcategory
                        </PrimaryButton>
                    </form>

                    <div class="divide-y divide-gray-200">
                        <div
                            v-if="subcategories.length === 0"
                            class="py-8 text-center text-sm text-gray-600"
                        >
                            No subcategories yet.
                        </div>
                        <div
                            v-for="(subcategory, index) in subcategories"
                            :key="subcategory.id"
                            class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between cursor-move"
                            draggable="true"
                            @dragstart="onDragStart(index)"
                            @dragover.prevent
                            @drop="onDrop(index)"
                        >
                            <div class="flex-1">
                                <TextInput
                                    :id="`subcategory-${subcategory.id}`"
                                    type="text"
                                    class="block w-full"
                                    v-model="subcategory.editName"
                                    :placeholder="subcategory.name"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                    @click="updateSubcategory(subcategory)"
                                >
                                    Save
                                </button>
                                <button
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-700"
                                    @click="deleteSubcategory(subcategory.id)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

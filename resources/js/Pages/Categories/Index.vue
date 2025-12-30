<script setup>
import { computed, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
    categories: Array,
});

const orderedCategories = ref([]);
const draggingIndex = ref(null);

const syncCategories = () => {
    orderedCategories.value = [...(props.categories || [])];
};

syncCategories();

watch(
    () => props.categories,
    () => {
        syncCategories();
    },
);

const confirmingDelete = ref(false);
const pendingCategory = ref(null);

const requestDeleteCategory = (category) => {
    pendingCategory.value = category;
    confirmingDelete.value = true;
};

const closeDeleteModal = () => {
    confirmingDelete.value = false;
    pendingCategory.value = null;
};

const confirmDeleteCategory = () => {
    if (!pendingCategory.value) {
        return;
    }

    router.delete(route('categories.destroy', pendingCategory.value.id), {
        onFinish: () => closeDeleteModal(),
    });
};

const deleteMessage = computed(() => {
    const category = pendingCategory.value;
    if (!category) {
        return 'Delete this category?';
    }
    return `Delete ${category.name}?`;
});

const onDragStart = (index) => {
    draggingIndex.value = index;
};

const onDrop = (index) => {
    if (draggingIndex.value === null) {
        return;
    }

    const updated = [...orderedCategories.value];
    const [moved] = updated.splice(draggingIndex.value, 1);
    updated.splice(index, 0, moved);
    orderedCategories.value = updated;
    draggingIndex.value = null;

    router.patch(route('categories.reorder'), {
        order: orderedCategories.value.map((category) => category.id),
    });
};
</script>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <Head title="Categories" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Categories</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Organize your transactions with custom categories.
                    </p>
                </div>
                <Link
                    :href="route('categories.create')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Add Category
                </Link>
            </div>

            <div class="pf-card">
                <div class="divide-y divide-gray-200">
                    <div
                        v-if="!orderedCategories || orderedCategories.length === 0"
                        class="px-6 py-12 text-center text-sm text-gray-600"
                    >
                        <p>No categories yet. Add a new one to get started.</p>
                        <Link
                            :href="route('categories.create')"
                            class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Add Category
                        </Link>
                    </div>
                    <div
                        v-for="(category, index) in orderedCategories"
                        :key="category.id"
                        class="px-6 py-4 flex items-center justify-between cursor-move"
                        draggable="true"
                        @dragstart="onDragStart(index)"
                        @dragover.prevent
                        @drop="onDrop(index)"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center text-lg"
                                :style="{ backgroundColor: category.color || '#e5e7eb' }"
                            >
                                {{ category.icon || 'C' }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                    <span>{{ category.name }}</span>
                                    <span
                                        v-if="category.is_system"
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600"
                                    >
                                        System
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ category.type }}
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <Link :href="route('categories.edit', category.id)" class="text-indigo-600 hover:text-indigo-900">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </Link>
                            <button @click="requestDeleteCategory(category)" class="text-red-600 hover:text-red-900">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="confirmingDelete"
            title="Delete category?"
            :message="deleteMessage"
            confirm-text="Delete"
            @close="closeDeleteModal"
            @confirm="confirmDeleteCategory"
        />
    </AppLayout>
</template>

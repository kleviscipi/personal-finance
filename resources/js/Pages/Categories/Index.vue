<script setup>
import { ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
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

const deleteCategory = (categoryId) => {
    if (confirm('Are you sure you want to delete this category?')) {
        router.delete(route('categories.destroy', categoryId));
    }
};

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
                                <div class="text-sm font-medium text-gray-900">
                                    {{ category.name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ category.type }}
                                </div>
                            </div>
                        </div>
                        <Link
                            :href="route('categories.edit', category.id)"
                            class="text-sm text-sky-600 hover:text-sky-700"
                        >
                            Edit
                        </Link>
                        <button
                            type="button"
                            class="text-sm text-red-600 hover:text-red-700"
                            @click="deleteCategory(category.id)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<template>
    <AppLayout :auth="auth" :current-account="currentAccount">
        <div class="text-center py-20">
            <h2 class="text-3xl font-bold text-gray-900">Categories</h2>
            <p class="mt-4 text-gray-600">Coming soon...</p>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    auth: Object,
    currentAccount: Object,
});
</script>

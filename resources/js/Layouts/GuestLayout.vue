<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-transparent pt-8 sm:justify-center sm:pt-0"
    >
        <div class="flex items-center gap-3 text-slate-900">
            <div class="h-10 w-10 rounded-xl bg-sky-600/10 flex items-center justify-center text-sky-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="text-lg font-semibold tracking-tight">Personal Finance</div>
                <div class="text-xs text-slate-500">Sign in to continue</div>
            </div>
        </div>

        <div
            class="mt-6 w-full overflow-hidden pf-card p-6 sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>

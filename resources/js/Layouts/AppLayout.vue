<template>
    <div class="min-h-screen text-slate-900">
        <!-- Navigation -->
        <nav class="bg-white/70 backdrop-blur-xl border-b border-slate-200/60 shadow-[0_8px_24px_-20px_rgba(15,23,42,0.4)] relative z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <div class="h-9 w-9 rounded-xl bg-sky-600/10 flex items-center justify-center text-sky-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-xl font-semibold tracking-tight text-slate-900">Personal Finance</span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link 
                                :href="route('dashboard')" 
                                :class="[route().current('dashboard') ? 'border-sky-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h4v10H3V10zm7-6h4v16h-4V4zm7 8h4v8h-4v-8z" />
                                </svg>
                                Dashboard
                            </Link>
                            <Link 
                                :href="route('transactions.index')" 
                                :class="[route().current('transactions.*') ? 'border-sky-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                                </svg>
                                Transactions
                            </Link>
                            <Link 
                                :href="route('budgets.index')" 
                                :class="[route().current('budgets.*') ? 'border-sky-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m9-6a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Budgets
                            </Link>
                            <Link 
                                :href="route('categories.index')" 
                                :class="[route().current('categories.*') ? 'border-sky-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                Categories
                            </Link>
                            <Link 
                                :href="route('statistics.index')" 
                                :class="[route().current('statistics.*') ? 'border-sky-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v18m4-6v6m4-12v12m4-8v8m4-14v14" />
                                </svg>
                                Statistics
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="ml-3 relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-3 rounded-full px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500"
                            >
                                <div class="hidden lg:block text-left leading-tight max-w-[140px]">
                                    <div class="text-sm font-medium text-gray-900 truncate">
                                        {{ auth?.user?.name || 'Account' }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ auth?.user?.email || '' }}
                                    </div>
                                </div>
                                <div class="h-8 w-8 rounded-full bg-sky-600 flex items-center justify-center text-white font-medium">
                                    {{ userInitial }}
                                </div>
                            </button>

                            <!-- User dropdown -->
                            <div v-show="showUserMenu" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-[999]">
                                <div class="px-4 py-2 text-xs text-gray-500">
                                    Signed in as
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ auth?.user?.name || '' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ auth?.user?.email || '' }}
                                    </div>
                                </div>
                                <div v-if="accounts.length" class="px-4 py-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Active account</label>
                                    <select
                                        class="pf-input block w-full text-sm"
                                        :value="activeAccountId"
                                        @change="switchAccount"
                                    >
                                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                                            {{ account.name }} ({{ account.base_currency }})
                                        </option>
                                    </select>
                                </div>
                                <Link :href="route('profile.edit')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 019 16h6a4 4 0 013.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Profile
                                </Link>
                                <Link :href="route('family.index')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Family
                                </Link>
                                <Link :href="route('settings')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7zM19.4 15a7.97 7.97 0 00.1-2l2-1.2-2-3.4-2.3.7a7.9 7.9 0 00-1.7-1l-.3-2.4H11l-.3 2.4a7.9 7.9 0 00-1.7 1l-2.3-.7-2 3.4 2 1.2a7.97 7.97 0 000 2l-2 1.2 2 3.4 2.3-.7a7.9 7.9 0 001.7 1l.3 2.4h4.4l.3-2.4a7.9 7.9 0 001.7-1l2.3.7 2-3.4-2-1.2z" />
                                    </svg>
                                    Settings
                                </Link>
                                <Link :href="route('accounts.create')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    New Account
                                </Link>
                                <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Log out
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="flex items-center sm:hidden">
                        <button
                            type="button"
                            @click="showMobileMenu = !showMobileMenu"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-sky-500"
                        >
                            <svg
                                v-if="!showMobileMenu"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg
                                v-else
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-show="showMobileMenu" class="sm:hidden border-t border-gray-200">
                <div class="space-y-1 px-4 py-4">
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h4v10H3V10zm7-6h4v16h-4V4zm7 8h4v8h-4v-8z" />
                        </svg>
                        Dashboard
                    </Link>
                    <Link
                        :href="route('transactions.index')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                        Transactions
                    </Link>
                    <Link
                        :href="route('budgets.index')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m9-6a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Budgets
                    </Link>
                    <Link
                        :href="route('categories.index')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Categories
                    </Link>
                    <Link
                        :href="route('statistics.index')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v18m4-6v6m4-12v12m4-8v8m4-14v14" />
                        </svg>
                        Statistics
                    </Link>
                    <Link
                        :href="route('family.index')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Family
                    </Link>
                    <Link
                        :href="route('settings')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7zM19.4 15a7.97 7.97 0 00.1-2l2-1.2-2-3.4-2.3.7a7.9 7.9 0 00-1.7-1l-.3-2.4H11l-.3 2.4a7.9 7.9 0 00-1.7 1l-2.3-.7-2 3.4 2 1.2a7.97 7.97 0 000 2l-2 1.2 2 3.4 2.3-.7a7.9 7.9 0 001.7 1l.3 2.4h4.4l.3-2.4a7.9 7.9 0 001.7-1l2.3.7 2-3.4-2-1.2z" />
                        </svg>
                        Settings
                    </Link>
                </div>
                <div class="border-t border-gray-200 px-4 py-4">
                    <div v-if="accounts.length" class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Account</label>
                        <select
                            class="pf-input block w-full text-sm"
                            :value="activeAccountId"
                            @change="switchAccount"
                        >
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.name }} ({{ account.base_currency }})
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-sky-600 flex items-center justify-center text-white font-medium">
                            {{ userInitial }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ auth?.user?.name || 'Account' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ auth?.user?.email || '' }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-1">
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 019 16h6a4 4 0 013.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Profile
                    </Link>
                    <Link
                        :href="route('accounts.create')"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Account
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                            class="block w-full text-left rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100"
                        >
                            Sign out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flashMessage"
                    class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    role="status"
                >
                    {{ flashMessage }}
                </div>
                <div
                    v-if="flashError"
                    class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                    role="alert"
                >
                    {{ flashError }}
                </div>
                <div class="pf-page">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    currentAccount: Object,
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message || '');
const flashError = computed(() => page.props.flash?.error || '');
const accounts = computed(() => page.props.accounts || []);
const activeAccountId = computed(() => page.props.activeAccount?.id || null);

const showUserMenu = ref(false);
const showMobileMenu = ref(false);

const userInitial = computed(() => {
    const name = props.auth?.user?.name;
    if (!name || typeof name !== 'string') {
        return '?';
    }
    return name.charAt(0).toUpperCase();
});

const switchAccount = (event) => {
    const accountId = event.target.value;
    if (!accountId || accountId === String(activeAccountId.value)) {
        return;
    }
    router.post(route('accounts.active'), { account_id: accountId }, { preserveScroll: true });
};
</script>

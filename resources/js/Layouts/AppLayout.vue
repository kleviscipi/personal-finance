<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center">
                                <svg class="h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="ml-2 text-xl font-bold text-gray-900">Personal Finance</span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link 
                                :href="route('dashboard')" 
                                :class="[route().current('dashboard') ? 'border-primary-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                Dashboard
                            </Link>
                            <Link 
                                :href="route('transactions.index')" 
                                :class="[route().current('transactions.*') ? 'border-primary-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                Transactions
                            </Link>
                            <Link 
                                :href="route('budgets.index')" 
                                :class="[route().current('budgets.*') ? 'border-primary-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                Budgets
                            </Link>
                            <Link 
                                :href="route('categories.index')" 
                                :class="[route().current('categories.*') ? 'border-primary-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium']"
                            >
                                Categories
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <button @click="showAccountSelector = !showAccountSelector" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            {{ currentAccount?.name || 'Select Account' }}
                        </button>

                        <div class="ml-3 relative">
                            <button @click="showUserMenu = !showUserMenu" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <div class="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
                                    {{ auth.user?.name?.charAt(0).toUpperCase() }}
                                </div>
                            </button>

                            <!-- User dropdown -->
                            <div v-show="showUserMenu" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5">
                                <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</Link>
                                <Link :href="route('settings')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</Link>
                                <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Sign out
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    auth: Object,
    currentAccount: Object,
});

const showUserMenu = ref(false);
const showAccountSelector = ref(false);
</script>

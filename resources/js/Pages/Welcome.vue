<template>
    <div class="min-h-screen bg-gradient-to-br from-sky-50 via-white to-sky-100">
        <!-- Navigation -->
        <nav class="bg-white/80 backdrop-blur border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <svg class="h-8 w-8 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900">Personal Finance</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link :href="route('login')" class="text-gray-700 hover:text-gray-900">Login</Link>
                        <Link :href="route('register')" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700">Get Started</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-5xl font-bold text-gray-900 sm:text-6xl md:text-7xl">
                    Take Control of Your
                    <span class="text-sky-600">Finances</span>
                </h1>
                <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto">
                    A modern, professional personal finance application designed for individuals and families. Track expenses, set budgets, and achieve your financial goals.
                </p>
                <div class="mt-10 flex justify-center space-x-4">
                    <Link :href="route('register')" class="px-8 py-3 bg-sky-600 text-white text-lg font-medium rounded-lg hover:bg-sky-700 shadow-lg">
                        Start Free Trial
                    </Link>
                    <Link :href="route('login')" class="px-8 py-3 bg-white text-gray-900 text-lg font-medium rounded-lg hover:bg-gray-50 shadow-lg border border-gray-200">
                        Sign In
                    </Link>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="bg-white p-8 rounded-xl shadow-sm">
                    <div class="h-12 w-12 bg-sky-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="h-6 w-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Track Expenses</h3>
                    <p class="text-gray-600">Monitor all your spending in one place with detailed categorization and insights.</p>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm">
                    <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Set Budgets</h3>
                    <p class="text-gray-600">Create monthly budgets and get alerts when you're close to your limits.</p>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm">
                    <div class="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Family Accounts</h3>
                    <p class="text-gray-600">Manage family finances together with role-based access control.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

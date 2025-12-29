<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Welcome Page
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Auth routes (simplified for now)
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');

// Dashboard and authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('transactions', TransactionController::class);
    Route::resource('budgets', BudgetController::class);
    Route::resource('categories', CategoryController::class);
    
    Route::get('/settings', function () {
        return Inertia::render('Settings');
    })->name('settings');
    
    Route::get('/profile/edit', function () {
        return Inertia::render('Profile/Edit');
    })->name('profile.edit');
});



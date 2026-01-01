<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavingsGoalController;
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

Route::get('/invites/{token}', [InvitationController::class, 'show'])->name('invites.show');
Route::post('/invites/{token}', [InvitationController::class, 'accept'])->name('invites.accept');

// Dashboard and authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('transactions', TransactionController::class);
    Route::resource('budgets', BudgetController::class);
    Route::resource('savings-goals', SavingsGoalController::class);
    Route::patch('/categories/reorder', [CategoryController::class, 'reorder'])
        ->name('categories.reorder');
    Route::resource('categories', CategoryController::class);
    Route::post('/categories/{category}/subcategories', [SubcategoryController::class, 'store'])
        ->name('categories.subcategories.store');
    Route::patch('/categories/{category}/subcategories/reorder', [SubcategoryController::class, 'reorder'])
        ->name('categories.subcategories.reorder');
    Route::patch('/categories/{category}/subcategories/{subcategory}', [SubcategoryController::class, 'update'])
        ->name('categories.subcategories.update');
    Route::delete('/categories/{category}/subcategories/{subcategory}', [SubcategoryController::class, 'destroy'])
        ->name('categories.subcategories.destroy');

    Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::post('/accounts/active', [AccountController::class, 'setActive'])->name('accounts.active');
    
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('/exchange-rates/sync', [ExchangeRateController::class, 'sync'])->name('exchange-rates.sync');
    Route::patch('/exchange-rates/settings', [ExchangeRateController::class, 'updateSettings'])
        ->name('exchange-rates.settings');

    Route::get('/family', [FamilyController::class, 'index'])->name('family.index');
    Route::post('/family', [FamilyController::class, 'store'])->name('family.store');
    Route::patch('/family/{user}', [FamilyController::class, 'update'])->name('family.update');
    Route::delete('/family/{user}', [FamilyController::class, 'destroy'])->name('family.destroy');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';

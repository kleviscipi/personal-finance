<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\SavingsGoalController;
use App\Http\Controllers\Api\V1\StatisticsController;
use App\Http\Controllers\Api\V1\SubcategoryController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('meta/currencies', [MetaController::class, 'currencies']);

        Route::get('dashboard', [DashboardController::class, 'show']);
        Route::get('statistics', [StatisticsController::class, 'index']);

        Route::get('accounts', [AccountController::class, 'index']);
        Route::post('accounts', [AccountController::class, 'store']);
        Route::get('accounts/{accountId}', [AccountController::class, 'show']);
        Route::patch('accounts/{accountId}', [AccountController::class, 'update']);
        Route::delete('accounts/{accountId}', [AccountController::class, 'destroy']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::get('categories/{categoryId}', [CategoryController::class, 'show']);
        Route::patch('categories/{categoryId}', [CategoryController::class, 'update']);
        Route::delete('categories/{categoryId}', [CategoryController::class, 'destroy']);
        Route::post('categories/{categoryId}/subcategories', [SubcategoryController::class, 'store']);
        Route::patch('categories/{categoryId}/subcategories/{subcategoryId}', [SubcategoryController::class, 'update']);
        Route::delete('categories/{categoryId}/subcategories/{subcategoryId}', [SubcategoryController::class, 'destroy']);

        Route::get('transactions', [TransactionController::class, 'index']);
        Route::post('transactions', [TransactionController::class, 'store']);
        Route::get('transactions/{transactionId}', [TransactionController::class, 'show']);
        Route::patch('transactions/{transactionId}', [TransactionController::class, 'update']);
        Route::delete('transactions/{transactionId}', [TransactionController::class, 'destroy']);

        Route::get('budgets', [BudgetController::class, 'index']);
        Route::post('budgets', [BudgetController::class, 'store']);
        Route::get('budgets/{budgetId}', [BudgetController::class, 'show']);
        Route::patch('budgets/{budgetId}', [BudgetController::class, 'update']);
        Route::delete('budgets/{budgetId}', [BudgetController::class, 'destroy']);

        Route::get('savings-goals', [SavingsGoalController::class, 'index']);
        Route::post('savings-goals', [SavingsGoalController::class, 'store']);
        Route::get('savings-goals/{savingsGoalId}', [SavingsGoalController::class, 'show']);
        Route::patch('savings-goals/{savingsGoalId}', [SavingsGoalController::class, 'update']);
        Route::delete('savings-goals/{savingsGoalId}', [SavingsGoalController::class, 'destroy']);
    });
});

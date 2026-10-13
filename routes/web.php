<?php

use App\Http\Controllers\Admin\TteRecordController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/users', [TteRecordController::class, 'index'])->defaults('type', 'all')->name('records.index');
    Route::get('/akan-expired', [TteRecordController::class, 'index'])->defaults('type', 'upcoming')->name('records.upcoming');
    Route::get('/sudah-expired', [TteRecordController::class, 'index'])->defaults('type', 'expired')->name('records.expired');
});

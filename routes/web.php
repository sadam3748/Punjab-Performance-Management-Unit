<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiCardController;
use App\Http\Controllers\KpiDashboardController;
use App\Http\Controllers\KpiSubmissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/kpi/{kpiCard}/dashboard', [KpiDashboardController::class, 'show'])->name('kpi.dashboard');
    Route::get('/kpi/{kpiCard}/dashboard/data', [KpiDashboardController::class, 'data'])->name('kpi.dashboard.data');

    Route::get('/kpi-submissions', [KpiSubmissionController::class, 'review'])->name('kpi-submissions.index');
    Route::get('/submit-kpi/{kpiCard}', [KpiSubmissionController::class, 'create'])->name('kpi-submissions.create');
    Route::post('/submit-kpi/{kpiCard}', [KpiSubmissionController::class, 'store'])->name('kpi-submissions.store');
    Route::patch('/submissions/{submission}/status', [KpiSubmissionController::class, 'updateStatus'])->name('submissions.status');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    Route::get('/manage-kpis', [KpiCardController::class, 'index'])->name('manage-kpis.index');
    Route::get('/manage-kpis/create', [KpiCardController::class, 'create'])->name('manage-kpis.create');
    Route::post('/manage-kpis', [KpiCardController::class, 'store'])->name('manage-kpis.store');
    Route::get('/manage-kpis/{kpi_card}/edit', [KpiCardController::class, 'edit'])->name('manage-kpis.edit');
    Route::put('/manage-kpis/{kpi_card}', [KpiCardController::class, 'update'])->name('manage-kpis.update');
    Route::post('/manage-kpis/{kpi_card}/fields', [KpiCardController::class, 'storeField'])->name('manage-kpis.fields.store');
    Route::delete('/manage-kpis/{kpi_card}/fields/{field}', [KpiCardController::class, 'destroyField'])->name('manage-kpis.fields.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('/change-password', [SettingController::class, 'changePassword'])->name('settings.change-password');
    Route::post('/change-password', [SettingController::class, 'updatePassword'])->name('settings.change-password.update');
});

Route::redirect('/portal/dashboard', '/dashboard');
Route::redirect('/portal/{any}', '/dashboard')->where('any', '.*');

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'auth.login')->name('login');

Route::post('/login', function (Request $request) {
    return redirect()->route('dashboard');
})->name('login.post');

Route::get('/logout', function () {
    return redirect()->route('login');
})->name('logout');

Route::prefix('portal')->group(function () {

    Route::view('/dashboard', 'dashboard.index')->name('dashboard');

    Route::view('/scorecard', 'scorecard.index')->name('scorecard.index');
    Route::view('/scorecard/tier-wise', 'scorecard.index')->name('scorecard.tier');
    Route::view('/kpi', 'kpi.index')->name('kpi.index');
    Route::view('/kpi/create', 'kpi.create')->name('kpi.create');
    Route::post('/kpi/store', function () {
        return redirect()->route('kpi.index')->with('success', 'KPI saved successfully in demo mode.');
    })->name('kpi.store');
    Route::view('/district-performance', 'districts.performance')->name('districts.performance');
    Route::view('/division-performance', 'divisions.performance')->name('divisions.performance');
    Route::view('/departments', 'departments.index')->name('departments.index');
    Route::view('/reports', 'reports.index')->name('reports.index');
    Route::view('/map', 'map.index')->name('map.index');
    Route::view('/users', 'users.index')->name('users.index');
    Route::view('/settings', 'settings.index')->name('settings.index');
});

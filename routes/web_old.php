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

    Route::view('/petrol-pump-dashboard', 'petrol_pump.dashboard')->name('petrol-pump.dashboard');
    Route::view('/petrol-pump-monitoring', 'petrol_pump.dashboard')->name('petrol.dashboard');
    Route::view('/inspections/map', 'inspections.map')->name('inspections.map');
    Route::view('/inspections/list', 'inspections.list')->name('inspections.list');
    Route::view('/geo-taggings/map', 'geo_taggings.map')->name('geo-taggings.map');
    Route::view('/geo-taggings/list', 'geo_taggings.list')->name('geo-taggings.list');
    Route::view('/geo-taggings/detail', 'geo_taggings.detail')->name('geo-taggings.detail');
    Route::view('/kpi-reporting-status', 'kpi.reporting-status')->name('kpi.reporting-status');
    Route::view('/provincial-kpi-data', 'kpi.provincial-data')->name('kpi.provincial-data');
    Route::view('/district-baseline-report', 'reports.district-baseline')->name('reports.district-baseline');
    Route::view('/district-wise-kpi-score', 'reports.district-wise-kpi-score')->name('reports.district-wise-kpi-score');
    Route::view('/category-wise-district-score', 'reports.category-wise-district-score')->name('reports.category-wise-district-score');
    Route::view('/district-sfn-victim-tier-report', 'reports.district-sfn-victim-tier')->name('reports.district-sfn-victim-tier');
    Route::view('/district-sfn-comparison', 'reports.district-sfn-comparison')->name('reports.district-sfn-comparison');
    Route::view('/district-sfn-comparison-report', 'reports.district-sfn-comparison')->name('reports.district-sfn-comparison');
    Route::view('/division-score-report', 'reports.division-score')->name('reports.division-score');
    Route::view('/district-comparison-report', 'reports.district-comparison')->name('reports.district-comparison');
    Route::view('/district-accumulative-report', 'reports.district-accumulative')->name('reports.district-accumulative');
    Route::view('/division-kpi-ranking', 'reports.division-kpi-ranking')->name('reports.division-kpi-ranking');
    Route::view('/district-weekly-kpi-inspection', 'reports.district-weekly-kpi-inspection')->name('reports.district-weekly-kpi-inspection');
    Route::view('/district-week-rank-changelog', 'reports.district-week-rank-changelog')->name('reports.district-week-rank-changelog');
    Route::view('/change-password', 'settings.change-password')->name('settings.change-password');
    Route::view('/system-manual', 'settings.system-manual')->name('settings.system-manual');

});

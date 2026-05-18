<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaselineDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeoTaggingController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\KpiCategoryController;
use App\Http\Controllers\KpiReportController;
use App\Http\Controllers\PetrolPumpController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScorecardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| PPMF Portal Routes
|--------------------------------------------------------------------------
*/

Route::prefix('portal')->middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard / Overview
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Scorecard
    |--------------------------------------------------------------------------
    */

    Route::get('/scorecard', [ScorecardController::class, 'index'])
        ->name('scorecard.index');

    Route::get('/scorecard/district-wise', [ScorecardController::class, 'index'])
        ->name('scorecard.district-wise');

    Route::get('/scorecard/tier-wise', [ScorecardController::class, 'tierWise'])
        ->name('scorecard.tier');

    Route::get('/scorecard/district/{district}', [ScorecardController::class, 'districtDetail'])
        ->name('scorecard.district-detail');

/*
|--------------------------------------------------------------------------
| KPI Category Management
|--------------------------------------------------------------------------
*/

    Route::get('/kpi-management', [KpiCategoryController::class, 'index'])
        ->name('kpi.index');

    Route::get('/kpi-data-entry', [KpiCategoryController::class, 'create'])
        ->name('kpi.create');

    Route::post('/kpi/store', [KpiCategoryController::class, 'store'])
        ->name('kpi.store');

    Route::get('/kpi/{kpiCategory}/edit', [KpiCategoryController::class, 'edit'])
        ->name('kpi.edit');

    Route::put('/kpi/{kpiCategory}', [KpiCategoryController::class, 'update'])
        ->name('kpi.update');

    Route::delete('/kpi/{kpiCategory}', [KpiCategoryController::class, 'destroy'])
        ->name('kpi.destroy');

    /*
    |--------------------------------------------------------------------------
    | KPI / Data Reports
    |--------------------------------------------------------------------------
    */

    Route::get('/kpi', [KpiCategoryController::class, 'index'])->name('kpi.index');
    Route::get('/kpi/create', [KpiCategoryController::class, 'create'])->name('kpi.create');
    Route::post('/kpi', [KpiCategoryController::class, 'store'])->name('kpi.store');
    Route::get('/kpi/{kpiCategory}/edit', [KpiCategoryController::class, 'edit'])->name('kpi.edit');
    Route::put('/kpi/{kpiCategory}', [KpiCategoryController::class, 'update'])->name('kpi.update');
    Route::delete('/kpi/{kpiCategory}', [KpiCategoryController::class, 'destroy'])->name('kpi.destroy');

    Route::get('/kpi-reporting-status', [KpiReportController::class, 'reportingStatus'])
        ->name('kpi.reporting-status');

    // New (preferred) KPI reporting URLs
    Route::get('/kpi/provincial-data', [KpiReportController::class, 'provincialData'])
        ->name('kpi.provincial-data');

    Route::get('/kpi/district-wise-kpi-score', [KpiReportController::class, 'districtWiseKpiScore'])
        ->name('kpi.district-wise-kpi-score');

    // Backward-compatible URL (keep existing bookmarks)
    Route::get('/provincial-kpi-wise-data', [KpiReportController::class, 'provincialData']);

    Route::get('/kpi-graphical-report', [KpiReportController::class, 'graphicalReport'])
        ->name('kpi.graphical-report');

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    | Keep static until performance controller is added.
    */

    Route::view('/division-performance', 'divisions.performance')
        ->name('divisions.performance');

    Route::view('/district-performance', 'districts.performance')
        ->name('districts.performance');

    /*
    |--------------------------------------------------------------------------
    | Petrol Pump Monitoring
    |--------------------------------------------------------------------------
    */

    Route::get('/petrol-pump-monitoring', [PetrolPumpController::class, 'dashboard'])
        ->name('petrol.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Inspections
    |--------------------------------------------------------------------------
    */

    Route::get('/inspections/map', [InspectionController::class, 'map'])
        ->name('inspections.map');

    Route::get('/inspections/list', [InspectionController::class, 'list'])
        ->name('inspections.list');

    Route::get('/inspections/{id}', [InspectionController::class, 'show'])
        ->name('inspections.show');

    /*
    |--------------------------------------------------------------------------
    | Geo Taggings
    |--------------------------------------------------------------------------
    */

    Route::get('/geo-taggings/map', [GeoTaggingController::class, 'map'])
        ->name('geo-taggings.map');

    Route::get('/geo-taggings/list', [GeoTaggingController::class, 'list'])
        ->name('geo-taggings.list');

    Route::get('/geo-taggings/detail', [GeoTaggingController::class, 'detail'])
        ->name('geo-taggings.detail');

    Route::get('/geo-taggings/{id}', [GeoTaggingController::class, 'show'])
        ->name('geo-taggings.show');
/*
|--------------------------------------------------------------------------
| Baseline Data Management
|--------------------------------------------------------------------------
*/
    Route::get('/district-baseline-data-report', [BaselineDataController::class, 'districtBaseline'])
        ->name('baseline.district-baseline');

    Route::get('/baseline-data', [BaselineDataController::class, 'index'])
        ->name('baseline.index');

    Route::get('/baseline-data/create', [BaselineDataController::class, 'create'])
        ->name('baseline.create');

    Route::post('/baseline-data/store', [BaselineDataController::class, 'store'])
        ->name('baseline.store');

    Route::get('/baseline-data/{id}/edit', [BaselineDataController::class, 'edit'])
        ->name('baseline.edit');

    Route::put('/baseline-data/{id}', [BaselineDataController::class, 'update'])
        ->name('baseline.update');

    Route::get('/baseline-data/{id}', [BaselineDataController::class, 'show'])
        ->name('baseline.show');

/*
|--------------------------------------------------------------------------
| Baseline Asset Records
|--------------------------------------------------------------------------
*/

    Route::get('/baseline-assets', [BaselineDataController::class, 'assets'])
        ->name('baseline.assets');

    Route::get('/baseline-assets/{id}', [BaselineDataController::class, 'showAsset'])
        ->name('baseline.assets.show');

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    Route::view('/departments', 'departments.index')
        ->name('departments.index');

    Route::view('/punjab-map', 'map.index')
        ->name('map.index');

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/category-wise-district-score', [ReportController::class, 'categoryWiseDistrictScore'])
        ->name('reports.category-wise-district-score');

    Route::get('/district-sfn-victim-tier-report', [ReportController::class, 'districtSfnVictimTier'])
        ->name('reports.district-sfn-victim-tier');

    Route::get('/district-sfn-comparison-report', [ReportController::class, 'districtSfnComparison'])
        ->name('reports.district-sfn-comparison');

    Route::get('/division-score-report', [ReportController::class, 'divisionScore'])
        ->name('reports.division-score');

    Route::get('/district-comparison-report', [ReportController::class, 'districtComparison'])
        ->name('reports.district-comparison');

    Route::get('/district-accumulative-report', [ReportController::class, 'districtAccumulative'])
        ->name('reports.district-accumulative');

    Route::get('/division-kpi-ranking-report', [ReportController::class, 'divisionKpiRanking'])
        ->name('reports.division-kpi-ranking');

    Route::get('/district-weekly-kpi-inspection-report', [ReportController::class, 'districtWeeklyKpiInspection'])
        ->name('reports.district-weekly-kpi-inspection');

    Route::get('/district-week-rank-changelog-report', [ReportController::class, 'districtWeekRankChangelog'])
        ->name('reports.district-week-rank-changelog');

    Route::get('/district-wise-kpi-score-report', [ReportController::class, 'districtWiseKpiScore'])
        ->name('reports.district-wise-kpi-score');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');

    Route::post('/users/store', [UserController::class, 'store'])
        ->name('users.store');

    Route::get('/users/{id}/edit', [UserController::class, 'edit'])
        ->name('users.edit');

    Route::put('/users/{id}', [UserController::class, 'update'])
        ->name('users.update');

    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings.index');

    Route::get('/change-password', [SettingController::class, 'changePassword'])
        ->name('settings.change-password');

    Route::post('/change-password', [SettingController::class, 'updatePassword'])
        ->name('settings.change-password.update');

    Route::get('/system-manual', [SettingController::class, 'systemManual'])
        ->name('settings.system-manual');
});

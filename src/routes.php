<?php

use Dennisbusk\DebugNotary\Http\Controllers\DebugNotaryController;
use Illuminate\Support\Facades\Route;

$prefix = config('debug-notary.route_prefix', 'laravel-debug-notary');
$reportingMiddleware = config('debug-notary.reporting_middleware', ['web']);
$dashboardMiddleware = config('debug-notary.middleware', ['web', 'auth']);

Route::group(['middleware' => $reportingMiddleware], function () use ($prefix) {
    Route::post($prefix.'/store', [DebugNotaryController::class, 'storeNotary'])->name('debug-notary.store');
});

Route::group(['middleware' => $dashboardMiddleware], function () use ($prefix) {
    Route::get($prefix, [DebugNotaryController::class, 'index'])->name('debug-notary.index');
    Route::get($prefix.'/{id}', [DebugNotaryController::class, 'show'])->name('debug-notary.show');
    Route::patch($prefix.'/{id}/status', [DebugNotaryController::class, 'updateStatus'])->name('debug-notary.update-status');
    Route::delete($prefix.'/{id}', [DebugNotaryController::class, 'destroy'])->name('debug-notary.destroy');
    Route::post($prefix.'/bulk-delete', [DebugNotaryController::class, 'bulkDestroy'])->name('debug-notary.bulk-destroy');
});

<?php

use Dennisbusk\DebugNotary\Http\Controllers\DebugNotaryController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    $prefix = config('debug-notary.route_prefix', 'laravel-debug-notary');

    Route::get($prefix, [DebugNotaryController::class, 'index'])->name('debug-notary.index');
    Route::post($prefix.'/store', [DebugNotaryController::class, 'storeNotary'])->name('debug-notary.store');
    Route::get($prefix.'/{id}', [DebugNotaryController::class, 'show'])->name('debug-notary.show');
    Route::patch($prefix.'/{id}/status', [DebugNotaryController::class, 'updateStatus'])->name('debug-notary.update-status');
    Route::delete($prefix.'/{id}', [DebugNotaryController::class, 'destroy'])->name('debug-notary.destroy');
    Route::post($prefix.'/bulk-delete', [DebugNotaryController::class, 'bulkDestroy'])->name('debug-notary.bulk-destroy');
});

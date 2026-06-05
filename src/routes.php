<?php

use Dennisbusk\DebugNotary\Http\Controllers\DebugNotaryController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    Route::get('laravel-debug-notary', [DebugNotaryController::class, 'index'])->name('debug-notary.index');
    Route::post('laravel-debug-notary/store', [DebugNotaryController::class, 'storeNotary'])->name('debug-notary.store');
    Route::patch('laravel-debug-notary/{id}/status', [DebugNotaryController::class, 'updateStatus'])->name('debug-notary.update-status');
    Route::delete('laravel-debug-notary/{id}', [DebugNotaryController::class, 'destroy'])->name('debug-notary.destroy');
    Route::post('laravel-debug-notary/bulk-delete', [DebugNotaryController::class, 'bulkDestroy'])->name('debug-notary.bulk-destroy');
});

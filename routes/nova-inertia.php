<?php

use Illuminate\Support\Facades\Route;
use Laravel\Nova\Http\Requests\NovaRequest;

/*
|--------------------------------------------------------------------------
| Tool Routes
|--------------------------------------------------------------------------
|
| Here is where you may register Tool routes for your tool. These routes
| are loaded by the ToolServiceProvider when the tool is booted.
|
*/

Route::get('/', function (NovaRequest $request) {
    return inertia('DebugNotaryTool');
});

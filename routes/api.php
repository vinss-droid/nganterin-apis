<?php

use App\Http\Controllers\API\V1\APIKeyController;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1')->group(function () {
    Route::prefix('/api-key')->controller(APIKeyController::class)->group(function () {
        Route::post('/generate', 'generateApiKey')->name('apiKey.generate');
        Route::get('/activate/{name}', 'activateApiKey')->name('apiKey.activate');

        Route::middleware(['auth.apikey'])->group(function () {
            Route::get('/list', 'listApiKey')->name('apiKey.list');
            Route::get('/deactivate/{name}', 'deactivateApiKey')->name('apiKey.deactivate');
        });
    });
});

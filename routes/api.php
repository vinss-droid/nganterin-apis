<?php

use App\Http\Controllers\API\V1\APIKeyController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
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

    Route::middleware('auth.apikey')->group(function () {

        Route::prefix('/auth')->controller(AuthController::class)->group(function () {
            Route::post('/login/email', 'emailLogin')->name('auth.login.email');
            Route::post('/register', 'emailRegister')->name('auth.register');

            Route::middleware(['auth:sanctum'])->group(function () {
                Route::get('/email/verification-notification', 'emailVerifyNotification')
                    ->name('auth.email.verify.notification')
                    ->middleware(['throttle:6,1']);
                Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
                    $request->fulfill();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Success, email verified!'
                    ]);
                })->name('verification.verify');

                Route::get('/logout', 'logout')->name('auth.logout');
            });

        });

        Route::middleware(['auth:sanctum', 'verified'])->group(function () {

            Route::prefix('profile')
                ->controller(UserController::class)
                ->group(function () {
                    Route::get('/', 'profile')->name('profile.index');
                    Route::post('/user-detail', 'createOrUpdateUserDetail')->name('profile.createOrUpdateUserDetail');
                    Route::post('/address', 'updateUserAddress')->name('profile.updateUserAddress');
                });

        });

    });
});

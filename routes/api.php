<?php

use App\Http\Controllers\API\V1\APIKeyController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\HotelController;
use App\Http\Controllers\API\V1\PartnerController;
use App\Http\Controllers\API\V1\UploadFileController;
use App\Http\Controllers\API\V1\UserController;
use \App\Http\Controllers\API\V1\CountryStateCityController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1')->group(function () {

    Route::prefix('/files')
        ->controller(UploadFileController::class)
        ->group(function () {
            Route::get('/{id}', 'getFile')->name('files');
        });

    Route::prefix('/api-key')->controller(APIKeyController::class)->group(function () {
        Route::post('/generate', 'generateApiKey')->name('apiKey.generate');
        Route::get('/activate/{name}', 'activateApiKey')->name('apiKey.activate');

        Route::middleware(['auth.apikey'])->group(function () {
            Route::get('/list', 'listApiKey')->name('apiKey.list');
            Route::get('/deactivate/{name}', 'deactivateApiKey')->name('apiKey.deactivate');
        });
    });

    Route::middleware('auth.apikey')->group(function () {

        Route::prefix('/locations')
            ->controller(CountryStateCityController::class)
            ->group(function () {
                Route::get('/countries', 'country')->name('locations.countries');
                Route::get('/countries/{iso2_country}/states', 'states')->name('locations.states');
                Route::get('/countries/{iso2_country}/states/{iso2_state}/cities', 'cities')->name('locations.cities');
            });

        Route::prefix('/auth')->controller(AuthController::class)->group(function () {
            Route::post('/login/oauth/google', 'googleLoginRegister')->name('auth.login.google');
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

            Route::prefix('/files')
                ->controller(UploadFileController::class)
                ->group(function () {
                    Route::post('/upload', 'uploadFile')->name('files.upload');
                });

            Route::prefix('/profile')
                ->controller(UserController::class)
                ->group(function () {
                    Route::get('/', 'profile')->name('profile.index');
                    Route::post('/user-detail', 'createOrUpdateUserDetail')->name('profile.createOrUpdateUserDetail');
                    Route::post('/address', 'updateUserAddress')->name('profile.updateUserAddress');
                });

            Route::prefix('/partner')
                ->controller(PartnerController::class)
                ->middleware(['partner'])
                ->group(function () {
                    Route::get('/', 'getPartner')->name('partner');
                    Route::post('/register', 'registerPartners')->name('partner.register');

                    Route::prefix('hotels')
                        ->controller(HotelController::class)
                        ->middleware(['hotels'])
                        ->group(function () {
                           Route::get('/', 'getMyHotels')->name('hotel.index');
                           Route::post('/create', 'createHotel')->name('hotel.create');
                        });
                });

            Route::prefix('/hotels')
                ->controller(HotelController::class)
                ->group(function () {
                    Route::get('/', 'searchHotel')->name('hotels.search');
                    Route::get('/{id}', 'getHotelById')->name('hotels.detail');
                });

        });

    });
});

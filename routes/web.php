<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

    Route::get('/', function () {return view('index');})->name('index');
    Route::get('/verification', [\App\Http\Controllers\appController::class,'verification'])->name('verification');
    Route::post('/verification-control', [\App\Http\Controllers\appController::class,'verificationControl'])->name('verification.control');
    Route::post('/tekrar-sms-gonder', [\App\Http\Controllers\appController::class,'sendSms'])->name('repeat.sms');
    Route::post('/tc-kontrol', [\App\Http\Controllers\appController::class,'tcControl'])->name('tc.control');
    Route::post('/cikis-yap', [\App\Http\Controllers\appController::class,'logout'])->name('logout');
    Route::middleware(['auth'])->group(function ()
    {
        Route::get('/odemeler', [\App\Http\Controllers\appController::class,'priceList'])->name('price.list');
        Route::get('/odeme-yap', [\App\Http\Controllers\appController::class,'pay'])->name('pay');
        Route::post('/odeme-yap', [\App\Http\Controllers\appController::class,'pricing'])->name('pricing');
        Route::post('/odeme-hesapla', [\App\Http\Controllers\appController::class,'calcPrice'])->name('price-calculation');
        Route::get('/profil', [\App\Http\Controllers\appController::class,'profile'])->name('profile');
        Route::post('/profil-duzenle', [\App\Http\Controllers\appController::class,'profileEdit'])->name('profile.edit');
        Route::post('/odeme-sonuc', [\App\Http\Controllers\appController::class,'payResult'])->name('pay.result');
    });

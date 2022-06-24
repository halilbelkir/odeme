<?php

use Illuminate\Support\Facades\Artisan;
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
    Route::get('/verification', [\App\Http\Controllers\AppController::class,'verification'])->name('verification');
    Route::post('/verification-control', [\App\Http\Controllers\AppController::class,'verificationControl'])->name('verification.control');
    Route::post('/tekrar-sms-gonder', [\App\Http\Controllers\AppController::class,'sendSms'])->name('repeat.sms');
    Route::post('/tc-kontrol', [\App\Http\Controllers\AppController::class,'tcControl'])->name('tc.control');
    Route::post('/cikis-yap', [\App\Http\Controllers\AppController::class,'logout'])->name('logout');
    Route::middleware(['back-history','variables','auth'])->group(function ()
    {
        Route::post('/odeme-test', [\App\Http\Controllers\AppController::class,'testPrice'])->name('pay.test');
        Route::get('/odemeler', [\App\Http\Controllers\AppController::class,'priceList'])->name('price.list');
        Route::get('/odeme-yap', [\App\Http\Controllers\AppController::class,'pay'])->name('pay');
        Route::post('/odeme-yap', [\App\Http\Controllers\AppController::class,'pricing'])->name('pricing');
        Route::post('/odeme-hesapla', [\App\Http\Controllers\AppController::class,'calcPrice'])->name('price-calculation');
        Route::get('/profil', [\App\Http\Controllers\AppController::class,'profile'])->name('profile');
        Route::post('/profil-duzenle', [\App\Http\Controllers\AppController::class,'profileEdit'])->name('profile.edit');
        Route::match(['get','post'],'/odeme-sonuc', [\App\Http\Controllers\AppController::class,'payResult'])->name('pay.result');
        Route::get('/receipt/{amount}', [\App\Http\Controllers\AppController::class,'receipt'])->name('receipt');
    });

    Route::prefix('yonetimpaneli')->group(function ()
    {
        Route::get('/', function () { return view('admin.index');})->name('admin.index');
        Route::post('/login', [\App\Http\Controllers\AdminController::class,'login'])->name('admin.login');
        Route::get('/kayit-ol', function () { return view('admin.register');})->name('admin.register');
        Route::post('/register', [\App\Http\Controllers\AdminController::class,'register'])->name('admin.register.submit');
        Route::get('/sifremi-unuttum', function () { return view('admin.password');})->name('admin.password');
        Route::post('/password', [\App\Http\Controllers\AdminController::class,'password'])->name('admin.password.submit');
        Route::post('/cikis-yap', [\App\Http\Controllers\AdminController::class,'logout'])->name('admin.logout');
        Route::get('/sifremi-unuttum/{token}', function () { return view('admin.passwordReset');})->name('password.reset');
        Route::post('/reset-password', [\App\Http\Controllers\AdminController::class, 'passwordReset'])->name('admin.password.reset.submit');

        Route::middleware(['admin.auth'])->group(function ()
        {
            Route::get('/dashboard', [\App\Http\Controllers\AdminController::class,'dashboard'])->name('admin.dashboard');
            Route::post('/datatables', [App\Http\Controllers\AdminController::class, 'datatables'])->name('admin.datatables');
            Route::get('/todayDatatables', [App\Http\Controllers\AdminController::class, 'todayDatatable'])->name('admin.today.datatables');
            Route::get('/weekDatatables', [App\Http\Controllers\AdminController::class, 'weekDatatable'])->name('admin.week.datatables');
            Route::get('/monthDatatables', [App\Http\Controllers\AdminController::class, 'monthDatatable'])->name('admin.month.datatables');
            Route::get('/datatablesTurkish', [App\Http\Controllers\AdminController::class, 'datatablesTurkish'])->name('admin.datatables.turkish');
        });
    });

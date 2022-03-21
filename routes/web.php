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
    Route::get('/verification', [\App\Http\Controllers\AppController::class,'verification'])->name('verification');
    Route::post('/verification-control', [\App\Http\Controllers\AppController::class,'verificationControl'])->name('verification.control');
    Route::post('/tekrar-sms-gonder', [\App\Http\Controllers\AppController::class,'sendSms'])->name('repeat.sms');
    Route::post('/tc-kontrol', [\App\Http\Controllers\AppController::class,'tcControl'])->name('tc.control');
    Route::post('/cikis-yap', [\App\Http\Controllers\AppController::class,'logout'])->name('logout');
    Route::middleware(['auth'])->group(function ()
    {
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
        Route::post('/cikis-yap', [\App\Http\Controllers\AdminController::class,'logout'])->name('admin.logout');

        Route::middleware(['auth'])->group(function ()
        {
            Route::get('/dashboard', [\App\Http\Controllers\AdminController::class,'dashboard'])->name('admin.dashboard');
            Route::get('/todayDatatables', [App\Http\Controllers\AdminController::class, 'todayDatatable'])->name('admin.today.datatables');
            Route::get('/weekDatatables', [App\Http\Controllers\AdminController::class, 'weekDatatable'])->name('admin.week.datatables');
            Route::get('/monthDatatables', [App\Http\Controllers\AdminController::class, 'monthDatatable'])->name('admin.month.datatables');
        });
    });

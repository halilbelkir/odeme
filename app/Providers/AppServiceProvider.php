<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('*',function($view)
        {
            if (Auth::user())
            {
                if (Cache::has('remainder')) {
                    $remainder = Cache::get('remainder');
                }
                else {
                    $customerCode = Auth::user()->customer_code;
                    //$customerCode = 246492;
                    $remainder = DB::connection('sqlsrv')->select('Exec sp_mus_odeme :customer_code', ['customer_code' => $customerCode]);
                    Cache::put('remainder', $remainder, Carbon::now()->addMinutes(480));
                }

                if (Cache::has('priceList')) {
                    $priceList = Cache::get('priceList');
                    $totalPrice = Cache::get('totalPrice');
                }
                else {
                    $priceList = array();
                    $totalPrice = 0;

                    foreach ($remainder as $price) {
                        $key = $price->Month;
                        $priceList[$key]['price'] = isset($priceList[$key]) ? $priceList[$key]['price'] + $price->RemainingInstallment : $price->RemainingInstallment;
                        $priceList[$key]['month'] = $price->AYAD1;
                        $priceList[$key]['year'] = $price->YIL;
                        $totalPrice += $price->RemainingInstallment;
                    }

                    Cache::put('priceList', $priceList, Carbon::now()->addMinutes(480));
                    Cache::put('totalPrice', $totalPrice, Carbon::now()->addMinutes(480));
                }

                $view->with('priceList', $priceList);
                $view->with('totalPrice', $totalPrice);
            }
        });

    }
}

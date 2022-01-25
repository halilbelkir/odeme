<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
                $customerCode        = Auth::user()->customer_code;
                $remainderCacheName  = 'remainder'.$customerCode;
                $priceListCacheName  = 'priceList'.$customerCode;
                $totalPriceCacheName = 'totalPrice'.$customerCode;

                if (Cache::has($remainderCacheName))
                {
                    $remainder = Cache::get($remainderCacheName);
                }
                else
                {
                    //$customerCode = 246492;
                    $remainder = DB::connection('sqlsrv')->select('Exec sp_mus_odeme :customer_code', ['customer_code' => $customerCode]);

                    Cache::put($remainderCacheName, $remainder, Carbon::now()->addMinutes(480));
                }

                if (Cache::has('priceList'))
                {
                    $priceList = Cache::get($priceListCacheName);
                    $totalPrice = Cache::get($totalPriceCacheName);
                }
                else
                {
                    $priceList = array();
                    $totalPrice = 0;

                    foreach ($remainder as $price)
                    {
                        $key = $price->Month;
                        $priceList[$key]['price'] = isset($priceList[$key]) ? $priceList[$key]['price'] + $price->RemainingInstallment : $price->RemainingInstallment;
                        $priceList[$key]['month'] = $price->AYAD1;
                        $priceList[$key]['year'] = $price->YIL;
                        $totalPrice += $price->RemainingInstallment;
                    }

                    ksort($priceList);

                    Cache::put($priceListCacheName, $priceList, Carbon::now()->addMinutes(480));
                    Cache::put($totalPriceCacheName, $totalPrice, Carbon::now()->addMinutes(480));
                }

                $view->with('priceList', $priceList);
                $view->with('totalPrice', $totalPrice);
            }
        });

    }
}

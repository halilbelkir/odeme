<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class Variables
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!empty(Auth::user()))
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
                $remainder = DB::connection('sqlsrv')->select('Exec Sp_Web_Odeme_0_Musteri_Odeme_List :customer_code', ['customer_code' => $customerCode]);

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
                    $priceList[$key]['amounts'][] = $price->RemainingInstallment;
                    $priceList[$key]['month']   = $price->AYAD1;
                    $priceList[$key]['year']    = $price->YIL;
                    $priceList[$key]['OrderPaymentPlanID'][] = $price->OrderPaymentPlanID;
                    $totalPrice += $price->RemainingInstallment;
                }

                ksort($priceList);

                Cache::put($priceListCacheName, $priceList, Carbon::now()->addMinutes(480));
                Cache::put($totalPriceCacheName, $totalPrice, Carbon::now()->addMinutes(480));
            }

            View::share([
                'priceList' => $priceList,
                'totalPrice' => $totalPrice,
            ]);
        }
        else
        {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Cache::flush();
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use App\helpers\helpers;
use App\Models\PayResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Validator,Session;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        try
        {
            $attribute = array(
                'email'    => 'E-Mail',
                'password' => 'Şifre',
            );

            $rules = array(
                'email'         => 'required|email',
                'password'      => 'required'
            );


            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->remember))
            {
                if (Auth::user()->login_control == 1)
                {
                    Session::flash('message', array('Başarılı!','Giriş Başarılı.', 'success'));
                    return redirect()->intended(route('admin.dashboard'));
                }
                else
                {
                    Session::flash('message', array('Başarısız!','Giriş Yetkiniz Bulunmamaktadır.', 'error'));
                    return redirect()->back()->withInput($request->only('email', 'password'));
                }
            }

            Session::flash('message', array('Başarısız!','E-mail adresiniz veya şifreniz hatalıdır.', 'error'));
            return redirect()->back()->withInput($request->only('email', 'password'));
        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
            return redirect()->back();
        }
    }

    public function register(Request $request)
    {
        try
        {
            $attribute = array(
                'name'     => 'Ad',
                'surname'  => 'Soyad',
                'email'    => 'E-Mail',
                'password' => 'Şifre',
            );

            $rules = array(
                'email'     => 'required|unique:users,email',
                'password'  => 'required',
                'name'      => 'required',
                'surname'   => 'required',
            );


            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails())
            {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $user           = new User;
            $user->name     = $request->get('name');
            $user->surname  = $request->get('surname');
            $user->email    = $request->get('email');
            $user->password = bcrypt($request->get('password'));
            $user->login_control = 1;
            $user->save();

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password], 1))
            {
                Session::flash('message', array('Başarılı!','Kayıt Başarılı.', 'success'));
                return redirect()->intended(route('admin.dashboard'));
            }

            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
            return redirect()->back()->withInput($request->only('email', 'password','name','surname'));
        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
            return redirect()->back();
        }
    }

    public function password(Request $request)
    {
        try
        {
            $attribute = array(
                'email'    => 'E-Mail',
            );

            $rules = array(
                'email'     => 'required|email',
            );


            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails())
            {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['email' => __($status)]);
        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
            return redirect()->back();
        }
    }

    public function passwordReset(Request $request)
    {
        try
        {
            $attribute = array(
                'token'    => 'Token',
                'email'    => 'E-mail',
                'password' => 'Şifre',
            );

            $rules = array(
                'token'    => 'required',
                'email'    => 'required|email',
                'password' => 'required',
            );

            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails())
            {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $user = User::where('email',$request->email)->first();

            if ($user)
            {
                $user->password = bcrypt($request->password);
                $user->save();

                if (Auth::attempt(['email' => $request->email, 'password' => $request->password], 1))
                {
                    Session::flash('message', array('Başarılı!','Şifre Değiştirildi.', 'success'));
                    return redirect()->intended(route('admin.dashboard'));
                }
            }

        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
            return redirect()->back();
        }
    }

    public function dashboard()
    {
        $todayCount      = PayResult::where('response_code','00')->whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) = DATE_FORMAT(CURDATE(), '%Y/%m/%d')")->count();
        $weekCount       = PayResult::where('response_code','00')->whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) > DATE_SUB(CURDATE(), INTERVAL 1 WEEK)")->count();
        $monthCount      = PayResult::where('response_code','00')->whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->count();
        $todayTotalPrice = PayResult::selectRaw('SUM(amount) as price')->where('response_code','00')->whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) = DATE_FORMAT(CURDATE(), '%Y/%m/%d')")->first()->price;
        $todayTotalPrice = helpers::priceFormatCc($todayTotalPrice);
        $weekTotalPrice  = PayResult::selectRaw('SUM(amount) as price')->where('response_code','00')->whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) > DATE_SUB(CURDATE(), INTERVAL 1 WEEK)")->first()->price;
        $weekTotalPrice  = helpers::priceFormatCc($weekTotalPrice);
        $monthTotalPrice = PayResult::selectRaw('SUM(amount) as price')->where('response_code','00')->whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->first()->price;
        $monthTotalPrice = helpers::priceFormatCc($monthTotalPrice);

        return view('admin.dashboard',compact('todayCount','todayTotalPrice','weekCount','weekTotalPrice','monthCount','monthTotalPrice'));
    }

    public function todayDatatable()
    {
        return Datatables::of(PayResult::whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) = DATE_FORMAT(CURDATE(), '%Y/%m/%d')")->get())
            ->editColumn('created_at', function ($payResult) {
                return $payResult->created_at ? with(new Carbon($payResult->created_at))->format('d-m-Y') : '';
            })
            ->editColumn('amount', function ($payResult) {
                return helpers::priceFormatCc($payResult->amount).' ₺';
            })
            ->addColumn('response_code', function ($payResult) {
                return $payResult->response_code == 0 ? '<h4><span class="badge bg-green">İşlem Başarılı</span></h4>' : '<span class="badge bg-red">İşlem Başarısız ('.$payResult->response_code.')</span>';
            })
            ->editColumn('error_message', function ($payResult) {
                return '<strong>'.$payResult->error_message_detail.'</strong><br>'.$payResult->error_message_title ?? null;
            })
            ->rawColumns(['response_code','error_message'])
            ->toJson();
    }

    public function weekDatatable()
    {
        return Datatables::of(PayResult::whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) > DATE_SUB(CURDATE(), INTERVAL 1 WEEK)")->get())
            ->editColumn('created_at', function ($payResult) {
                return $payResult->created_at ? with(new Carbon($payResult->created_at))->format('d-m-Y') : '';
            })
            ->editColumn('amount', function ($payResult) {
                return helpers::priceFormatCc($payResult->amount).' ₺';
            })
            ->addColumn('response_code', function ($payResult) {
                return $payResult->response_code == 0 ? '<h4><span class="badge bg-green">İşlem Başarılı</span></h4>' : '<span class="badge bg-red">İşlem Başarısız ('.$payResult->response_code.')</span>';
            })
            ->editColumn('error_message', function ($payResult) {
                return '<strong>'.$payResult->error_message_detail.'</strong><br>'.$payResult->error_message_title ?? null;
            })
            ->rawColumns(['response_code','error_message'])
            ->toJson();
    }

    public function monthDatatable()
    {
        return Datatables::of(PayResult::whereRaw("(DATE_FORMAT(created_at,'%Y/%m/%d')) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->get())
            ->editColumn('created_at', function ($payResult) {
                return $payResult->created_at ? with(new Carbon($payResult->created_at))->format('d-m-Y') : '';
            })
            ->editColumn('amount', function ($payResult) {
                return helpers::priceFormatCc($payResult->amount).' ₺';
            })
            ->addColumn('response_code', function ($payResult) {
                return $payResult->response_code == 0 ? '<h4><span class="badge bg-green">İşlem Başarılı</span></h4>' : '<span class="badge bg-red">İşlem Başarısız ('.$payResult->response_code.')</span>';
            })
            ->editColumn('error_message', function ($payResult) {
                return '<strong>'.$payResult->error_message_detail.'</strong><br>'.$payResult->error_message_title ?? null;
            })
            ->rawColumns(['response_code','error_message'])
            ->toJson();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cache::flush();
        return redirect()->route('admin.index');
    }
}

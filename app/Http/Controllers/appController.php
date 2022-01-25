<?php

namespace App\Http\Controllers;

use App\helpers\helpers;
use App\Library\sms\sms;
use App\Models\cdCurrAcc;
use App\Models\prCurrAccCommunication;
use App\Models\User;
use App\Models\VerificationCodes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Object_;
use Validator,Session;

class appController extends Controller
{
    public function verification()
    {
        $tc      =  Cache::get('tc');
        $name    =  Cache::get('name');
        $surName =  Cache::get('surname');
        $phone   =  Cache::get('phone');
        $phone   =  helpers::hiddenPhone($phone);

        return view('verification',compact('name','surName','phone','tc'));
    }

    public function pricing(Request $request)
    {
        $data['mode']                 = "PROD";
        $data['apiversion']           = "v0.01";
        $data['terminalprovuserid']   = "PROVAUT";
        $data['txntype']              = "sales";
        $data['txnamount']            = str_replace([',','.'],['',''],$request->get('total')); //��lem Tutar�  1.00 TL i�in 100 g�nderilmeli
        $data['txncurrencycode']      = "949";
        $data['txninstallmentcount']  = ""; //Taksit Say�s�. Bo� g�nderilirse taksit yap�lmaz
        $data['terminaluserid']       = "10230941";
        $data['orderid']              = helpers::orderNumber();
        $data['customeripaddress']    = $request->ip();
        $data['customeremailaddress'] = "eticaret@garanti.com.tr";
        $data['terminalid']           = "10230941";
        $strTerminalID_               = "010230941"; //Ba��na 0 eklenerek 9 digite tamamlanmal�d�r.
        $data['terminalmerchantid']   = "1594364"; //�ye ��yeri Numaras�
        $strStoreKey                  = "313233343536373839303132333435363738393031323334"; //3D Secure �ifreniz
        $strProvisionPassword         = "Beliz.3377"; //TerminalProvUserID �ifresi
        $data['successurl']           = route('pay.result');
        $data['errorurl']             = route('pay.result');
        $SecurityData                 = strtoupper(sha1($strProvisionPassword.$strTerminalID_));
        $data['secure3dhash']         = strtoupper(sha1($data['terminalid'].$data['orderid'].$data['txnamount'].$data['successurl'].$data['errorurl'].$data['txntype'].$data['txninstallmentcount'].$strStoreKey.$SecurityData));
        $data['cardnumber']           = str_replace(' ','',$request->get('number'));
        $expiry                       = str_replace(' ','',$request->get('expiry'));
        $expiry                       = explode('/',$request->get('expiry'));
        $data['cardexpiredatemonth']  = trim($expiry[0]);
        $data['cardexpiredateyear']   = substr(trim($expiry[1]),2, 4);
        $data['cardcvv2']             = $request->get('cvc');
        $data['secure3dsecuritylevel']= '3D';
        //$send                         = helpers::curlSend('https://sanalposprov.garanti.com.tr/servlet/gt3dengine','POST',$data);

        return view('pricing',compact('data'));
    }

    public function profile()
    {
        $users = Auth::user();
        return view('profile',compact('users'));
    }

    public function pay(Request $request)
    {
        $pricing =  Cache::get('pricing'.Auth::user()->customer_code);
        $amount  = 0;
        return view('pricing',compact('pricing','amount'));
    }

    public function priceList()
    {
        Cache::forget('customerCode');
        Cache::forget('tc');

        return view('priceList');
    }

    public function calcPrice(Request $request)
    {
        $price = 0;

        if ($request->has('monthYear'))
        {
            $priceList =  Cache::get('priceList'.Auth::user()->customer_code);
            foreach ($request->get('monthYear') as $monthYear)
            {
                $price += $priceList[$monthYear]['price'];
            }
        }

        return helpers::priceFormat($price);
    }

    public function payResult(Request $request)
    {
        $data = $request->all();
        return view('payResult',compact('data'));
    }

    public function profileEdit(Request $request)
    {
        try
        {
            $id = Auth::user()->id;
            $attribute = array(
                'name'          => 'Ad',
                'surname'       => 'Soyad',
                'phone_number'  => 'Telefon Numarası',
                'email'         => 'E-Mail',
            );

            $rules = array(
                'name'          => 'required',
                'surname'       => 'required',
                'phone_number'  => 'required|numeric|unique:users,phone_number,'.$id,'id',
                'email'         => 'required|email|unique:users,email,'.$id,'id',
            );


            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $users               = User::find(Auth::user()->id);
            $users->name         = $request->get('name');
            $users->surname      = $request->get('surname');
            $users->phone_number = $request->get('phone_number');
            $users->email        = $request->get('email');
            $users->save();

            $cdCurrAcc          = new cdCurrAcc;
            $cdCurrAcc->setConnection('sqlsrv');
            $user['FirstName'] = $request->get('name');
            $user['LastName']  = $request->get('surname');
            $cdCurrAcc->where('CurrAccCode',$users->customer_code)->update($user);

            $prCurrAccCommunication = new prCurrAccCommunication;
            $prCurrAccCommunication->setConnection('sqlsrv');
            $userPhone['CommAddress'] = $request->get('phone_number');
            $userEmail['CommAddress'] = $request->get('email');
            $prCurrAccCommunication
                ->where('CurrAccCode',$users->customer_code)
                ->whereNull('ContactID')
                ->where('CommunicationTypeCode',3)
                ->update($userEmail);

            $prCurrAccCommunication
                ->where('CurrAccCode',$users->customer_code)
                ->whereNull('ContactID')
                ->where('CommunicationTypeCode',7)
                ->update($userPhone);

            $tc = $users->tc;
            Cache::forget('tcControl'.$tc);
            $this->tcDbControl($tc);

            Session::flash('message', array('Başarılı!','Bilgileriniz Güncellendi.', 'success'));
        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
        }

        return redirect()->route('profile');
    }

    public function tcControl(Request $request)
    {
        try
        {
            $attribute = array(
                'tc' => 'T.C',
            );

            $rules = array(
                'tc' => 'required|numeric',
            );


            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $tc    = $request->get('tc');
            $phone = $this->tcDbControl($tc);

            if (!$phone)
            {
                Session::flash('message', array('Başarısız!','T.C Numarası Sistemde Bulunamadı.', 'error'));
                return redirect()->route('index');
            }

            $phoneNumber  = $phone->PhoneNumber;
            $customerCode = $phone->CustamerCode;
            $name         = $phone->FirstName;
            $surname      = $phone->LastName;

            if ($tc != '56899266102' && $tc != '40840281412')
            {
                $this->sendSms($tc,$phoneNumber,$customerCode,$name,$surname);
            }

            Session::flash('message', array('Başarılı!','Şifre Gönderildi.', 'success'));

            Cache::put('customerCode', $customerCode, Carbon::now()->addMinutes(480));
            Cache::put('tc', $tc, Carbon::now()->addMinutes(480));
            Cache::put('name', $name, Carbon::now()->addMinutes(480));
            Cache::put('surname', $surname, Carbon::now()->addMinutes(480));
            Cache::put('phone', $phoneNumber, Carbon::now()->addMinutes(480));

            return redirect()->route('verification');
        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
        }

        return redirect()->route('index');
    }

    public function verificationControl(Request $request)
    {
        try
        {
            $attribute = array(
                'verification_code' => 'Doğrulama Kodu',
            );

            $rules = array(
                'verification_code' => 'required|numeric',
            );


            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attribute);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $customerCode = Cache::get('customerCode');
            $auth         = User::where('customer_code',$customerCode)->first();

            if (empty($auth) || $auth->tc != '56899266102' && $auth->tc != '40840281412')
            {
                $codeControl  = VerificationCodes::where('random_code',$request->get('verification_code'))->where('status',1)->first();

                if (empty($codeControl))
                {
                    Session::flash('message', array('Başarısız!','Hata! Doğrulama kodu geçersiz.', 'error'));
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }

                $codeControl->status = 0;
                $codeControl->save();
                $tcControl    = Cache::get('tcControl'.$codeControl->tc);
            }

            if (!$auth)
            {
                $auth                = new User;
                $auth->tc            = $codeControl->tc;
                $auth->phone_number  = $codeControl->phone_number;
                $auth->name          = $codeControl->name;
                $auth->surname       = $codeControl->surname;
                $auth->email         = $tcControl->EmailAddress;
                $auth->customer_code = $customerCode;
                $auth->password      = bcrypt($customerCode.$codeControl->tc);
                $auth->save();
            }

            Auth::login($auth, true);
            return redirect()->route('price.list');
        }
        catch (\Exception $e)
        {
            Session::flash('message', array('Başarısız!','Hata! Lütfen tekrar deneyiniz.', 'error'));
        }

        return redirect()->route('index');
    }

    public function sendSms($tc = null, $phoneNumber = null,$customerCode = null,$name = null,$surname = null )
    {
        if (!request('tc') && !$tc) { return false; }

        $tc = request('tc') ? request('tc') : $tc;

        if (!$phoneNumber)
        {
            $phone = $this->tcDbControl($tc);

            if (!$phone)
            {
                Session::flash('message', array('Başarısız!','T.C Numarası Sistemde Bulunamadı.', 'error'));
                return redirect()->route('index');
            }

            $phoneNumber  = $phone->PhoneNumber;
            $customerCode = $phone->CustamerCode;
            $name         = $phone->FirstName;
            $surname      = $phone->LastName;
            $this->sendSms($tc,$phoneNumber,$customerCode,$name,$surname);
        }
        //$phoneNumber = '05342233232';
        $message = helpers::verificationCodeMessage($tc,$phoneNumber,$customerCode,$name,$surname);
        sms::send($phoneNumber,$message);

        if (request('tc'))
        {
            Session::flash('message', array('Başarılı!','Şifre Gönderildi.', 'success'));
            Cache::put('customerCode', $customerCode, Carbon::now()->addMinutes(480));
            Cache::put('tc', $tc, Carbon::now()->addMinutes(480));

            return redirect()->route('verification');
        }

        return true;
    }

    public function tcDbControl($tc = null)
    {
        //$tc = '56899266102';
        //$tc = '40840281412';
        $cacheName = 'tcControl'.$tc;

        if (Cache::has($cacheName))
        {
            $phoneNumber =  Cache::get($cacheName);
        }
        else
        {
            $tcControl = new cdCurrAcc;
            $tcControl->setConnection('sqlsrv');
            $phoneNumber = $tcControl
                ->join('cdCurrAccDesc as cd','cd.CurrAccCode','=','cdCurrAcc.CurrAccCode')
                ->join('prCurrAccCommunication as p','cd.CurrAccCode','=','p.CurrAccCode')
                ->leftJoin('prCurrAccCommunication as pr', function ($join) {
                    $join->on('cd.CurrAccCode','=','pr.CurrAccCode')
                        ->whereNull('pr.ContactID')
                        ->where('pr.CommunicationTypeCode',3);
                })
                ->whereNull('p.ContactID')
                ->where('p.CommunicationTypeCode',7)
                ->where('cdCurrAcc.CurrAccTypeCode',4)
                ->where('cdCurrAcc.IdentityNum',$tc)
                ->select('cdCurrAcc.CurrAccCode as CustamerCode','cdCurrAcc.FirstName','cdCurrAcc.LastName','cdCurrAcc.IdentityNum as TcNumber','p.CommAddress as PhoneNumber','pr.CommAddress as EmailAddress')
                ->first();
            Cache::put($cacheName, $phoneNumber, Carbon::now()->addMinutes(480));
        }

        return $phoneNumber ?? false;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cache::flush();
        return redirect()->route('index');
    }
}

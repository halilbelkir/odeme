<?php

namespace App\Http\Controllers;

use App\helpers\helpers;
use App\Jobs\Test;
use App\Library\sms\sms;
use App\Models\cdCurrAcc;
use App\Models\PayResult;
use App\Models\prCurrAccCommunication;
use App\Models\TrCreditCardPaymentHeader;
use App\Models\TrCreditCardPaymentLine;
use App\Models\TrCurrAccBook;
use App\Models\TrPaymentHeader;
use App\Models\TrPaymentLine;
use App\Models\User;
use App\Models\VerificationCodes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Object_;
use Validator,Session;

class AppController extends Controller
{
    public function verification()
    {
        if (Cache::has('tc_'.csrf_token()))
        {
            $tc      =  Cache::get('tc_'.csrf_token());
            $name    =  Cache::get('name_'.csrf_token());
            $surName =  Cache::get('surname_'.csrf_token());
            $phone   =  Cache::get('phone_'.csrf_token());
            $phone   =  helpers::hiddenPhone($phone);

            return view('verification',compact('name','surName','phone','tc'));
        }

        return redirect()->route('index');
    }

    public function pricing(Request $request)
    {
        $total                        = strpos($request->get('total'), '.') == false ?  helpers::priceFormat($request->get('total'),2) : helpers::priceFormat($request->get('total'),3);
        $totalPrice                   = Cache::get('totalPrice'.Auth::user()->customer_code);
        $totalPrice                   = helpers::priceFormat($totalPrice,2);

        if ($total > $totalPrice)
        {
            return  json_encode(array('message' => 'Girilen tutar, toplam ödenecek tutardan fazladır. Lütfen doğru tutar giriniz.'));
        }

        $data['mode']                 = "PROD";
        $data['apiversion']           = "v0.01";
        $data['terminalprovuserid']   = "PROVAUT";
        $data['txntype']              = "sales";
        $data['txnamount']            = str_replace([',','.'],['',''],$total); //��lem Tutar�  1.00 TL i�in 100 g�nderilmeli
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
        $data['cardexpiredateyear']   = trim($expiry[1]);
        $data['cardcvv2']             = $request->get('cvc');
        $data['secure3dsecuritylevel']= '3D';

        if ($request->has('price'))
        {
            $customerCode = Auth::user()->customer_code;
            $priceList    = Cache::get('priceList'.$customerCode);
            $price        = 0;

            foreach ($request->get('price') as $monthYear)
            {
                $price += $priceList[$monthYear]['price'];
            }

            Cache::put('price'.$customerCode, $request->get('price'), Carbon::now()->addMinutes(30));
            Cache::put('priceTotal'.$customerCode, $price, Carbon::now()->addMinutes(30));
        }

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

    public function testPrice(Request $request)
    {
        $amount = strpos($request->get('total'), '.') == false ?  helpers::priceFormat($request->get('total'),1) : helpers::totalPriceFormat($request->get('total'));

        $this->priceMssqlSave($amount);

        $data['link'] = route('receipt',[$amount]);
        $data['Transaction']['Response']['Code'] = 0;
        $this->cacheClear();
        session()->flash('flash_message', $data);
        return redirect()->route('price.list');
    }

    public function priceList()
    {
        $deleteDate    = '20220715';
        $selectDate    = '20220715';
        $customerCode  = Auth::user()->customer_code;
        $connection    = DB::connection('sqlsrv');

/*
                                                                                        $kkRefNo       = $connection->select("SELECT SCOPE_IDENTITY();
                                                                                                                                        delete from trPaymentLineCurrency Where PaymentLineID in
                                                                                                                                        (Select PaymentLineID from trPaymentLine Where PaymentHeaderID in
                                                                                                                                        (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$deleteDate."' ));
                                                                                                                                        delete from trPaymentLine Where PaymentHeaderID in
                                                                                                                                        (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$deleteDate."' );
                                                                                                                                        delete from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$deleteDate."';    --- 6.Tablo
                                                                                                                                        Delete  from trCurrAccBookCurrency Where CurrAccBookID in
                                                                                                                                        (select CurrAccBookID from trCurrAccBook where CurrAccCode = '".$customerCode."' and DocumentDate ='".$deleteDate."');
                                                                                                                                        Delete from trCurrAccBook where CurrAccCode = '".$customerCode."' and DocumentDate ='".$deleteDate."';
                                                                                                                                        Delete from trCreditCardPaymentLineCurrency where CreditCardPaymentLineID in
                                                                                                                                        (Select  CreditCardPaymentLineID from trCreditCardPaymentLine where CreditCardPaymentHeaderID in
                                                                                                                                        (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader
                                                                                                                                        where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$deleteDate."'));
                                                                                                                                        Delete  from trCreditCardPaymentLine where CreditCardPaymentHeaderID in
                                                                                                                                        (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader  where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$deleteDate."');
                                                                                                                                        Delete from trCreditCardPaymentHeader  where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$deleteDate."';
                                                                                                                                ");
                                                                                        Cache::flush();
                                                                                        return view('priceList');
        *//*
                        $s[2] = $connection->select("SET NOCOUNT ON; Select * from trCreditCardPaymentLineCurrency where CreditCardPaymentLineID in (Select  CreditCardPaymentLineID from trCreditCardPaymentLine where CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$selectDate."'));");
                        $s[7] = $connection->select("
                                                                                                                   SET NOCOUNT ON;
                                                                                                                   Select * from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."');
                                                                                                                   ");
                        $s[8] = $connection->select("
                                                                                                                   SET NOCOUNT ON;
                                                                                                                   Select * from trPaymentLineCurrency Where PaymentLineID in (Select PaymentLineID from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."' ))
                                                                                                                   ");
                        dd($s);/*
                                        $paymentLineId = '6386AFBE-1B41-4F81-93A6-815B5399F16B2222';
                                        $data['amount'] = '500';
                                        $connection->select("SELECT SCOPE_IDENTITY();EXEC Sp_Web_Odeme_8_trPaymentLineCurrency '".$paymentLineId."','".$data['amount']."'");
                                        $s = $connection->select("
                                                                                                                   SET NOCOUNT ON;
                                                                                                                   Select * from trPaymentLineCurrency Where PaymentLineID in (Select PaymentLineID from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."' ))
                                                                                                                   ");
                                        dd($s);
                                                $s = $connection->select("
                                                                                                                   SET NOCOUNT ON;
                                                                                                                   Select * from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."');
                                                                                                                   Select * from trPaymentLineCurrency Where PaymentLineID in (Select PaymentLineID from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."' ))
                                                                                                                   ");
                                                        dd($s);
                                                                                                        $s = $connection->select("
                                                                                                                   SET NOCOUNT ON;
                                                                                                                   Select * from trCreditCardPaymentHeader  where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$selectDate."';
                                                                                                                   Select * from trCreditCardPaymentLine where CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader  where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$selectDate."');
                                                                                                                   Select * from trCreditCardPaymentLineCurrency where CreditCardPaymentLineID in (Select  CreditCardPaymentLineID from trCreditCardPaymentLine where CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader where CurrAccCode = '".$customerCode."'  and PaymentDate = '".$selectDate."'));
                                                                                                                   select * from trCurrAccBook where CurrAccCode = '".$customerCode."' and DocumentDate ='".$selectDate."';
                                                                                                                   Select * from trCurrAccBookCurrency Where CurrAccBookID in (select CurrAccBookID from trCurrAccBook where CurrAccCode = '".$customerCode."' and DocumentDate ='".$selectDate."');
                                                                                                                   Select * from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."';
                                                                                                                   Select * from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."');
                                                                                                                   Select * from trPaymentLineCurrency Where PaymentLineID in (Select PaymentLineID from trPaymentLine Where PaymentHeaderID in (Select PaymentHeaderID from trPaymentHeader Where CurrAccCode = '".$customerCode."' and DocumentDate = '".$selectDate."' ))
                                                                                                                   ");
                                                                                                               dd($s);*/

        return view('priceList');
    }

    public function SPSave($amount)
    {
        $customerCode  = Auth::user()->customer_code;
        $connection    = DB::connection('sqlsrv');
        $kkRefNo       = $connection->select('SET NOCOUNT ON; exec sp_LastRefNumCreditCardPayment 1');
        $ccRefNo       = $kkRefNo[0]->CreditCardPaymentNumber;
        $date          = date('Y-m-d');
        $time          = date('H:i:s');
        $amount        = helpers::priceFormatCc($amount);

        // paymentHeader
            $creditCardPaymentHeaderID = TrCreditCardPaymentHeader::select('CreditCardPaymentHeaderID')->where('CurrAccCode',$customerCode)->where('CreditCardPaymentNumber',$ccRefNo)->first();

            if (empty($creditCardPaymentHeaderID))
            {
                $paymentHeader = $connection->insert("EXEC Sp_Web_Odeme_1_trCreditCardPaymentHeader '".$ccRefNo."','".$date."','".$time."','".$customerCode."'");

                if (!$paymentHeader)
                {
                    return $this->SPSave($amount);
                }
            }

            $creditCardPaymentHeaderID = TrCreditCardPaymentHeader::select('CreditCardPaymentHeaderID')->where('CurrAccCode',$customerCode)->where('CreditCardPaymentNumber',$ccRefNo)->first()->CreditCardPaymentHeaderID;

            Log::emergency('creditCardPaymentHeaderID',[$creditCardPaymentHeaderID.'- Müşteri Kodu :'.$customerCode]);
        // paymentHeader

        // paymentLine
            $creditCardPaymentLineID = TrCreditCardPaymentLine::select('CreditCardPaymentLineID')->whereRaw('CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader  where CurrAccCode = ? and CreditCardPaymentNumber = ?)',[$customerCode,$ccRefNo])->first();

            if (empty($creditCardPaymentLineID))
            {
                $paymentLine = $connection->insert("EXEC Sp_Web_Odeme_2_trCreditCardPaymentLine '".$creditCardPaymentHeaderID."','".$date."','1','".$amount."'");

                if (!$paymentLine)
                {
                    return $this->SPSave($amount);
                }
            }

            $creditCardPaymentLineID = TrCreditCardPaymentLine::select('CreditCardPaymentLineID')->whereRaw('CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader  where CurrAccCode = ? and CreditCardPaymentNumber = ?)',[$customerCode,$ccRefNo])->first()->CreditCardPaymentLineID;

            Log::emergency('creditCardPaymentLineID',[$creditCardPaymentLineID.'- Müşteri Kodu :'.$customerCode]);
        // paymentLine

        // ccPaymentLineCurrency
            if (!empty($creditCardPaymentLineID))
            {
                $ccPaymentLineCurrency = $connection->insert("EXEC Sp_Web_Odeme_3_trCreditCardPaymentLineCurrency '".$creditCardPaymentLineID."','".$amount."'");

                if (!$ccPaymentLineCurrency)
                {
                    return $this->SPSave($amount);
                }

                Log::emergency('ccPaymentLineCurrency',[$creditCardPaymentLineID.'- Müşteri Kodu :'.$customerCode]);
            }
        // ccPaymentLineCurrency

        // trCurrAccBook
            $currAccBookID = TrCurrAccBook::select('CurrAccBookID')->where('CurrAccCode',$customerCode)->whereRaw('ApplicationID in( Select CreditCardPaymentLineID from trCreditCardPaymentLine Where CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader where CurrAccCode = ? and CreditCardPaymentNumber = ?))',[$customerCode,$ccRefNo])->first();

            if (empty($currAccBookID))
            {
                $trCurrAccBook = $connection->insert("EXEC Sp_Web_Odeme_4_trCurrAccBook '".$customerCode."','".$date."','".$time."','".$creditCardPaymentLineID."'");

                if (!$trCurrAccBook)
                {
                    return $this->SPSave($amount);
                }
            }

            $currAccBookID = TrCurrAccBook::select('CurrAccBookID')->where('CurrAccCode',$customerCode)->whereRaw('ApplicationID in( Select CreditCardPaymentLineID from trCreditCardPaymentLine Where CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader where CurrAccCode = ? and CreditCardPaymentNumber = ?))',[$customerCode,$ccRefNo])->first()->CurrAccBookID;

            Log::emergency('currAccBookID',[$currAccBookID.'- Müşteri Kodu :'.$customerCode]);
        // trCurrAccBook

        // trCurrAccBookCurrency
            if (!empty($currAccBookID))
            {
                $trCurrAccBookCurrency = $connection->insert("EXEC Sp_Web_Odeme_5_trCurrAccBookCurrency '".$currAccBookID."','".$amount."'");

                if (!$trCurrAccBookCurrency)
                {
                    return $this->SPSave($amount);
                }

                Log::emergency('trCurrAccBookCurrency',[$currAccBookID.'- Müşteri Kodu :'.$customerCode]);
            }

        // trCurrAccBookCurrency

        // paymentNumber
            $paymentNumber  = $connection->select("SET NOCOUNT ON;exec sp_LastRefNumPayment 1,'YS','1-5-1',2")[0]->PaymentNumber;
            Log::emergency('PaymentNumber',[$paymentNumber.'- Müşteri Kodu :'.$customerCode]);
        // paymentNumber

        // trPaymentHeader
            $paymentHeaderID = TrPaymentHeader::select('PaymentHeaderID')->where('CurrAccCode',$customerCode)->where('PaymentNumber',$paymentNumber)->first();

            if (empty($paymentHeaderID))
            {
                $trPaymentHeader = $connection->insert("EXEC Sp_Web_Odeme_6_trPaymentHeader '".$date."','".$time."','".$paymentNumber."','".$customerCode."'");

                if (!$trPaymentHeader)
                {
                    return $this->SPSave($amount);
                }
            }

            $paymentHeaderID = TrPaymentHeader::select('PaymentHeaderID')->where('CurrAccCode',$customerCode)->where('PaymentNumber',$paymentNumber)->first()->PaymentHeaderID;

            Log::emergency('paymentHeaderID',[$paymentHeaderID.'- Müşteri Kodu :'.$customerCode]);
        // trPaymentHeader

        return
            [
                'paymentHeaderID'         => $paymentHeaderID,
                'creditCardPaymentLineID' => $creditCardPaymentLineID,
                'paymentNumber'           => $paymentNumber,
                'CreditCardPaymentNumber' => $ccRefNo
            ];
    }

    public function lastTwoSPSave($data,$status = null)
    {
        if ($status == 1)
        {
            $data['purchaseAmount'] = \App\helpers\helpers::priceFormat($data['purchaseAmount']);
            $data['purchaseAmount'] = str_replace('.00','',$data['purchaseAmount']);
        }
        else
        {
            $data['amounts'] = \App\helpers\helpers::mssqlPrice($data['amounts']);

            if ($data['getTotal'] < $data['amounts'] && $data['remainingAmount'] == 0)
            {
                $data['purchaseAmount']  = $data['getTotal'];
            }
            else
            {
                $data['purchaseAmount']  = $data['amounts'] - ($data['totalPrice'] - $data['getTotal']);

                if ($data['purchaseAmount']  == $data['getTotal'] )
                {
                    $data['purchaseAmount']  = $data['amounts'];
                }

                if ($data['getTotal'] > $data['totalPrice'])
                {
                    $data['purchaseAmount']  = $data['amounts'];
                }
            }

            $data['remainingAmount'] =  $data['getTotal'] - $data['amounts'];
            $data['purchaseAmount']  = \App\helpers\helpers::priceFormatCc($data['purchaseAmount']);
        }


        $orderPaymentPlanId = $data['OrderPaymentPlanID'];
        //print_r(' Ödenecek Tutar : '.$data['purchaseAmount'].' Kalan : '.$data['remainingAmount'].' Total:'.$data['totalPrice'].' Gelen Tutar:'.$data['getTotal'].' value price:'.$data['amounts'].' orderPaymentPlanId '.$orderPaymentPlanId.'------<br>');
        //$data['response']  .= 'Kalan Taksit1 : '.$data['purchaseAmount1'].' Ödenecek Tutar : '.$data['purchaseAmount'].' Kalan : '.$data['remainingAmount'].' Total:'.$data['totalPrice'].' Gelen Tutar:'.$data['getTotal'].' value price:'.$data['amounts'].' orderPaymentPlanId '.$orderPaymentPlanId.'------<br>';

        $connection           = DB::connection('sqlsrv');
        $sp7                  = $connection->insert("EXEC Sp_Web_Odeme_7_trPaymentLine '".$data['paymentHeaderID']."','".$data['creditCardPaymentLineID']."','".$orderPaymentPlanId."',".($data['order'] + 1));
        $paymentLineId        = TrPaymentLine::select('PaymentLineID')->whereRaw('PaymentHeaderID  in (Select PaymentHeaderID from trPaymentHeader Where PaymentNumber = ?  and CurrAccCode = ? )',[$data['paymentNumber'],$data['customerCode']])->orderBy('CreatedDate','desc')->first()->PaymentLineID;
        $sp8                  = $connection->insert("EXEC Sp_Web_Odeme_8_trPaymentLineCurrency '".$paymentLineId."','".$data['purchaseAmount']."'");
        $update               = $connection->update("update trCreditCardPaymentHeader set
            DocumentNumber = (Select PaymentNumber from trPaymentHeader Where PaymentNumber =  ?),
            ApplicationID = (Select PaymentHeaderID from trPaymentHeader Where PaymentNumber = ?)
            where CurrAccCode = ? and CreditCardPaymentNumber = ?",[$data['paymentNumber'],$data['paymentNumber'],$data['customerCode'],$data['CreditCardPaymentNumber']]);

        $paymentLineIdmsg[]   = $paymentLineId.'- Müşteri kodu : '.$data['customerCode'];
        $sp7e[]               = $sp7.'- Müşteri kodu : '.$data['customerCode'];
        $error[]              = $sp8.'- Müşteri kodu : '.$data['customerCode'];
        $updateErr[]          = $update.'- Müşteri kodu : '.$data['customerCode'];

        Log::emergency('PaymentLineID',$paymentLineIdmsg);
        Log::emergency('sp7',$sp7e);
        Log::emergency('sp8',$error);
        Log::emergency('update',$updateErr);

        return $data;
    }

    public function calcPrice(Request $request)
    {
        $price         = 0;
        $remainingDept = 0;
        $customerCode  = Auth::user()->customer_code;
        $priceList     = Cache::get('priceList'.$customerCode);
        $priceTotal    = 0;
        $moreMoneyWarn = null;

        if ($request->get('status') == 1 && is_array($request->get('monthYear')))
        {
            foreach ($request->get('monthYear') as $monthYear)
            {
                $price += $priceList[$monthYear]['price'];
            }

            foreach ($priceList as $value)
            {
                $priceTotal += $value['price'];
            }

            Cache::put('price'.$customerCode, $request->get('monthYear'), Carbon::now()->addMinutes(30));
            Cache::put('priceTotal'.$customerCode, $price, Carbon::now()->addMinutes(30));
            $remainingDept = $priceTotal - $price;
        }
        elseif ($request->get('status') == 2)
        {
            $price      = $request->get('price');

            foreach ($priceList as $value)
            {
                $priceTotal += $value['price'];
            }

            Cache::put('price'.$customerCode, $request->get('monthYear'), Carbon::now()->addMinutes(30));
            Cache::put('priceTotal'.$customerCode, $price, Carbon::now()->addMinutes(30));
            $remainingDept = $priceTotal - $price;
        }

        /*
        if ($price > $priceTotal)
        {
            $price         = $priceTotal;
            $remainingDept = 0;
            $moreMoneyWarn = array('Uyarı!','Ödeme tutarından fazla tutar girdiniz. Lütfen maksimum tutarı giriniz.');
        }
        */

        return json_encode(array('totalPrice' => helpers::priceFormat($price),'remainingDept' => helpers::priceFormat($remainingDept),'moreMoneyWarn' => $moreMoneyWarn));
    }

    public function payResult(Request $request)
    {
        $strMDStatus = $request->get('mdstatus');
        if ($strMDStatus == "1" || $strMDStatus == "2" || $strMDStatus == "3" || $strMDStatus == "4")
        {
            $strMode                  = $request->get('mode');
            $strVersion               = $request->get('apiversion');
            $strTerminalID            = $request->get('clientid');
            $strTerminalID_           = "0".$request->get('clientid');
            $strProvisionPassword     = "Beliz.3377"; //Terminal UserID �ifresi
            $strProvUserID            = $request->get('terminalprovuserid');
            $strUserID                = $request->get('terminaluserid');
            $strMerchantID            = $request->get('terminalmerchantid');
            $strIPAddress             = $request->get('customeripaddress');
            $strEmailAddress          = $request->get('customeremailaddress');
            $strOrderID               = $request->get('orderid');
            $strNumber                = ""; //Kart bilgilerinin bo� gitmesi gerekiyor
            $strExpireDate            = ""; //Kart bilgilerinin bo� gitmesi gerekiyor
            $strCVV2                  = ""; //Kart bilgilerinin bo� gitmesi gerekiyor
            $strAmount                = $request->get('txnamount');
            $strCurrencyCode          = $request->get('txncurrencycode');
            $strInstallmentCount      = $request->get('txninstallmentcount');
            $strCardholderPresentCode = "13"; //3D Model i�lemde bu de�er 13 olmal�
            $strType                  = $request->get('txntype');
            $strMotoInd               = "N";
            $strAuthenticationCode    = $request->get('cavv');
            $strSecurityLevel         = $request->get('eci');
            $strTxnID                 = $request->get('xid');
            $strMD                    = $request->get('md');
            $SecurityData             = strtoupper(sha1($strProvisionPassword.$strTerminalID_));
            $HashData                 = strtoupper(sha1($strOrderID.$strTerminalID.$strAmount.$SecurityData)); //Daha k�s�tl� bilgileri HASH ediyoruz.
            $strHostAddress           = "https://sanalposprov.garanti.com.tr/VPServlet"; //Provizyon i�in xml'in post edilece�i adres

            //Provizyona Post edilecek XML �ablonu

            $strXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <GVPSRequest>
                    <Mode>$strMode</Mode>
                    <Version>$strVersion</Version>
                    <ChannelCode></ChannelCode>
                    <Terminal>
                        <ProvUserID>$strProvUserID</ProvUserID>
                        <HashData>$HashData</HashData>
                        <UserID>$strUserID</UserID>
                        <ID>$strTerminalID</ID>
                        <MerchantID>$strMerchantID</MerchantID>
                        <SubMerchantID>1</SubMerchantID>
                    </Terminal>
                    <Customer>
                        <IPAddress>$strIPAddress</IPAddress>
                        <EmailAddress>$strEmailAddress</EmailAddress>
                    </Customer>
                    <Card>
                        <Number></Number>
                        <ExpireDate></ExpireDate>
                        <CVV2></CVV2>
                    </Card>
                    <Order>
                        <OrderID>$strOrderID</OrderID>
                        <GroupID></GroupID>
                        <AddressList><Address>
                        <Type>B</Type>
                        <Name></Name>
                        <LastName></LastName>
                        <Company></Company>
                        <Text></Text>
                        <District></District>
                        <City></City>
                        <PostalCode></PostalCode>
                        <Country></Country>
                        <PhoneNumber></PhoneNumber>
                        </Address></AddressList>
                    </Order>
                    <Transaction>
                        <Type>$strType</Type>
                        <InstallmentCnt>$strInstallmentCount</InstallmentCnt>
                        <Amount>$strAmount</Amount>
                        <CurrencyCode>$strCurrencyCode</CurrencyCode>
                        <CardholderPresentCode>$strCardholderPresentCode</CardholderPresentCode>
                        <MotoInd>$strMotoInd</MotoInd>
                        <Secure3D>
                            <AuthenticationCode>$strAuthenticationCode</AuthenticationCode>
                            <SecurityLevel>$strSecurityLevel</SecurityLevel>
                            <TxnID>$strTxnID</TxnID>
                            <Md>$strMD</Md>
                        </Secure3D>
                    </Transaction>

                    </GVPSRequest>";

            $ch=curl_init();
            curl_setopt($ch, CURLOPT_URL, $strHostAddress);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1) ;
            curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".$strXML);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $results = curl_exec($ch);
            curl_close($ch);

            //echo "<br /><b>Gelen Yanit </b><br />";
            $xmlObject = simplexml_load_string($results);
            $json      = json_encode($xmlObject);
            $data      = json_decode($json, true);

            $payResult                       = new PayResult();
            $payResult->name_surname         = is_array($data['Transaction']['CardHolderName']) ? null : $data['Transaction']['CardHolderName'];
            $payResult->card_number          = is_array($data['Transaction']['CardNumberMasked']) ? null : $data['Transaction']['CardNumberMasked'];
            $payResult->response_code        = $data['Transaction']['Response']['Code'];
            $payResult->error_message        = $data['Transaction']['Response']['Message'] ?? null;
            $payResult->error_message_title  = is_array($data['Transaction']['Response']['ErrorMsg']) ? null : $data['Transaction']['Response']['ErrorMsg'];
            $payResult->error_message_detail = is_array($data['Transaction']['Response']['SysErrMsg']) ? null : $data['Transaction']['Response']['SysErrMsg'];
            $payResult->hash_data            = $data['Transaction']['HashData'] ?? null;
            $payResult->amount               = $strAmount;
            $payResult->reference_no         = is_array($data['Transaction']['RetrefNum']) ? null : $data['Transaction']['RetrefNum'];
            $payResult->user_id              = Auth::user()->id;
            $payResult->md_status            = $strMDStatus;
            $payResult->ip_address           = $data['Customer']['IPAddress'];
            $payResult->save();

            if ($data['Transaction']['Response']['Code'] == '00')
            {
                //$amount = helpers::priceFormatCc($strAmount);
                $amount = $strAmount;
                $this->priceMssqlSave($amount);
                $data['link']= route('receipt',[$amount]);
            }

            $this->cacheClear();

            session()->flash('flash_message', $data);
        }

        return redirect()->route('price.list');
    }

    public function priceMssqlSave($amount)
    {
        $customerCode     = Auth::user()->customer_code;
        $sp               = $this->SPSave($amount);
        $priceList        = Cache::get('priceList'.$customerCode);
        $totalPrice       = 0;
        $order            = 0;
        $getTotal         = $amount;
        $lastTwoSPSave    =
            [
                'purchaseAmount'  => 0,
                'remainingAmount' => 0,
                'response'        => null,
            ];

        foreach ($priceList as $key => $value)
        {
            foreach ($value['amounts'] as $key2 => $value2)
            {
                if ($totalPrice < $getTotal)
                {
                    if ($order == 0)
                    {
                        $totalPrice = $value['amounts'][$key2];
                        $totalPrice = \App\helpers\helpers::mssqlPrice($totalPrice);
                    }
                    else
                    {
                        $totalPrice += \App\helpers\helpers::mssqlPrice($value['amounts'][$key2]);
                    }

                    $value3 =
                        [
                            'amounts'                 => $value['amounts'][$key2],
                            'OrderPaymentPlanID'      => $value['OrderPaymentPlanID'][$key2],
                            'order'                   => $order,
                            'getTotal'                => $getTotal,
                            'remainingAmount'         => $lastTwoSPSave['remainingAmount'],
                            'paymentHeaderID'         => $sp['paymentHeaderID'],
                            'creditCardPaymentLineID' => $sp['creditCardPaymentLineID'],
                            'paymentNumber'           => $sp['paymentNumber'],
                            'CreditCardPaymentNumber' => $sp['CreditCardPaymentNumber'],
                            'customerCode'            => $customerCode,
                            'response'                => $lastTwoSPSave['response'],
                            'totalPrice'              => $totalPrice,
                            'purchaseAmount'          => $lastTwoSPSave['purchaseAmount'],
                        ];

                    $lastTwoSPSave = $this->lastTwoSPSave($value3);
                    $order++;
                }
                else
                {
                    break;
                }
            }
        }
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
                return response()->json(
                    [
                        'result'  => 2,
                        'title'   => 'Hata!',
                        'message' => $validator->errors()
                    ],403
                );
            }

            $tc    = $request->get('tc');


            $phone = $this->tcDbControl($tc);

            if (!$phone)
            {
                return response()->json(
                    [
                        'result'  => 3,
                        'title'   => 'Hata!',
                        'message' => 'T.C Numarasını eksik ya da yanlış girdiniz. Lütfen tekrar deneyiniz.'
                    ],403
                );
            }
            else if(is_int($phone) && $phone == 2)
            {
                return response()->json(
                    [
                        'result'  => 3,
                        'title'   => 'Hata!',
                        'message' => "Sistemde Aynı Tc 1'den Fazla Var."
                    ],403
                );
            }



            $phoneNumber  = $phone->PhoneNumber;
            $customerCode = $phone->CustamerCode;
            $name         = $phone->FirstName;
            $surname      = $phone->LastName;
            $email        = $phone->EmailAddress;


            Cache::put('customerCode_'.csrf_token(), $customerCode, Carbon::now()->addMinutes(480));
            Cache::put('tc_'.csrf_token(), $tc, Carbon::now()->addMinutes(480));
            Cache::put('name_'.csrf_token(), $name, Carbon::now()->addMinutes(480));
            Cache::put('surname_'.csrf_token(), $surname, Carbon::now()->addMinutes(480));
            Cache::put('phone_'.csrf_token(), $phoneNumber, Carbon::now()->addMinutes(480));
            Cache::put('email_'.csrf_token(), $email, Carbon::now()->addMinutes(480));

            if (env('APP_SMS_CODE') == true)
            {
                $this->sendSms($tc,$phoneNumber,$customerCode,$name,$surname);
            }

            return response()->json(
                [
                    'result'  => 1,
                    'title'   => 'Başarılı!',
                    'message' => 'Şifre Gönderildi.',
                    'route'   => route('verification')
                ]
            );
            //return redirect()->route('verification');
        }
        catch (\Exception $e)
        {
            return response()->json(
                [
                    'result'  => 0,
                    'title'   => 'Hata!',
                    'message' => 'Lütfen tekrar deneyiniz.',
                    'route'   => route('index')
                ],403
            );
        }
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

            $customerCode = Cache::get('customerCode_'.csrf_token());
            $auth         = User::where('customer_code',$customerCode)->first();
            $tc           = Cache::get('tc_'.csrf_token());

            if (env('APP_SMS_CODE') == true)
            {
                $codeControl  = VerificationCodes::where('tc',$tc)->where('random_code',$request->get('verification_code'))->where('status',1)->first();

                if (empty($codeControl))
                {
                    Session::flash('message', array('Başarısız!','Hata! Doğrulama kodu geçersiz.', 'error'));
                    Log::emergency('verificationControl',[$tc,$request->get('verification_code'),csrf_token()]);
                    return redirect()->back();
                }


                if (empty($auth))
                {
                    $codeControl->status = 0;
                    $codeControl->save();

                    $auth                = new User;
                    $auth->tc            = $codeControl->tc;
                    $auth->phone_number  = $codeControl->phone_number;
                    $auth->name          = $codeControl->name;
                    $auth->surname       = $codeControl->surname;
                    $auth->email         = empty($codeControl->email) ?  Str::slug($codeControl->name, '_').'_'.Str::slug($codeControl->surnamname, '_').'@ugurluceyiz.com.tr' : $codeControl->email;
                    $auth->customer_code = $customerCode;
                    $auth->password      = bcrypt($customerCode.$codeControl->tc);
                    $auth->save();
                }

                $codeControl->status = 0;
                $codeControl->save();
            }
            else
            {
                if (empty($auth))
                {
                    $auth                = new User;
                    $auth->tc            = Cache::get('tc_'.csrf_token());
                    $auth->phone_number  = Cache::get('phone_'.csrf_token());
                    $auth->name          = Cache::get('name_'.csrf_token());
                    $auth->surname       = Cache::get('surname_'.csrf_token());
                    $auth->email         = empty(Cache::get('email_'.csrf_token())) ? Str::slug(Cache::get('name_'.csrf_token()), '_').'_'.Str::slug(Cache::get('surname_'.csrf_token()), '_').'@ugurluceyiz.com.tr' : Cache::get('email_'.csrf_token());
                    $auth->customer_code = $customerCode;
                    $auth->password      = bcrypt($customerCode.Cache::get('tc_'.csrf_token()));
                    $auth->save();
                }
            }

            //Auth::login($auth, true);

            $attempt = array('email' => $auth->email,'password' => $customerCode.$auth->tc);
            //dd($attempt);

            Auth::attempt($attempt,true);

            return redirect()->route('price.list');
        }
        catch (\Exception $e)
        {
            Log::emergency('verificationControlCatch',[$e]);
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
        if (env('APP_SMS_CODE') == true)
        {
            sms::send($phoneNumber,$message);
        }

        if (request('tc'))
        {
            Session::flash('message', array('Başarılı!','Şifre Gönderildi.', 'success'));
            Cache::put('customerCode_'.csrf_token(), $customerCode, Carbon::now()->addMinutes(480));
            Cache::put('tc_'.csrf_token(), $tc, Carbon::now()->addMinutes(480));

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
                ->get();

            if (count($phoneNumber) > 1)
            {
                return $phoneNumber = 2;
            }
            else if (count($phoneNumber) == 0)
            {
                return $phoneNumber = false;
            }

            Cache::put($cacheName, $phoneNumber[0], Carbon::now()->addMinutes(480));
            $phoneNumber = $phoneNumber[0];
        }

        return $phoneNumber ?? false;
    }

    public function receipt($amount)
    {
        $path                = 'assets/img/logo.png';
        $type                = pathinfo($path, PATHINFO_EXTENSION);
        $data                = file_get_contents($path);
        $logo                = 'data:image/' . $type . ';base64,' . base64_encode($data);
        $customerCode        = Auth::user()->customer_code;;
        $customerNameSurname = Auth::user()->name.' '.Auth::user()->surname;
        $today               = date('d.m.Y');
        $hour                = date('H:i');
        $priceListCacheName  = 'priceList'.$customerCode;
        $priceList           = Cache::get($priceListCacheName);
        $totalPriceCacheName = 'totalPrice'.$customerCode;
        $priceListHtml       = '';
        $totalPrice          = Cache::get($totalPriceCacheName);
        $totalPrice          = \App\helpers\helpers::priceFormat($totalPrice);
        $amount              = env('APP_TEST')  ? \App\helpers\helpers::priceFormat($amount) :  \App\helpers\helpers::priceFormatCc($amount);

        foreach ($priceList as $key => $value)
        {
            $priceListHtml .=
                '
                    <tr>
                        <td>
                            <p style="text-align: left;font-size: 13px;margin-top: 10px;"> '.$value['year'].' </p>
                        </td>
                        <td style="padding-left: 10px">
                            <p style="text-align: left;font-size: 13px;margin-top: 10px;"> '.$value['month'].'</p>
                        </td>
                        <td>
                            <p style="text-align: right;font-size: 13px;margin-top: 10px;"> '.\App\helpers\helpers::priceFormat($value['price']).' ₺</p>
                        </td>
                    </tr>
                ';
        }

        $pdf = App::make('dompdf.wrapper');
        $receiptHtml =
            '
                    <style>
                        body
                        {
                            font-family: DejaVu Sans;
                        }

                        table.priceType,table.priceType  td,table.priceType th
                        {
                            border: 1px solid #a5a5a5;
                            border-collapse: collapse;
                        }
                        table.priceType tr:first-child td,
                        table.priceType tr td:first-child,
                        {
                            padding-left: 10px
                        }

                        table.priceType tr td,
                        {
                            padding-right: 10px
                        }

                    </style>
                    <title>Uğurlu Çeyiz Ödeme Dekontu</title>
                    <table width="100%" style="margin-top: -50px;border-bottom: 1px solid #a5a5a5;">
                        <tbody>
                            <tr>
                                <td>
                                    <img src="'.$logo.'" height="45" style="margin-top: 10px">
                                    <p style="text-align: center;font-size: 15px;font-weight: bold;margin-top: 10px;">Tahsilat Fişi</p>
                                    <p style="text-align: center;margin-top: -10px;">'.$today.'</p>
                                </td>
                                <td>
                                    <p style="text-align: left;font-size: 10px;padding-left: 5px">
                                        Merkez : Atatürk Bulvarı No:31 <br>
                                        Şube 1 : Atatürk Bulvarı No:23 <br>
                                        Şube 2 : Çukur Mah. Mehmet Uygun Cd. No:25/C <br>
                                        Şube 3 : Şahintepe Mah. 394 Nolu Cd. No:25/C <br>
                                        Şube 4 : Sarıgüllük Mah. Ünler Blv  No:12/B <br>
                                        Şube 5 : 60. Yıl Mah. Yavuz Sultan Selim Cd. <br>
                                        Şube 6 : Pirsultan Mah. Lefkoşe Cad. No:212 <br>
                                    </p>
                                </td>
                                <td>
                                    <p style="text-align: center;font-size: 20px;font-weight: bold;">
                                        Müşteri Hizmetleri
                                    </p>
                                    <p style="text-align: center;font-size: 20px;font-weight: bold;">
                                        444 0 943
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table>
                        <tbody>
                            <tr>
                                <td width="425">
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> <strong> Müşteri Kodu : </strong> '.$customerCode.'</p>
                                    <p style="text-align: left;font-size: 12px;margin-top: -10px;"> <strong> Müşteri Adı &nbsp;&nbsp;&nbsp;: </strong> '.$customerNameSurname.'</p>
                                </td>
                                <td>
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> <strong> Tarih : </strong> '.$today.' </p>
                                    <p style="text-align: left;font-size: 12px;margin-top: -10px;"> <strong> Saat&nbsp; : </strong> '.$hour.' </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="priceType" width="100%">
                        <tbody>
                            <tr>
                                <td>
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> <strong> Ödeme Tipi Açıklaması </strong> </p>
                                </td>
                                <td>
                                    <p style="text-align: center;font-size: 12px;margin-top: 10px;"> <strong> Ödeme Tutarı </strong> </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> Kredi Kartı </p>
                                </td>
                                <td>
                                    <p style="text-align: right;font-size: 12px;margin-top: 10px;"> '.$amount.' ₺</p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1">
                                    <p style="text-align: right;font-size: 12px;margin-top: 10px;font-weight: bold"> Toplam :</p>
                                </td>
                                <td>
                                    <p style="text-align: right;font-size: 12px;margin-top: 10px;font-weight: bold"> '.$amount.' ₺</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="priceType" width="100%" style="margin-top: 20px">
                        <tbody>
                            <tr>
                                <td colspan="3">
                                    <p style="text-align: center;font-size: 16px;margin-top: 10px;"> <strong> Kalan Taksit Dökümü Aşağıdadır </strong> </p>
                                </td>
                            </tr>
                            '.$priceListHtml.'
                            <tr>
                                <td colspan="2">
                                    <p style="text-align: right;font-size: 15px;margin-top: 10px;padding-right: 10px"> <strong>Kalan Bakiye : </strong> </p>
                                </td>
                                <td>
                                    <p style="text-align: right;font-size: 12px;margin-top: 10px;"> <strong>'.$totalPrice.' ₺</strong></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p style="font-size: 10px;font-style: italic;font-weight: bold;color: #dd0815;width: 100%;text-align: right">* Bu belge internet sitesinden verilmiştir.</p>
                ';
        $pdf->loadHTML($receiptHtml)->setPaper('a4');
        return $pdf->stream('ugurlu-ceyiz-odeme-dekontu.pdf');
    }

    public function logout(Request $request)
    {
        $this->cacheClear();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush();
        return redirect()->route('index');
    }

    public function cacheClear()
    {
        $customerCode        = Auth::user()->customer_code;
        $tc                  = Auth::user()->tc;

        Cache::forget('customerCode_'.csrf_token());
        Cache::forget('tc_'.csrf_token());
        Cache::forget('name_'.csrf_token());
        Cache::forget('surname_'.csrf_token());
        Cache::forget('phone_'.csrf_token());
        Cache::forget('email_'.csrf_token());
        Cache::forget('remainder'.$customerCode);
        Cache::forget('priceList'.$customerCode);
        Cache::forget('totalPrice'.$customerCode);
        Cache::forget('price'.$customerCode);
        Cache::forget('tcControl'.$tc);
    }
}

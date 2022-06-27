<?php

namespace App\Http\Controllers;

use App\helpers\helpers;
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
        if (Cache::has('tc'))
        {
            $tc      =  Cache::get('tc');
            $name    =  Cache::get('name');
            $surName =  Cache::get('surname');
            $phone   =  Cache::get('phone');
            $phone   =  helpers::hiddenPhone($phone);

            return view('verification',compact('name','surName','phone','tc'));
        }

        return redirect()->route('index');
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
        $data['cardexpiredateyear']   = trim($expiry[1]);
        $data['cardcvv2']             = $request->get('cvc');
        $data['secure3dsecuritylevel']= '3D';

        if ($request->has('price'))
        {
            $priceList = Cache::get('priceList'.Auth::user()->customer_code);
            $price     = 0;
            foreach ($request->get('price') as $monthYear)
            {
                $price += $priceList[$monthYear]['price'];
            }

            Cache::put('price', $request->get('price'), Carbon::now()->addMinutes(30));
            Cache::put('priceTotal', $price, Carbon::now()->addMinutes(30));
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
        $amount        = helpers::totalPriceFormat($request->get('total'));
        $customerCode  = Auth::user()->customer_code;
        $sP            = $this->SPSave($amount);
        $priceTotal    = str_replace([',','.'],['',''],Cache::get('priceTotal'));

        $payResult                       = new PayResult();
        $payResult->name_surname         = null;
        $payResult->card_number          = null;
        $payResult->response_code        = '00';
        $payResult->error_message        = null;
        $payResult->error_message_title  = null;
        $payResult->error_message_detail = null;
        $payResult->hash_data            = null;
        $payResult->amount               = $amount;
        $payResult->reference_no         = null;
        $payResult->user_id              = Auth::user()->id;
        $payResult->md_status            = '00';
        $payResult->ip_address           = $request->ip();
        $payResult->save();

        if (Cache::has('price') && $amount == $priceTotal)
        {
            $price            = Cache::get('price');
            $priceList        = Cache::get('priceList'.$customerCode);
            $totalPrice       = 0;
            $order            = 0;
            $getTotal         = 0;
            $response         =
                [
                    'purchaseAmount1' => 0,
                    'purchaseAmount'  => 0,
                    'remainingAmount' => 0,
                    'response'        => null,
                ];

            foreach ($price as $key => $value)
            {
                foreach ($priceList[$value]['amounts'] as $key2 => $value2)
                {
                    $value3 =
                        [
                            'amounts'                 => $priceList[$value]['amounts'][$key2],
                            'OrderPaymentPlanID'      => $priceList[$value]['OrderPaymentPlanID'][$key2],
                            'order'                   => $order,
                            'getTotal'                => $getTotal,
                            'remainingAmount'         => $response['remainingAmount'],
                            'paymentHeaderID'         => $sP['paymentHeaderID'],
                            'creditCardPaymentLineID' => $sP['creditCardPaymentLineID'],
                            'paymentNumber'           => $sP['paymentNumber'],
                            'CreditCardPaymentNumber' => $sP['CreditCardPaymentNumber'],
                            'customerCode'            => $customerCode,
                            'response'                => $response['response'],
                            'totalPrice'              => $totalPrice,
                            'purchaseAmount1'         => $response['purchaseAmount1'],
                            'purchaseAmount'          => $priceList[$value]['amounts'][$key2],
                        ];

                    $this->lastTwoSPSave($value3,1);
                }
            }
        }
        else
        {
            $priceList        = Cache::get('priceList'.$customerCode);
            $totalPrice       = 0;
            $order            = 0;
            $getTotal         = $amount;
            $response         =
                [
                    'purchaseAmount1' => 0,
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
                        $value3 =
                            [
                                'amounts'                 => $value['amounts'][$key2],
                                'OrderPaymentPlanID'      => $value['OrderPaymentPlanID'][$key2],
                                'order'                   => $order,
                                'getTotal'                => $getTotal,
                                'remainingAmount'         => $response['remainingAmount'],
                                'paymentHeaderID'         => $sP['paymentHeaderID'],
                                'creditCardPaymentLineID' => $sP['creditCardPaymentLineID'],
                                'paymentNumber'           => $sP['paymentNumber'],
                                'CreditCardPaymentNumber' => $sP['CreditCardPaymentNumber'],
                                'customerCode'            => $customerCode,
                                'response'                => $response['response'],
                                'totalPrice'              => $totalPrice,
                                'purchaseAmount1'         => $response['purchaseAmount1'],
                                'purchaseAmount'          => $response['purchaseAmount'],
                            ];

                        $this->lastTwoSPSave($value3);
                        $totalPrice += $value['amounts'][$key2];
                        $order++;
                    }
                    else
                    {
                        break;
                    }
                }

            }
        }

        $data['link'] = route('receipt',[$amount]);
        $data['Transaction']['Response']['Code'] = 0;
        Cache::flush();
        session()->flash('flash_message', $data);
        return redirect()->route('price.list');
    }

    public function priceList()
    {
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

        // paymentHeader
        $paymentHeader        = $connection->insert("EXEC Sp_Web_Odeme_1_trCreditCardPaymentHeader '".$ccRefNo."','".$date."','".$time."','".$customerCode."'");
        $paymentHeaderControl = new TrCreditCardPaymentHeader;
        $paymentHeaderControl->setConnection('sqlsrv');
        $creditCardPaymentHeaderID = $paymentHeaderControl->select('CreditCardPaymentHeaderID')->where('CurrAccCode',$customerCode)->where('CreditCardPaymentNumber',$ccRefNo)->first()->CreditCardPaymentHeaderID;
        // paymentHeader

        // paymentLine
        $paymentLine        = $connection->insert("EXEC Sp_Web_Odeme_2_trCreditCardPaymentLine '".$creditCardPaymentHeaderID."','".$date."','1','".$amount."'");
        $paymentLineControl = new TrCreditCardPaymentLine;
        $paymentLineControl->setConnection('sqlsrv');
        $creditCardPaymentLineID = $paymentLineControl->select('CreditCardPaymentLineID')->whereRaw('CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader  where CurrAccCode = ? and CreditCardPaymentNumber = ?)',[$customerCode,$ccRefNo])->first()->CreditCardPaymentLineID;
        // paymentLine

        // ccPaymentLineCurrency
        $connection->insert("EXEC Sp_Web_Odeme_3_trCreditCardPaymentLineCurrency '".$creditCardPaymentLineID."','".$amount."'");
        // ccPaymentLineCurrency

        // trCurrAccBook
        $trCurrAccBook        = $connection->insert("EXEC Sp_Web_Odeme_4_trCurrAccBook '".$customerCode."','".$date."','".$time."','".$creditCardPaymentLineID."'");
        $trCurrAccBookControl = new TrCurrAccBook;
        $trCurrAccBookControl->setConnection('sqlsrv');
        $currAccBookID        = $trCurrAccBookControl->select('CurrAccBookID')->where('CurrAccCode',$customerCode)->whereRaw('ApplicationID in( Select CreditCardPaymentLineID from trCreditCardPaymentLine Where CreditCardPaymentHeaderID in (Select CreditCardPaymentHeaderID from trCreditCardPaymentHeader where CurrAccCode = ? and CreditCardPaymentNumber = ?))',[$customerCode,$ccRefNo])->first()->CurrAccBookID;
        // trCurrAccBook

        // trCurrAccBookCurrency
        $connection->insert("EXEC Sp_Web_Odeme_5_trCurrAccBookCurrency '".$currAccBookID."','".$amount."'");
        // trCurrAccBookCurrency

        // paymentNumber
        $paymentNumber  = $connection->select("SET NOCOUNT ON;exec sp_LastRefNumPayment 1,'YS','1-5-1',2")[0]->PaymentNumber;
        // paymentNumber

        // trPaymentHeader
        $trPaymentHeader        = $connection->insert("EXEC Sp_Web_Odeme_6_trPaymentHeader '".$date."','".$time."','".$paymentNumber."','".$customerCode."'");
        $trPaymentHeaderControl = new TrPaymentHeader;
        $trPaymentHeaderControl->setConnection('sqlsrv');
        $paymentHeaderID = $trPaymentHeaderControl->select('PaymentHeaderID')->where('CurrAccCode',$customerCode)->where('PaymentNumber',$paymentNumber)->first()->PaymentHeaderID;
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
            $data['amounts'] = \App\helpers\helpers::priceFormat($data['amounts']);
            $data['amounts'] = str_replace(['.00',','],['',''],$data['amounts']);

            if ($data['order'] == 0)
            {
                if ($data['getTotal'] < $data['amounts'])
                {
                    $data['purchaseAmount1'] = $data['amounts'] - $data['getTotal'];
                    $data['purchaseAmount']  = $data['amounts'] - $data['purchaseAmount1'];
                    $data['remainingAmount'] = $data['purchaseAmount1'];
                }
                else
                {
                    $data['purchaseAmount1'] = $data['getTotal'] - $data['amounts'];
                    $data['purchaseAmount']  = $data['getTotal'] - $data['purchaseAmount1'];
                    $data['remainingAmount'] = $data['purchaseAmount1'];
                }
            }
            else if ($data['remainingAmount'] > $data['amounts'])
            {
                $data['purchaseAmount1'] = $data['remainingAmount'] - $data['amounts'];
                $data['purchaseAmount']  = $data['amounts'];
                $data['remainingAmount'] = $data['purchaseAmount1'] ;
            }
            else
            {
                $data['purchaseAmount']  = $data['remainingAmount'];
            }
        }

        $orderPaymentPlanId = $data['OrderPaymentPlanID'];
        $data['response']  .= 'Kalan Taksit1 : '.$data['purchaseAmount1'].' Ödenecek Tutar : '.$data['purchaseAmount'].' Kalan : '.$data['remainingAmount'].' Total:'.$data['totalPrice'].' Gelen Tutar:'.$data['getTotal'].' value price:'.$data['amounts'].' orderPaymentPlanId '.$orderPaymentPlanId.'------<br>';

        $connection           = DB::connection('sqlsrv');
        $sp7                  = $connection->insert("EXEC Sp_Web_Odeme_7_trPaymentLine '".$data['paymentHeaderID']."','".$data['creditCardPaymentLineID']."','".$orderPaymentPlanId."',".($data['order'] + 1));
        $sp7e[]               = $sp7;
        $trPaymentLineControl = new TrPaymentLine();
        $trPaymentLineControl->setConnection('sqlsrv');
        $paymentLineId        = $trPaymentLineControl->select('PaymentLineID')->whereRaw('PaymentHeaderID  in (Select PaymentHeaderID from trPaymentHeader Where PaymentNumber = ?  and CurrAccCode = ? )',[$data['paymentNumber'],$data['customerCode']])->orderBy('CreatedDate','desc')->first()->PaymentLineID;
        $sp8                  = $connection->insert("EXEC Sp_Web_Odeme_8_trPaymentLineCurrency '".$paymentLineId."','".$data['purchaseAmount']."'");
        $update               = $connection->update("update trCreditCardPaymentHeader set
            DocumentNumber = (Select PaymentNumber from trPaymentHeader Where PaymentNumber =  ?),
            ApplicationID = (Select PaymentHeaderID from trPaymentHeader Where PaymentNumber = ?)
            where CurrAccCode = ? and CreditCardPaymentNumber = ?",[$data['paymentNumber'],$data['paymentNumber'],$data['customerCode'],$data['CreditCardPaymentNumber']]);

        $paymentLineIdmsg[]   = $paymentLineId;
        $error[]              = $sp8;
        $updateErr[]          = $update;
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
        $priceList     =  Cache::get('priceList'.Auth::user()->customer_code);
        $priceTotal    = 0;

        if ($request->get('status') == 1)
        {
            foreach ($request->get('monthYear') as $monthYear)
            {
                $price += $priceList[$monthYear]['price'];
            }

            foreach ($priceList as $value)
            {
                $priceTotal += $value['price'];
            }

            Cache::put('price', $request->get('monthYear'), Carbon::now()->addMinutes(30));
            Cache::put('priceTotal', $price, Carbon::now()->addMinutes(30));
            $remainingDept = $priceTotal - $price;
        }
        elseif ($request->get('status') == 2)
        {
            $price      = $request->get('price');

            foreach ($priceList as $value)
            {
                $priceTotal += $value['price'];
            }

            Cache::put('price', $request->get('monthYear'), Carbon::now()->addMinutes(30));
            Cache::put('priceTotal', $price, Carbon::now()->addMinutes(30));
            $remainingDept = $priceTotal - $price;
        }

        return json_encode(array('totalPrice' => helpers::priceFormat($price),'remainingDept' => helpers::priceFormat($remainingDept)));
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
                $customerCode  = Auth::user()->customer_code;
                $amount        = helpers::priceFormatCc($strAmount);
                $sP            = $this->SPSave($amount);
                $priceTotal    = str_replace([',','.'],['',''],Cache::get('priceTotal'));

                if (Cache::has('price') && $amount == $priceTotal)
                {
                    $price            = Cache::get('price');
                    $priceList        = Cache::get('priceList'.$customerCode);
                    $totalPrice       = 0;
                    $order            = 0;
                    $getTotal         = 0;
                    $response         =
                        [
                            'purchaseAmount1' => 0,
                            'purchaseAmount'  => 0,
                            'remainingAmount' => 0,
                            'response'        => null,
                        ];

                    foreach ($price as $key => $value)
                    {
                        foreach ($priceList[$value]['amounts'] as $key2 => $value2)
                        {
                            $value3 =
                                [
                                    'amounts'                 => $priceList[$value]['amounts'][$key2],
                                    'OrderPaymentPlanID'      => $priceList[$value]['OrderPaymentPlanID'][$key2],
                                    'order'                   => $order,
                                    'getTotal'                => $getTotal,
                                    'remainingAmount'         => $response['remainingAmount'],
                                    'paymentHeaderID'         => $sP['paymentHeaderID'],
                                    'creditCardPaymentLineID' => $sP['creditCardPaymentLineID'],
                                    'paymentNumber'           => $sP['paymentNumber'],
                                    'CreditCardPaymentNumber' => $sP['CreditCardPaymentNumber'],
                                    'customerCode'            => $customerCode,
                                    'response'                => $response['response'],
                                    'totalPrice'              => $totalPrice,
                                    'purchaseAmount1'         => $response['purchaseAmount1'],
                                    'purchaseAmount'          => $priceList[$value]['amounts'][$key2],
                                ];

                            $this->lastTwoSPSave($value3,1);
                        }
                    }
                }
                else
                {
                    $priceList        = Cache::get('priceList'.$customerCode);
                    $totalPrice       = 0;
                    $order            = 0;
                    $getTotal         = $amount;
                    $response         =
                        [
                            'purchaseAmount1' => 0,
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
                                $value3 =
                                    [
                                        'amounts'                 => $value['amounts'][$key2],
                                        'OrderPaymentPlanID'      => $value['OrderPaymentPlanID'][$key2],
                                        'order'                   => $order,
                                        'getTotal'                => $getTotal,
                                        'remainingAmount'         => $response['remainingAmount'],
                                        'paymentHeaderID'         => $sP['paymentHeaderID'],
                                        'creditCardPaymentLineID' => $sP['creditCardPaymentLineID'],
                                        'paymentNumber'           => $sP['paymentNumber'],
                                        'CreditCardPaymentNumber' => $sP['CreditCardPaymentNumber'],
                                        'customerCode'            => $customerCode,
                                        'response'                => $response['response'],
                                        'totalPrice'              => $totalPrice,
                                        'purchaseAmount1'         => $response['purchaseAmount1'],
                                        'purchaseAmount'          => $response['purchaseAmount'],
                                    ];

                                $this->lastTwoSPSave($value3);
                                $totalPrice += $value['amounts'][$key2];
                                $order++;
                            }
                            else
                            {
                                break;
                            }
                        }

                    }
                }

                $data['link']= route('receipt',[$amount]);
            }

            Cache::flush();

            session()->flash('flash_message', $data);
        }

        return redirect()->route('price.list');
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


            Cache::flush();
            Cache::put('customerCode', $customerCode, Carbon::now()->addMinutes(480));
            Cache::put('tc', $tc, Carbon::now()->addMinutes(480));
            Cache::put('name', $name, Carbon::now()->addMinutes(480));
            Cache::put('surname', $surname, Carbon::now()->addMinutes(480));
            Cache::put('phone', $phoneNumber, Carbon::now()->addMinutes(480));
            Cache::put('email', $email, Carbon::now()->addMinutes(480));

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

            $customerCode = Cache::get('customerCode');
            $auth         = User::where('customer_code',$customerCode)->first();

            if (env('APP_SMS_CODE') == true)
            {
                $codeControl  = VerificationCodes::where('tc',Cache::get('tc'))->where('random_code',$request->get('verification_code'))->where('status',1)->first();

                if (empty($codeControl))
                {
                    Session::flash('message', array('Başarısız!','Hata! Doğrulama kodu geçersiz.', 'error'));
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }

                $codeControl->status = 0;
                $codeControl->save();

                if (empty($auth))
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

                    $auth                = new User;
                    $auth->tc            = $codeControl->tc;
                    $auth->phone_number  = $codeControl->phone_number;
                    $auth->name          = $codeControl->name;
                    $auth->surname       = $codeControl->surname;
                    $auth->email         = empty($codeControl->email) ? $codeControl->name.$codeControl->surnamname.'@ugurluceyiz.com.tr' : $codeControl->email;
                    $auth->customer_code = $customerCode;
                    $auth->password      = bcrypt($customerCode.$codeControl->tc);
                    $auth->save();
                }
            }
            else
            {
                if (empty($auth))
                {
                    $auth                = new User;
                    $auth->tc            = Cache::get('tc');
                    $auth->phone_number  = Cache::get('phone');
                    $auth->name          = Cache::get('name');
                    $auth->surname       = Cache::get('surname');
                    $auth->email         = empty(Cache::get('email')) ? Cache::get('name').Cache::get('surname').'@ugurluceyiz.com.tr' : Cache::get('email');
                    $auth->customer_code = $customerCode;
                    $auth->password      = bcrypt($customerCode.Cache::get('tc'));
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
                                        Merkez : Atatürk Bulvarı No:31 Gaziantep <br>
                                        Şube 1 : Atatürk Bulvarı No:23 Gaziantep <br>
                                        Şube 2 : Çukur Mah. Mehmet Uygun Cd. No:25/C Gaziantep <br>
                                        Şube 3 : Şahintepe Mah. 394 Nolu Cd. No:25/C Gaziantep <br>
                                        Şube 4 : Sarıgüllük Mah. Ünler Blv  No:12/B Gaziantep <br>
                                        Şube 5 : 60. Yıl Mah. Yavuz Sultan Selim Cd. Gaziantep <br>
                                    </p>
                                </td>
                                <td>
                                    <p style="text-align: center;font-size: 20px;font-weight: bold;">
                                        Müşteri Hizmetleri
                                    </p>
                                    <p style="text-align: center;font-size: 20px;">
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
                                    <p style="text-align: left;font-size: 12px;margin-top: -10px;"> <strong> Müşteri Adı : </strong> '.$customerNameSurname.'</p>
                                </td>
                                <td>
                                    <p style="text-align: center;font-size: 12px;margin-top: 10px;"> <strong> Tarih : </strong> '.$today.'</p>
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
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> <strong> Tutar </strong> </p>
                                </td>
                                <td>
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> <strong> Tutar (Y) </strong> </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="text-align: left;font-size: 12px;margin-top: 10px;"> Kredi Kartı </p>
                                </td>
                                <td>
                                    <p style="text-align: right;font-size: 12px;margin-top: 10px;"> '.$amount.' ₺</p>
                                </td>
                                <td>
                                    <p style="text-align: right;font-size: 12px;margin-top: 10px;"> '.$amount.' ₺</p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
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
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Cache::flush();
        $request->session()->flush();
        return redirect()->route('index');
    }
}

<?php


	namespace App\helpers;


	use Akaunting\Money\Money;
    use App\Models\VerificationCodes;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Str;

    class helpers
	{
        public static function orderNumber()
        {
            return rand(1, 1000000000);
        }

        public static function randomNumberCode()
        {
            return rand(1, 1000000);
        }

        public static function verificationCodeMessage($tc,$phoneNumber,$customerCode,$name,$surname)
        {
            $randomCode                = self::randomNumberCode();
            $message                   = 'Odeme sistemine giris icin dogrulama kodunuz : '.$randomCode;
            $randomCodeControl         = VerificationCodes::where('random_code',$randomCode)->first();
            $phoneNumber               = self::editPhoneNumber($phoneNumber);

            if ($randomCodeControl)
            {
                return self::verificationCodeMessage($tc,$phoneNumber,$customerCode);
            }
            else
            {

                $verification                = new VerificationCodes;
                $verification->tc            = $tc;
                $verification->phone_number  = $phoneNumber;
                $verification->customer_code = $customerCode;
                $verification->random_code   = $randomCode;
                $verification->name          = $name;
                $verification->surname       = $surname;
                $verification->save();

                /*
                    $verification['tc']           = $tc;
                    $verification['phone_number'] = $phoneNumber;
                    $verification['random_code']  = $randomCode;
                    VerificationCodes::create($verification);
                */
            }

            return $message;
        }

        public static function editPhoneNumber($phoneNumber)
        {
            if (!$phoneNumber) { return false; }
            return Str::of($phoneNumber)->ltrim('0');
        }

        public static function priceFormat($str,$status = null)
        {
            if ($status == 1)
            {
                $price = number_format($str, 2, '.', ',');
                return self::totalPriceFormat($price);
            }
            else if ($status == 2)
            {
                $price     = number_format($str, 2, '.', ',');
                $newAmount = str_replace(['.',','], ['',''],$price);

                return $newAmount;
            }
            else if ($status == 3)
            {
                $newAmount = str_replace(['.',','], ['',''],$str);

                return $newAmount;
            }


            return number_format($str, 2, '.', ',');
        }

        public static function totalPriceFormat($total)
        {
            $price    = Money::TRY($total)->formatSimple();
            $newPrice = str_replace(['.',','], ['',''],$price);

            return $newPrice;
        }

        public static function mssqlPrice($price)
        {
            $newAmountControl = substr($price,-2);

            if ($newAmountControl == 00)
            {
                $newAmount = substr($price,0,-2);
                $newAmount = str_replace(['.',','], ['',''],$newAmount);
            }
            else
            {
                $newAmount = $price;
            }

            return $newAmount;
        }

        public static function priceFormatCc($str,$status = null)
        {
            if (empty($str))
            {
                return 0;
            }

            $price = $str;

            $price = Money::TRY($price)->formatSimple();

            if ($status == 1)
            {
                $newPrice = str_replace('.', '',$price);
                $newPrice = str_replace(',', '.',$newPrice);
                //$newPrice = str_replace('.', '',$price);
            }
            else
            {
                $newPrice = str_replace(['.',','], [',','.'],$price);
            }

            return $newPrice;

            //return number_format($newPrice, 2, '.', ',');
        }

        public static function hiddenPhone($phone)
        {
            $phone = self::editPhoneNumber($phone);
            $phone = Str::substr($phone,-4);
            $phone = '******'.$phone;
            return $phone;
        }

        public static function nameAndSurname($str)
        {
            $nameAndSurname      = explode(' ',$str);
            $fullName['name']    = $nameAndSurname[0];
            $fullName['surname'] = $nameAndSurname[1];

            if (isset($nameAndSurname[2]))
            {
                $fullName['name']    = $nameAndSurname[0].' '.$nameAndSurname[1];
                $fullName['surname'] = $nameAndSurname[2];
            }

            return (object)$fullName;
        }

        public static function curlSend($url,$method,$data)
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $data,
            ));

            $response = curl_exec($curl);
            return $response;
            curl_close($curl);
            return $response;
        }
	}

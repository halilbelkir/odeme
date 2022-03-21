<?php


	namespace App\helpers;


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

        public static function priceFormat($str)
        {
            return number_format($str, 2, '.', ',');
        }

        public static function totalPriceFormat($total)
        {
            $newTotal = str_replace([',','.'], ['',''],$total);
            return rtrim($newTotal,'00');
        }

        public static function priceFormatCc($str)
        {
            if (empty($str))
            {
                return 0;
            }

            $price = rtrim($str,'00');
            return number_format($price, 2, '.', ',');
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
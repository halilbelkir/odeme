<?php


	namespace App\helpers;


	use App\Models\VerificationCodes;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Str;

    class helpers
	{
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

        public static function noktasiz($veri){

            $veri = str_replace(".","",$veri);
            $veri = str_replace(",","",$veri);
            echo $veri;
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
	}

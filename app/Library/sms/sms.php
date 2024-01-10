<?php


	namespace App\Library\sms;


	use DOMDocument;

    class sms
	{
	    static $apiKey    = '5603567218';
        static $apiSecret = '43213ca4bb1f447cbd4a6ea48336f2f2';

	    public static function curlSend($url,$method,$postfields)
        {
            $curl      = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $postfields,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: text/xml',
                    'Authorization: Basic ODQ2MzEwOTI1Mjo='
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        }

        public static function send($numbers = null,$message = null)
        {
            $url       = 'https://secure.mobilus.net/soapv3/API.asmx';
            $postfields = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
                           <soapenv:Header/>
                           <soapenv:Body>
                              <tem:SMSToManyAPIKey>
                                 <tem:apikey>'.self::$apiKey.'</tem:apikey>
                                 <tem:apisecret>'.self::$apiSecret.'</tem:apisecret>
                                 <tem:message>'.$message.'</tem:message>
                                 <tem:numbers>'.$numbers.'</tem:numbers>
                                 <tem:messageType>N</tem:messageType>
                              </tem:SMSToManyAPIKey>
                           </soapenv:Body>
                        </soapenv:Envelope>';

            $response = self::curlSend($url,'POST',$postfields);

            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->loadXML( $response );
            $statusCode = $doc->getElementsByTagName("StatusCode")->item(0)->nodeValue;
            $statusText = $doc->getElementsByTagName("StatusText")->item(0)->nodeValue;
            $return     = array('status' => $statusCode, 'message' => $statusText);
            return $return;
        }
	}

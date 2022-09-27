<?php
namespace Pay\WithAtPay\Model;

class Connect implements \Pay\WithAtPay\Api\ConnectInterface
{

    public function connect($name)

    {
        $response = ['success' => false];
        try
        {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            if (!isset($_SERVER['PHP_AUTH_USER']))
            {
                echo "you need proper credentials";
                exit;
            }
            else if (($_SERVER['PHP_AUTH_USER'] && ($_SERVER['PHP_AUTH_PW'])))
            {
                $cURL = curl_init();
                $setopt_array = array(
                    CURLOPT_URL => "http://atpay-api-lb-2068155291.ap-southeast-2.elb.amazonaws.com/api/webhook/storestatusupdate",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
            "status":"connected",
            "type":"Magento" ,  
            "store_url":"http://atpay-magento.kinectro.com"  
}',
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Authorization: Basic ' . base64_encode($username . ":" . $password) ,
                    )
                );
                curl_setopt_array($cURL, $setopt_array);
                $json_response_data = curl_exec($cURL);
                print_r($json_response_data);
                curl_close($cURL);
                return "Hello, " . $name;
            }
            else
            {
                echo "you need proper credentials";
            }
        }
        catch(e)
        {
            $response = ['success' => false, 'message' => $e->getMessage() ];
        }
    }
    
}

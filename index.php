<?php

require_once ('./helper.php');

session_start();

date_default_timezone_set('Asia/Dhaka');

$MerchantID = "683002007104225";
$DateTime = Date('YmdHis');
$amount = "100.00";
$OrderId = 'TEST'.strtotime("now").rand(1000, 10000);
$random = generateRandomString();


$PostURL = "http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/check-out/initialize/" . $MerchantID . "/" . $OrderId;

// $PostURL = "https://sandbox-ssl.mynagad.com/api/dfs/check-out/initialize/" . $MerchantID . "/" . $OrderId;

$_SESSION['orderId'] = $OrderId;

$merchantCallbackURL = "http://127.0.0.1/nagad_projects/Nagad_Integration-eCommerce-PHP/merchant-callback-website.php";



$SensitiveData = array(
    'merchantId' => $MerchantID,
    'datetime' => $DateTime,
    'orderId' => $OrderId,
    'challenge' => $random
);
// var_dump($SensitiveData);exit;
$PostData = array(
    'dateTime' => $DateTime,
    'sensitiveData' => EncryptDataWithPublicKey(json_encode($SensitiveData)),
    'signature' => SignatureGenerate(json_encode($SensitiveData))
);
// echo '////////';
// var_dump($PostData);
// echo '<br/>';
// echo $PostURL;
$Result_Data = HttpPostMethod($PostURL, $PostData);

// var_dump($Result_Data);
// exit;

if (isset($Result_Data['sensitiveData']) && isset($Result_Data['signature'])) {
    if ($Result_Data['sensitiveData'] != "" && $Result_Data['signature'] != "") {

        $PlainResponse = json_decode(DecryptDataWithPrivateKey($Result_Data['sensitiveData']), true);

        // var_dump($PlainResponse); exit;
        if (isset($PlainResponse['paymentReferenceId']) && isset($PlainResponse['challenge'])) {


            $paymentReferenceId = $PlainResponse['paymentReferenceId'];


            $randomServer = $PlainResponse['challenge'];

            // $SensitiveDataOrder = array(
            //     'merchantId' => $MerchantID,
            //     'orderId' => $OrderId,
            //     'currencyCode' => '050',
            //     'amount' => $amount,
            //     'challenge' => $randomServer,
            //     'otherAmount' => array(
            //         'serviceFee' => '2.50'
            //     )
                   
            // );
            // $amount = '100.00';
            $gatewayFee = '10.00';
            $SensitiveDataOrder = array(
                'merchantId' => $MerchantID,
                'orderId' => $OrderId,
                'amount' => $amount + $gatewayFee,
                'currencyCode' => '050',
                'amount' => $amount + $gatewayFee,
                'challenge' => $randomServer
                   
            );   

            
            $logo = "https://my-brand.be/wp-content/uploads/2021/08/my-brand-logo.jpg";
            
            $merchantAdditionalInfo = '{"serviceName":"Brand Name", "serviceLogoURL": "https://img.freepik.com/premium-vector/simple-letter-m-y-logo-design_304830-170.jpg" , "additionalFieldNameEN":"My Charge", "additionalFieldNameBN":"আমার চার্জ", "additionalFieldValue":"BDT '.$gatewayFee.'"}';

            $PostDataOrder = array(
                'sensitiveData' => EncryptDataWithPublicKey(json_encode($SensitiveDataOrder)),
                'signature' => SignatureGenerate(json_encode($SensitiveDataOrder)),
                'merchantCallbackURL' => $merchantCallbackURL,
                // 'additionalMerchantInfo' => json_decode($merchantAdditionalInfo)
            );

                      
            $OrderSubmitUrl = "http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/check-out/complete/" . $paymentReferenceId;
            // $OrderSubmitUrl = "https://sandbox-ssl.mynagad.com/api/dfs/check-out/complete/" . $paymentReferenceId;

            // var_dump($PostDataOrder);
            // echo '<br/>';
            // echo $OrderSubmitUrl; exit; 
            $Result_Data_Order = HttpPostMethod($OrderSubmitUrl, $PostDataOrder);
            // var_dump($Result_Data_Order); exit;
            
                if ($Result_Data_Order['status'] == "Success") {
                    $url = json_encode($Result_Data_Order['callBackUrl']);   
                    echo "<script>window.open($url, '_self')</script>";  
                            
                }
                else {
                    echo json_encode($Result_Data_Order);
                     
                }
        } else {
            echo json_encode($PlainResponse);
                
        }
    }
}



?>
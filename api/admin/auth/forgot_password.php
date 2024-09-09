<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\Mail_SMS_Responses;
use Config\API_User_Response;
$allowedDomainNames=Constants::BASE_URL;
$apimethod="POST";
// set seconds you want the API response cache to expire and revalidated
// set to 0 if not needed
$expiredata=['hr'=>0,'min'=>0,'sec'=>60];
$totalexpiresec=($expiredata['hr'] * 60 * 60) + ($expiredata['min'] * 60) + $expiredata['sec'];
$expirationTime = time() + $totalexpiresec ; // time + seconds * minute * hour
// limit access to resources to only those domains trusted, 
header("Access-Control-Allow-Origin: $allowedDomainNames");
//Indicate that the content being sent or received is in JSON format and encoded using the UTF-8 character encoding
header("Content-Type: application/json; charset=UTF-8");
// 1)private,max-age=60 (browser is only allowed to cache) 2)no-store(public),max-age=60 (all intermidiary can cache, not browser alone)  3)no-cache (no ceaching at all)  
// comment below if you need to allow caching on this api
header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
// header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
// $utility_class_call->setTimeZoneForUser('');
// header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call= new Config\API_Status_Code;
$db_call_class= new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try{
        $verifytype = 3;
        $email = isset($_POST['email']) ? $utility_class_call->clean_user_data($_POST['email']) : '';// 1 whatsapp/sms/email 2-Call
        $getuserattached= $db_call_class->selectRows("admin", "email, id", [[
            ['column' => 'email', 'operator' => '=', 'value' => $email]]
        ]);

        $medthodis=1;
        $getoldotpsent=[];

        if(!$utility_class_call->input_is_invalid($getuserattached)){
            $userdata=$getuserattached[0];
            $userid = $userdata['id'];
            // checking if sms is sent is not older than 1 min
            $getoldotpsent= $db_call_class->selectRows("system_otps", "created_at", [[
                ['column' => 'TIMESTAMPDIFF(MINUTE, created_at, NOW())', 'operator' => '<', 'value' => 1],
                ['column' => 'user_id', 'operator' => '=', 'value' => $userid],
                'operator'=>'AND']
            ]);
        }

        // send error if ur is not in the database
        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$userNotFound);
        } else if (!$utility_class_call->input_is_invalid($getoldotpsent)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$otpsentalready);
        }  else{
            $useridentity = "";
            $user_id = $userdata['id'];
            $user_email = $userdata['email'];
            $useridentity = $user_email;
            
            // set expireTime of the token to 5 minutes
            $expiresin = 5;
            // $length, $tablename, $tablecolname, $tokentag, $addnumbers, $addcapitalletters, $addsmalllletters
            $otp = $db_call_class->createUniqueRandomStringForATableCol(4, "system_otps", "otp", "", true, false, false);
            // generating  token
            // $length, $tablename, $tablecolname, $tokentag, $addnumbers, $addcapitalletters, $addsmalllletters
            $token = $db_call_class->createUniqueRandomStringForATableCol(18, "system_otps", "token", "", true, true, true);
            $status=0;
            $createdOtpId=$db_call_class->insertRow("system_otps", ["user_id" => $user_id, "useridentity" => $useridentity, 'token' => $token,'verification_type' => $verifytype, "otp" => $otp, 'forwho' => 2,'method_used' => $medthodis,'status' => $status]);
            if ($createdOtpId>0){
                $sent=Mail_SMS_Responses::sendMailOTP($user_id, $otp, $token, $user_email);
                if ($sent) {
                    // update status as sent
                    $db_call_class->updateRows("system_otps", ["status" => 1], [[
                        ['column' => 'id', 'operator' => '=', 'value' => $createdOtpId]]
                    ]);

                    # code...
                    $maindata['sentto'] = $user_email;
                    $maindata=[$maindata];
                    $text=API_User_Response::$emailotpSentSuccessfully;
                    $api_status_code_class_call->respondOK($maindata,$text);
                }else {
                    $api_status_code_class_call->respondInternalError(API_User_Response::$errorSendingMail,API_User_Response::$errorSendingMail);
                }
            } else{
                $api_status_code_class_call->respondInternalError($createdOtpId);
            }      
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
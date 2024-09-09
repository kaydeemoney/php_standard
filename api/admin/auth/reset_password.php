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
        $token = isset($_POST['token']) ? $utility_class_call->clean_user_data($_POST['token']) : '';
        $otp = isset($_POST['otp']) ? $utility_class_call->clean_user_data($_POST['otp']) : 0;
        $newpassword = isset($_POST['newpassword']) ? $utility_class_call->clean_user_data($_POST['newpassword']) : '';
        // checking if sms is sent is not older than 5 min
        $getoldotpsent= $db_call_class->selectRows("system_otps", "user_id", [[
            ['column' => 'TIMESTAMPDIFF(MINUTE, created_at, NOW())', 'operator' => '<', 'value' => 5],
            ['column' => 'forwho', 'operator' => '=', 'value' => 2],
            ['column' => 'verification_type', 'operator' => '=', 'value' => 3],
            'operator' => 'AND'],[
            ['column' => 'token', 'operator' => '=', 'value' => $token],
            ['column' => 'otp', 'operator' => '=', 'value' => $otp],
            'operator' => 'OR']
        ]);
        
        // send error if ur is not in the database
       if($utility_class_call->input_is_invalid($token)&&$utility_class_call->input_is_invalid($otp)){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } if($utility_class_call->input_is_invalid($newpassword)){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }else  if ($utility_class_call->input_is_invalid($getoldotpsent)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidOtporExpired);
        } else{
            $otpdata=$getoldotpsent[0];
            $user_id = $otpdata['user_id'];
            $getuserattached= $db_call_class->selectRows("admin", "id, email", [[
                ['column' =>'id', 'operator' =>'=', 'value' =>$user_id]]
            ]);

            if ($utility_class_call->input_is_invalid($getuserattached)){
                $api_status_code_class_call->respondUnauthorized();
            }else{
                $email=$getuserattached[0]['email'];
                $password=$utility_class_call->Password_encrypt($newpassword);
                $updateddate= $db_call_class->updateRows("admin", ["password" => $password], [[
                    ['column' =>'id', 'operator' =>'=', 'value' =>$user_id]]
                ]);

                if ($updateddate) {
                    $maindata=[];
                    $text=API_User_Response::$data_updated;
                    $api_status_code_class_call->respondOK($maindata,$text);
                }else {
                    $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                }
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
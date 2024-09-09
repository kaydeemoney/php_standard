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
        #Get Post Data
        $email = isset($_POST['email']) ? $utility_class_call->clean_user_data($_POST['email'], 1) : ''; //email or username
        $password = isset($_POST['password']) ? $utility_class_call->clean_user_data($_POST['password'], 1) : '';

        $responseData = $db_call_class->selectRows("admin", "id, email, password, adminpubkey, status", [[
            ['column' => 'email', 'operator' => '=', 'value' => $email]]
        ]);

        if ($utility_class_call->input_is_invalid($email) || $utility_class_call->input_is_invalid($password)) {
            //     checking if data is empty
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } elseif ($utility_class_call->input_is_invalid($responseData)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidUserDetail);
        } elseif (Constants::ALLOW_USER_TO_LOGIN_REGISTER==0) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$serverUnderMaintainance);
        } else{
            $found = $responseData[0];
            $user_id = $found['id'];
            $dash_mail = $found['email'];
            $pass = $found['password'];
            $userPubkey = $found['adminpubkey'];
            $statusis = $found['status'];
            $banreason = 'You have been Banned';

            //verify the new password with the db pass
            $verifypass = $utility_class_call->is_password_hash_valid($password, $pass);
            if ($verifypass) {
                if ($statusis==1) {
                    $maindata=[];
                    $ipaddress= $utility_class_call->getIpAddress();
                    
                    // saving user login session
                    $seescode = $db_call_class->createUniqueRandomStringForATableCol(20, "userloginsessionlog", "sessioncode", time(), true, true, true);
                    $browser = ' '.$utility_class_call->getBrowserInfo()['name'].' on '.ucfirst($utility_class_call->getBrowserInfo()['platform']);
                    $location='';
                    //Put sessioncode inside database
                    $db_call_class->insertRow("userloginsessionlog", ["email" => $email,'sessioncode' => $seescode, 'ipaddress' => $ipaddress, 'browser' => $browser, 'username' => ' ', 'forwho' => 2, 'location' => $location]);
                    // generating user access token
                    $tokentype = 1;
                    $accesstoken = $api_status_code_class_call->getTokenToSendAPI($userPubkey, $tokentype);
                    $maindata['access_token'] = $accesstoken;
                
                    $maindata=[$maindata];
                    $text=API_User_Response::$loginSuccessful;
                    $api_status_code_class_call->respondOK($maindata,$text);
                    
                }  elseif ($statusis==0) {//banned
                    $api_status_code_class_call->respondBadRequest($banreason);
                }else {
                    $api_status_code_class_call->respondBadRequest(API_User_Response:: $user_permanetly_banned);
                }
            } else {
                $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidUserDetail);
            }
            
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
$api_status_code_class_call->respondMethodNotAlowed();
}


?>
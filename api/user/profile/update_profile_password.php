<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\API_User_Response;

$allowedDomainNames = Constants::BASE_URL;
$apimethod = "POST";
// set seconds you want the API response cache to expire and revalidated
// set to 0 if not needed
$expiredata = ['hr' => 0, 'min' => 0, 'sec' => 60];
$totalexpiresec = ($expiredata['hr'] * 60 * 60) + ($expiredata['min'] * 60) + $expiredata['sec'];
$expirationTime = time() + $totalexpiresec; // time + seconds * minute * hour
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

$api_status_code_class_call = new Config\API_Status_Code;
$db_call_class = new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try {
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached = $db_call_class->selectRows("users", "password,city_tid", [[
            ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey],]
        ]);

        $currentpassword= isset($_POST['old_password']) ? $utility_class_call->clean_user_data($_POST['old_password'],1) : '';
        $newpassword= isset($_POST['new_password']) ? $utility_class_call->clean_user_data($_POST['new_password'],1) : '';


        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        } else if ($utility_class_call->input_is_invalid($currentpassword)||$utility_class_call->input_is_invalid($newpassword)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }  else if (!$utility_class_call->isPasswordStrong($newpassword)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidPassword);
        } else {
            $userdata=$getuserattached[0];
            $oldHashedPass = $userdata['password'];
            $city_tid= $userdata['city_tid'];
            $selectData = $db_call_class->selectRows("countries_cities", "time_zone_name", [[
                ['column' => 'trackid', 'operator' => '=', 'value' =>$city_tid],]
            ]);
            if (!$utility_class_call->input_is_invalid($selectData)) {
                $usertimezone=$selectData[0]['time_zone_name'];
                $utility_class_call->setTimeZoneForUser($usertimezone);
            }else{
                $utility_class_call->setTimeZoneForUser('');
            }


            if (!$utility_class_call->is_password_hash_valid($currentpassword, $oldHashedPass)){
                $api_status_code_class_call->respondBadRequest(API_User_Response::$oldpasswordIncorrect);
            }else if($utility_class_call->is_password_hash_valid($newpassword, $oldHashedPass)){
                $api_status_code_class_call->respondBadRequest(API_User_Response::$newpasswordCanNotBeOld);
            }else{
                    $password=$utility_class_call->Password_encrypt($newpassword);
                    $timeis= date("Y-m-d h:i:s");
                    $updateddate= $db_call_class->updateRows("users",['lastpassupdate'=>$timeis,'password'=>$password],
                    [  
                        [
                            ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey]
                        ]
                    ] );
                    if ($updateddate) {
                        $text = API_User_Response::$data_updated;
                        $maindata = [];
                        $api_status_code_class_call->respondOK($maindata, $text);
                    } else {
                        $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record, API_User_Response::$error_updating_record);
                    }
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

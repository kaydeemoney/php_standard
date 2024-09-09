<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1, whocalled: 1);
        $user_pubkey = $decodedToken->usertoken;
        [$getuserattached] = $db_call_class->selectRows("users", "trackid", [[
            ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);
        
        $address = isset($_POST['address']) ? $utility_class_call->clean_user_data($_POST['address']) : '';
        $description = isset($_POST['description']) ? $utility_class_call->clean_user_data($_POST['description']) : '';
        $job_title = isset($_POST['job_title']) ? $utility_class_call->clean_user_data($_POST['job_title']) : '';
        $time= isset($_POST['time']) ? $utility_class_call->clean_user_data($_POST['time']) : ''; 
        $date = isset($_POST['date']) ? $utility_class_call->clean_user_data($_POST['date']) : ''; 
        $trackid =isset($_POST['trackid']) ? $utility_class_call->clean_user_data($_POST['trackid']) : ''; 
      
        if ($utility_class_call->input_is_invalid($address) || $utility_class_call->input_is_invalid($description) ||  $utility_class_call->input_is_invalid($job_title) ||   $utility_class_call->input_is_invalid($time)||  $utility_class_call->input_is_invalid($date)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } else   if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        } else{
            $user_tid = $getuserattached['trackid'];
        
            $insertData= $db_call_class->updateRows("bookings", ["title"=>$job_title,'address' => $address,'description' => $description,"the_time"=>$time,"the_date"=>$date], 
            [[
                ['column' => 'trackid', 'operator' => '=', 'value' => $trackid],
                ['column' => 'user_tid', 'operator' => '=', 'value' => $user_tid]
            ]]);
            
            if($insertData > 0){
                $text=API_User_Response::$data_updated;
                $maindata=[];
                $api_status_code_class_call->respondOK($maindata, $text);
            }else{
                $api_status_code_class_call->respondInternalError(API_User_Response::$error_creating_record,API_User_Response::$error_creating_record);
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
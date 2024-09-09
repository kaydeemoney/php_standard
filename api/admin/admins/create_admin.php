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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken:1,whocalled:2);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached= $db_call_class->selectRows("admin", "id,email", [[
            ['column' =>'adminpubkey', 'operator' =>'=', 'value' =>$user_pubkey]]
        ]);
     
        $name = isset($_POST['name']) ? $utility_class_call->clean_user_data($_POST['name']) : '';
        $email = isset($_POST['email']) ? $utility_class_call->clean_user_data($_POST['email'],1) : '';
        $password = isset($_POST['password']) ? $utility_class_call->clean_user_data($_POST['password']) : '';
        $level = isset($_POST['level']) ? $utility_class_call->clean_user_data($_POST['level']) : ''; // 1 super admin
        $status = isset($_POST['status']) ? $utility_class_call->clean_user_data($_POST['status']) : '';

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else if ( $utility_class_call->input_is_invalid($name) || $utility_class_call->input_is_invalid($email) || $utility_class_call->input_is_invalid($password) || $utility_class_call->input_is_invalid($level)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }elseif ($db_call_class->checkIfRowExistAndCount('admin', [[
                ['column' => 'email', 'operator' => '=', 'value' => $email],
            ]
        ]) > 0) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Email"));
        } elseif ( !$utility_class_call->isEmailValid($email) ) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Email"));
        }else{
            $trackid = $db_call_class->createUniqueRandomStringForATableCol(5, "admin", "trackid", "", true, true, false);  
            $adminpubkey = $db_call_class->createUniqueRandomStringForATableCol(26, "admin", "adminpubkey", "", true, true, true);
            $password = $utility_class_call->Password_encrypt($password); 
            $insertData = $db_call_class->insertRow("admin", ["name" => $name, 'trackid' => $trackid, 'email' => $email, 'password' => $password,'adminpubkey' => $adminpubkey, 'userlevel' => $level, 'status' => $status]);
            if($insertData > 0){
                $text = API_User_Response::$data_created;
                $maindata = [];
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
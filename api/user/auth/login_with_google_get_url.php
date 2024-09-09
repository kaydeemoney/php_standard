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

            $systemData =$db_call_class->selectRows("allapicredentials","baseurl,public_key,private_key",
            [   
                [
                    ['column' =>'provider', 'operator' =>'=', 'value' =>4],
                    ['column' =>'status', 'operator' =>'=', 'value' =>1],
                    'operator'=>'AND'
                ]
            ],['limit'=>1]);
            if (!$utility_class_call->input_is_invalid($systemData)) {
                $systemData = $systemData[0];
                $client = new Google_Client();
                $client->setClientId($systemData['public_key']);
                $client->setClientSecret($systemData['private_key']);
                $client->setRedirectUri($systemData['baseurl']);
                $client->addScope('email');
                $client->addScope('profile');
                

                $login_url = $client->createAuthUrl();
                $maindata['url']=$login_url;
                $maindata=[$maindata];
                $text=API_User_Response::$data_found;
                $api_status_code_class_call->respondOK($maindata,$text);
            }else{
                $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
            }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
$api_status_code_class_call->respondMethodNotAlowed();
}


?>
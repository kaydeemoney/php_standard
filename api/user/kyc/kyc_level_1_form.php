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
        $country_tid = isset($_POST['country_tid']) ? $utility_class_call->clean_user_data($_POST['country_tid']) : '';
        $state_tid = isset($_POST['state_tid']) ? $utility_class_call->clean_user_data($_POST['state_tid']) : '';
        $city_tid = isset($_POST['city_tid']) ? $utility_class_call->clean_user_data($_POST['city_tid']) : '';
        $address = isset($_POST['address']) ? $utility_class_call->clean_user_data($_POST['address']) : '';
        $gender = isset($_POST['gender']) ? $utility_class_call->clean_user_data($_POST['gender']) : '';// 1 for male 2 female 3 amaphrodite
        $dob = isset($_POST['dob']) ? $utility_class_call->clean_user_data($_POST['dob']) : '';
        $pin = isset($_POST['pin']) ? $utility_class_call->clean_user_data($_POST['pin']) : '';
      
        $whichtoken = 1;
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: $whichtoken);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached= $db_call_class->selectRows("users", "id, pinadded, pin, fname, lname, email, phoneno", [[
                ['column' =>'userpubkey', 'operator' =>'=', 'value' => $user_pubkey]]
        ]);

        $getcountryData= $db_call_class->selectRows("countries", "id", [[
            ['column' => 'trackid', 'operator' => '=', 'value' => $country_tid]]
        ]);

        $getStateData= $db_call_class->selectRows("countries_states", "id, postal_code", [[
            ['column' => 'trackid', 'operator' => '=', 'value' => $state_tid],
            ['column' => 'country_tid', 'operator' => '=', 'value' => $country_tid],
            'operator'=> 'AND']
        ]);

        $getCityData= $db_call_class->selectRows("countries_cities", "id, time_zone_name", [[
            ['column' =>'trackid', 'operator' =>'=', 'value' =>$city_tid],
            ['column' =>'state_tid', 'operator' =>'=', 'value' =>$state_tid],
            'operator'=>'AND']
        ]);

        // print("$country_tid, $state_tid,$city_tid")
        // send error if ur is not in the database
        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else if($utility_class_call->input_is_invalid($country_tid) || $utility_class_call->input_is_invalid($state_tid) ||$utility_class_call->input_is_invalid($city_tid) || $utility_class_call->input_is_invalid($address) ||$utility_class_call->input_is_invalid($gender)||$utility_class_call->input_is_invalid($dob)||$utility_class_call->input_is_invalid($pin) || $utility_class_call->input_is_invalid($getcountryData) || $utility_class_call->input_is_invalid($getStateData) ||$utility_class_call->input_is_invalid($getCityData) || !is_numeric($gender)){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } else if (strlen($address)<25){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalid_house_address);
        } else if(strlen($pin) >4 || strlen($pin) <4 || !is_numeric($pin)){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalid_pin);
        } else{
            $userdata=$getuserattached[0];
            $userid = $userdata['id'];
            $pinadded = $userdata['pinadded'];
            $oldpin = $userdata['pin'];
            $fname = $userdata['fname'];
            $lname = $userdata['lname'];
            $fullname = $lname.' '.$fname;
            $email = $userdata['email'];
            $phoneno = $userdata['phoneno'];
            
            $citydata = $getCityData[0];
            $timeZoneToUse = $citydata['time_zone_name'];

            $statedata = $getStateData[0];
            $postalcode = $statedata['postal_code'];

            $pinadded=false;

            if($pinadded==1&&!$utility_class_call->input_is_invalid($oldpin)){
                $pinadded=true;
            }else{
                $pin=$utility_class_call->Password_encrypt($pin);
                $utility_class_call->setTimeZoneForUser($timeZoneToUse);
                $timedone=date('Y-m-d H:i:s');
                $updateddate= $db_call_class->updateRows("users", ["pin" => $pin, "lastpinupdate" => $timedone], [[
                    ['column' => 'id', 'operator' => '=', 'value' => $userid]]
                ] );

                if ($updateddate) {
                    $pinadded=true;
                }else {
                    $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                }
            }

            if($pinadded){
                $updateddate= $db_call_class->updateRows("users",["state_tid"=>$state_tid,'country_id'=>$country_tid,'city_tid'=>$city_tid,'address1'=>$address,'sex'=>$gender], [[
                    ['column' => 'id', 'operator' => '=', 'value' => $userid]]
                ] );

                if ($updateddate) {
                    // create KYC data tzble if not created before
                    $getuserkycdata= $db_call_class->selectRows("user_kyc_data", "id", [[
                        ['column' =>'user_id', 'operator' => '=', 'value' => $userid]]
                    ]);

                    if ($utility_class_call->input_is_invalid($getuserkycdata)){
                        // create
                        $InsertResponseData =$db_call_class->insertRow("user_kyc_data", ['user_id' => $userid,'identity_number' => ' ', 'fblink' => ' ', 'twitterlink' => ' ', 'telegram' => ' ', 'instagram' => ' ', 'business_type' => 0, 'business_cc' => ' ', 'passport' => ' ', 'front_regcard' => ' ', 'back_regcard' => ' ', 'reg_type' =>0, 'fname'=>$fname, 'lname'=>$lname, 'middlename'=>' ', 'fullname'=>$fullname, 'title'=>' ', 'email'=>$email, 'phoneno'=>$phoneno, 'dob'=>$dob, 'full_address'=>$address, 'state_tid'=>$state_tid, 'country_tid'=>$country_tid, 'status'=>0, 'adminseen'=>0, 'json'=>' ', 'gender'=>$gender, 'postalcode'=>$postalcode, 'city_tid'=>$city_tid, 'house_number'=>' ', 'reg_id_number'=>' ', 'utility_bills'=>' ']);
                    }
                    
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
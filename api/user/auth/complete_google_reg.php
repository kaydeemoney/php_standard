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
        $countrytid= isset($_POST['country_tid']) ? $utility_class_call->clean_user_data($_POST['country_tid']) : 'UKYT';
        $phone= isset($_POST['phone']) ? $utility_class_call->clean_user_data($_POST['phone']) : '';
        $firstname = isset($_POST['firstname']) ? $utility_class_call->clean_user_data($_POST['firstname']) : '';
        $lastname = isset($_POST['lastname']) ? $utility_class_call->clean_user_data($_POST['lastname']) : '';
        $username = isset($_POST['username']) ? $utility_class_call->clean_user_data($_POST['username'],1) : '';
        $referedby = isset($_POST['referedby']) ? $utility_class_call->clean_user_data($_POST['referedby']) : '';
        $password = isset($_POST['password']) ? $utility_class_call->clean_user_data($_POST['password']) : '';

      
        $whichtoken=1;
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken:$whichtoken);
        $user_pubkey = $decodedToken->usertoken;

        $getuserattached= $db_call_class->selectRows("users", "id,username", 
        [   
            [
                ['column' =>'userpubkey', 'operator' =>'=', 'value' =>$user_pubkey],
                ['column' =>'register_via_google', 'operator' =>'=', 'value' =>1],
                
            ]
        ]
        );
        // check if the number + it country code exist and also if number exist without country code
        $minpnovalue=0;
        $phonecode="";
        $max_pno_value=0;
        $remove_first_no=0;
        $requestData =$db_call_class->selectRows("countries","min_pno_value,max_pno_value,remove_first_no,phonecode",
        [   
            [
                ['column' =>'trackid', 'operator' =>'=', 'value' =>$countrytid]
            ]
        ]
        );
        if (!$utility_class_call->input_is_invalid($requestData)) {
            $requestData = $requestData[0];
            $minpnovalue=$requestData['min_pno_value'];
            $max_pno_value=$requestData['max_pno_value'];
            $remove_first_no=$requestData['remove_first_no'];
            $phonecode=$requestData['phonecode'];
            $phonecode=str_replace("+","",$phonecode);
        }

        // removing country code
        $phoneNumber = $phone;//+2349061962412 or 2349061962412
        $pattern = "/^(\+?$phonecode|0)?(\d{{$minpnovalue},{$max_pno_value}})$/";
        if($remove_first_no==1){
            $replacement = '0$2';
        }else{
            $replacement = '$2';
        }
        $newPhoneNumber = preg_replace($pattern, $replacement, $phoneNumber);// add 0 in front once you remove the country code 09061962412
        $replacement = '$2';
        $newPhoneNumber2 = preg_replace($pattern, $replacement, $phoneNumber);// just the phone number 9061962412


        // print("$country_tid, $state_tid,$city_tid")
        // send error if ur is not in the database
        if ($utility_class_call->input_is_invalid($getuserattached)){
                $api_status_code_class_call->respondUnauthorized();
        }else if($utility_class_call->input_is_invalid($countrytid)||$utility_class_call->input_is_invalid($phone)|| $utility_class_call->input_is_invalid($firstname) || $utility_class_call->input_is_invalid($lastname) || $utility_class_call->input_is_invalid($username)||$utility_class_call->input_is_invalid($password) ){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }  elseif (strlen($firstname)>25|| $utility_class_call->textHasEmojis($firstname)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::lengthError('First Name',25));
        } elseif (strlen($lastname)>25|| $utility_class_call->textHasEmojis($lastname)) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::lengthError('Last Name',25));
        } elseif (!$utility_class_call->isPasswordStrong($password)) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidPassword);
        } elseif (strlen($username)>25) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::lengthError('Username',25));
        }elseif (!$utility_class_call->isUsernameValid($username)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidUsername);
        } else  if ($referedby != ""&&!$utility_class_call->input_is_invalid($referedby)&&$referedby!="undefined"&&$db_call_class->checkIfRowExistAndCount('users',
        [   
            [
                ['column' =>'refcode', 'operator' =>'=', 'value' =>$referedby]

            ]
        ]
        )==0) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Referral Code"));
        } elseif ($db_call_class->checkIfRowExistAndCount('countries',
        [   
            [
                ['column' =>'trackid', 'operator' =>'=', 'value' =>$countrytid],
            ]
        ]
        )==0) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Country"));
        } elseif (strlen($phone)>$max_pno_value||strlen($phone)<$minpnovalue||!is_numeric($phone)) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::lengthMinMaxError('Phone Number',$max_pno_value,$minpnovalue));
        }  elseif ($db_call_class->checkIfRowExistAndCount('users',
        [   
            [
                ['column' =>'phoneno', 'operator' =>'=', 'value' =>$phone],
                ['column' =>'phoneno', 'operator' =>'=', 'value' =>$newPhoneNumber],
                ['column' =>'phoneno', 'operator' =>'=', 'value' =>$newPhoneNumber2],
                'operator'=>'OR'
            ],
            [
                ['column' =>'userpubkey', 'operator' =>'!=', 'value' =>$user_pubkey]
            ]
        ]
        )>0) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Phone Number"));
        }  elseif ($db_call_class->checkIfRowExistAndCount('users',
        [   
            [
                ['column' =>'username', 'operator' =>'=', 'value' =>$username],
            ]
        ])>0) {// checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Username"));
        }  else{
            if($referedby=="undefined"){
                $referedby="";
            }
                    $userdata=$getuserattached[0];
                    $userid = $userdata['id'];
                    $oldusername = $userdata['username'];
                    if(strlen($oldusername)<=0){
                        $password=$utility_class_call->Password_encrypt($password);

                        $updateddate= $db_call_class->updateRows("users",['phoneno'=>$phone,'password'=>$password,'referby'=>$referedby,'country_id'=>$countrytid,'phoneverified'=>0,"fname"=>$firstname,'lname'=>$lastname,'username'=>$username],
                        [  
                            [
                                ['column' =>'id', 'operator' =>'=', 'value' =>$userid]
                            ]
                        ] );
                        if ($updateddate) {
                                $maindata=[];
                                $text=API_User_Response::$data_updated;
                                $api_status_code_class_call->respondOK($maindata,$text);
                        }else {
                            $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                        }
                    }else{
                        $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                    }
                    
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
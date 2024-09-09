<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\API_User_Response;
use OTPHP\TOTP;
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
        $token= isset($_POST['token']) ? $utility_class_call->clean_user_data($_POST['token']) : '';
        $verifytype= isset($_POST['verifytype']) ? $utility_class_call->clean_user_data($_POST['verifytype']) : '';// 1 is email 2 is phone number 3 forgot password , 4 is setup 2FA Email, 5 is for BVN, 6 login 2fa email, 7 verify 2fa email
        $otp= isset($_POST['otp']) ? $utility_class_call->clean_user_data($_POST['otp']) : 0;
        $twoFaType= isset($_POST['two_fa_type']) ? $utility_class_call->clean_user_data($_POST['two_fa_type']) : 0;//1 for mail 2 for authy app 3 for sms
        $activate_2fa_for_login= isset($_POST['activate_2fa_for_login']) ? $utility_class_call->clean_user_data($_POST['activate_2fa_for_login']) : 0;//1 for yes 0 for no
        $whichtoken=1;
        if($verifytype==1||$verifytype==6){
            $whichtoken=2;
        }
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken:$whichtoken);
        $user_pubkey = $decodedToken->usertoken;

        $getuserattached= $db_call_class->selectRows("users", "id",
        [   
            [
                ['column' =>'userpubkey', 'operator' =>'=', 'value' =>$user_pubkey],
            ]
        ]
        );

        $getoldotpsent=[];
        $email_verified =0;
        $phone_verified =0;
        if(!$utility_class_call->input_is_invalid($getuserattached)){
            $userdata=$getuserattached[0];
            $userid = $userdata['id'];
            // checking if sms is sent is not older than 5 min
            $getoldotpsent= $db_call_class->selectRows("system_otps", "verification_type", 
            [   
                [
                    ['column' =>'TIMESTAMPDIFF(MINUTE, created_at, NOW())', 'operator' =>'<', 'value' =>5],
                    ['column' =>'user_id', 'operator' =>'=', 'value' =>$userid],
                    ['column' =>'forwho', 'operator' =>'=', 'value' =>1],
                    'operator'=>'AND'
                ],
                [
                    ['column' =>'token', 'operator' =>'=', 'value' =>$token],
                    ['column' =>'otp', 'operator' =>'=', 'value' =>$otp],
                    'operator'=>'OR'
                ]
            ]
            );
        }
        // send error if ur is not in the database
        if ($utility_class_call->input_is_invalid($getuserattached)){
                $api_status_code_class_call->respondUnauthorized();
        }else if($utility_class_call->input_is_invalid($token)&&$utility_class_call->input_is_invalid($otp)){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }else  if ($utility_class_call->input_is_invalid($getoldotpsent)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidOtporExpired);
        } else  if ($verifytype==4 &&(!is_numeric($twoFaType)||!is_numeric($activate_2fa_for_login) || $activate_2fa_for_login >1 || $activate_2fa_for_login<0||$twoFaType<0||$twoFaType>3)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } else{
                    $userdata=$getuserattached[0];
                    $userid = $userdata['id'];

                    $otpdata=$getoldotpsent[0];
                    $verifytype = $otpdata['verification_type'];
                    if($verifytype==1){
                        $updateddate= $db_call_class->updateRows("users",["emailverified"=>1,'activate_2fa'=>2],
                            [  
                                [
                                    ['column' =>'id', 'operator' =>'=', 'value' =>$userid]
                                ]
                            ] 
                         );
                        if ($updateddate) {
                                $maindata=[];
                                $accesstoken=$api_status_code_class_call->getTokenToSendAPI($user_pubkey,1);
                                $maindata['access_token']=$accesstoken;
                                $maindata=[$maindata];
                                $text=API_User_Response::$emailVerifiedSuccessFully;
                                $api_status_code_class_call->respondOK($maindata,$text);
                        }else {
                            $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                        }
                    }else if($verifytype==2){
                        $updateddate= $db_call_class->updateRows("users",["phoneverified"=>1],
                        [  
                            [
                                ['column' =>'id', 'operator' =>'=', 'value' =>$userid]
                            ]
                        ]  );
                        if ($updateddate) {
                            $maindata=[];
                            $text=API_User_Response::$phoneNoVerifiedSuccessFully;
                            $api_status_code_class_call->respondOK($maindata,$text);
                        }else {
                            $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                        } 
                    }else if($verifytype==4){
                        if($twoFaType==1){// Authy 2fa
                            $ga = new PHPGangsta_GoogleAuthenticator();
                            $getSysData= $db_call_class->selectRows("systemsettings", "name",
                            [   
                                [
                                     ['column' =>'id', 'operator' =>'=', 'value' =>1],
                                ]
                            ]
                            );
                            $companyname = $getSysData[0]['name'];
                            $secret=$db_call_class->createUniqueRandomStringForATableCol(32,"users","google_secret_key","",true,true,false,true);

                            $qrCodeUrl = $ga->getQRCodeGoogleUrl($companyname, $secret);
                            $oneCode = $ga->getCode($secret);
                            $checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
                            $txt = $qrCodeUrl;
                            $breakit= str_replace("https://api.qrserver.com/v1/create-qr-code/?data=", "", $txt);
                            $breakit= str_replace("&size=200x200&ecc=M", "", $breakit);
                            // $qrCodeUrl="https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=$breakit";
                            $qrCodeUrl="https://api.qrserver.com/v1/create-qr-code/?data=$breakit";
                            if ($checkResult) {
                                $maindata= array('url'=>$qrCodeUrl,'key'=>$secret,"qrdata"=>$breakit);
                            $maindata=[$maindata];

                                $updateddate= $db_call_class->updateRows("users",['google_secret_key'=>$secret],
                                [  
                                    [
                                        ['column' =>'id', 'operator' =>'=', 'value' =>$userid]
                                    ]
                                ] 
                                );
                                if ($updateddate) {
                                    $text=API_User_Response::$data_Valid;
                                    $api_status_code_class_call->respondOK($maindata,$text);
                                }else {
                                    $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                                } 
                            }else{
                                $api_status_code_class_call->respondInternalError(API_User_Response:: $data_InValid,API_User_Response:: $data_InValid);
                            }
                        }else   if($twoFaType==2){
                            // email 2fa
                            $updateddate= $db_call_class->updateRows("users",['activate_2fa'=>2,'activate_login_2fa'=>$activate_2fa_for_login],
                            [  
                                [
                                    ['column' =>'id', 'operator' =>'=', 'value' =>$userid]
                                ]
                            ] 
                            );
                            if ($updateddate) {
                                $maindata=[];
                                $text=API_User_Response::$data_Valid;
                                $api_status_code_class_call->respondOK($maindata,$text);
                            }else {
                                $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                            } 

                           
                        }else{
                            $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record,API_User_Response::$error_updating_record);
                        }
                    }else if($verifytype==6){
                            $maindata=[];
                            $accesstoken=$api_status_code_class_call->getTokenToSendAPI($user_pubkey,1);
                            $maindata['access_token']=$accesstoken;
                            $maindata=[$maindata];
                            $text=API_User_Response::$data_Valid;
                            $api_status_code_class_call->respondOK($maindata,$text);
                    }else if($verifytype==7){
                        $maindata=[];
                        $text=API_User_Response::$data_Valid;
                        $api_status_code_class_call->respondOK($maindata,$text);
                }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
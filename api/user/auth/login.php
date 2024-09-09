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
            $maindata['frozedate']="";

            #Get Post Data
            $email = isset($_POST['email']) ? $utility_class_call->clean_user_data($_POST['email'],1) : '';//email or username
            $password = isset($_POST['password']) ? $utility_class_call->clean_user_data($_POST['password'],1) : '';
            $googlecode =isset($_POST['googlecode']) ? $utility_class_call->clean_user_data($_POST['googlecode']) :  '';

            $responseData = $db_call_class->selectRows("users", "id,email,emailverified,password,activate_2fa,username,userpubkey,phoneno,status,activate_login_2fa,phoneverified,pinadded,userlevel", 
            [   
                [
                    ['column' =>'email', 'operator' =>'=', 'value' =>$email],
                    ['column' =>'username', 'operator' =>'=', 'value' =>$email]
                    ,'operator'=>'OR'
                ]
            ]
            );

            if ($utility_class_call->input_is_invalid($email) ||$utility_class_call->input_is_invalid($googlecode) || $utility_class_call->input_is_invalid($password)) {
                //     checking if data is empty
                $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
            } elseif ($utility_class_call->input_is_invalid($responseData)) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidUserDetail);
            } elseif (Constants::ALLOW_USER_TO_LOGIN_REGISTER==0) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::$serverUnderMaintainance);
            } else{
                $found= $responseData[0];
                $user_id = $found['id'];
                $dash_mail = $found['email'];
                $emailverified =$found['emailverified'];
                $phoneverified=$found['phoneverified'];
                $pinadded =$found['pinadded'];
                $pass = $found['password'];
                $phone = $found['phoneno'];
                $dashunmae= $found['username'];
                $userlevel=$found['userlevel'];
                $islog_in2fa_active= $found['activate_login_2fa'];//1 yes 0 no
                $is_2fa_active = $found['activate_2fa'];//0 no, 1 Auth app(google,twilo etc), 2 Email 2fa, 3 SMS 2fa
                $phone = $found['phoneno'];
                $userPubkey= $found['userpubkey'];
                $statusis=$found['status'];
                $banreason = 'You have been Banned';
                
                if($utility_class_call->is_theGoogleCaptchaValid($googlecode)) {
                    //verify the new password with the db pass
                    $verifypass = $utility_class_call->is_password_hash_valid($password, $pass);
                    if ($verifypass) {
                        if ($statusis==1) {
                            $maindata=[];
                            $maindata['kyclevel']=$userlevel;
                            $maindata['email_verified']=$emailverified;
                            $maindata['phone_no_verified']=$phoneverified;
                            $maindata['username_added']=$utility_class_call->input_is_invalid($dashunmae)?0:1;
                            $maindata['phone_no_added']=$utility_class_call->input_is_invalid($phone)?0:1;
                            $maindata['phone_no']=$phone;
                            $maindata['pin_added']=$pinadded;
                            $maindata['is_login_2fa_active'] =$islog_in2fa_active;
                            $maindata['the_2fa_type'] =$is_2fa_active;//0 no, 1 Auth app(google,twilo etc), 2 Email 2fa, 3 SMS 2fa

                            #To Check For 2FA Authentication
                            if ($islog_in2fa_active == 1){
                                $accesstoken=$api_status_code_class_call->getTokenToSendAPI($userPubkey,2);
                                $maindata['access_token']=$accesstoken;

                                $maindata=[$maindata];
                                $text="Redirecting to 2FA Page";
                                $api_status_code_class_call->respondOK($maindata,$text);
                            }  else{
                                $ipaddress= $utility_class_call->getIpAddress();
                                $getcount =0;
                                $responseData = $db_call_class->selectRows("userloginsessionlog", "email", 
                                [   
                                    [
                                        ['column' =>'ipaddress', 'operator' =>'=', 'value' =>$ipaddress]
                                    ]
                                ]
                                );
                                if(!$utility_class_call->input_is_invalid($responseData)){
                                    $getcount = count($responseData);
                                }
                                $datatosave=time();
                                $db_call_class->updateRows("users",["login_last_with"=>2,"last_time_logged_in"=>$datatosave],
                                [  
                                    [
                                        ['column' =>'id', 'operator' =>'=', 'value' =>$user_id]
                                    ]
                                ] );

                                 // saving user login session
                                $seescode = $db_call_class->createUniqueRandomStringForATableCol(20,"userloginsessionlog","sessioncode",time(),true,true,true);
                                $browser = ' '.$utility_class_call->getBrowserInfo()['name'].' on '.ucfirst($utility_class_call->getBrowserInfo()['platform']);
                                $location='';
                                    //Put sessioncode inside database
                                $db_call_class->insertRow("userloginsessionlog",["email"=>$email,'sessioncode'=>$seescode,'ipaddress'=>$ipaddress,'browser'=>$browser,'username'=>$dashunmae,'forwho'=>1,'location'=>$location,]);
                                // generating user access token
                                $tokentype=1;
                                if($emailverified==0){
                                    $tokentype=2;
                                }
                                $accesstoken=$api_status_code_class_call->getTokenToSendAPI($userPubkey,$tokentype);
                                $maindata['access_token']=$accesstoken;
                                if($getcount==0){
                                    $functionname='sendLoginSMSEMail';
                                    $classPath='Config\Mail_SMS_Responses';
                                    $parameters=json_encode([$user_id,$seescode]);
                                    $InsertCronData =$db_call_class->insertRow("cron_tasks",["function_name"=>$functionname,"parameters"=>$parameters,'classPath'=>$classPath]);
                                }
                                
                                $maindata=[$maindata];
                                $text=API_User_Response::$loginSuccessful;
                                $api_status_code_class_call->respondOK($maindata,$text);
                            }
                        } elseif ($statusis==2) {//suspended
                            $api_status_code_class_call->respondBadRequest($banreason);
                        } elseif ($statusis==3) {//frozen
                            $api_status_code_class_call->respondBadRequest($banreason);
                        } elseif ($statusis==0) {//banned
                            $api_status_code_class_call->respondBadRequest($banreason);
                        } elseif ($statusis==4) {//deleted
                            $api_status_code_class_call->respondBadRequest(API_User_Response::$user_account_deleted);
                        } else {
                            $api_status_code_class_call->respondBadRequest(API_User_Response:: $user_permanetly_banned);
                        }
                    } else {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidUserDetail);
                    }
                }else{
                    $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidreCAPTCHA);
                }
            }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
$api_status_code_class_call->respondMethodNotAlowed();
}


?>
<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\Mail_SMS_Responses;
use Config\API_User_Response;
use DatabaseCall\Users_Table;
use Google\Service\Oauth2 as Google_Service_Oauth2;
$allowedDomainNames=Constants::BASE_URL;
$apimethod="GET";
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
        $registervia = isset($_GET['registervia']) ? $utility_class_call->clean_user_data($_GET['registervia']) : 1;// 1 web 2 App
        $devicetype = isset($_GET['devicetype']) ? $utility_class_call->clean_user_data($_GET['devicetype'],1) : 'Web';
        $dataNo=isset($_GET['code']) ?$_GET['code']:'';
        $fromcode=isset($_GET['fromcode']) ?$_GET['fromcode']:1;

        if ($utility_class_call->input_is_invalid($dataNo)||$utility_class_call->input_is_invalid($registervia)||$utility_class_call->input_is_invalid($devicetype)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } elseif (Constants::ALLOW_USER_TO_LOGIN_REGISTER==0) {
            API_User_Response::$serverUnderMaintainance;
        }else{
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

                 // Set the cURL options to include the CA certificate
    // $client->setHttpClient(new \GuzzleHttp\Client(['verify' => 'D:\cacert-2024-03-11.pem']));

                $token = $client->fetchAccessTokenWithAuthCode($dataNo);
                  //This condition will check there is any error occur during geting authentication token. If there is no any error occur then it will execute if block of code/
                if (!isset($token['error']) || $fromcode==2) {
                    if (!isset($token['error'])){
                        $accesstokenis=$token['access_token'];
                    }else{
                        $accesstokenis=$dataNo;
                    }
                            $client->setAccessToken( $accesstokenis);
                    
                            // Get profile info from Google
                            $oauth2 = new Google_Service_Oauth2($client);
                            $google_user_info = $oauth2->userinfo->get();
                        if(isset($google_user_info['email'])){
                            $email = $google_user_info['email'];
                            //save google API response
                            $jsondata=json_encode($google_user_info);
                            $db_call_class->insertRow("responsesfromapicalllog",["jsonbody"=>'none',"name"=>'Google login','jsonresp'=>$jsondata]);

                            // {"email":"habnarmtech@gmail.com","familyName":"Imran","gender":null,"givenName":"Abiodun Babs","hd":null,"id":"110354732597629645238","link":null,"locale":null,"name":"Abiodun Babs Imran","picture":"https:\/\/lh3.googleusercontent.com\/a\/ACg8ocK2mCrnOf5N9-h0Bn-NPdJv8L0NYgu-h5gIxYJjldJkpSW6cds=s96-c","verifiedEmail":true}
                            //check if email exist, if yes create login access token
                            // if no register user and send login access token

                            $requestData =$db_call_class->selectRows("users","email,id,userpubkey,emailverified,phoneverified,username,phoneno,pinadded,activate_login_2fa,activate_2fa,status,userlevel",
                            [   
                                [
                                    ['column' =>'email', 'operator' =>'=', 'value' =>$email]
                                ]
                            ]
                            );
                            if ($utility_class_call->input_is_invalid($requestData)) {
                                // register
                                 #Get Post Data
                                $firstname = isset($google_user_info['givenName']) ? $utility_class_call->clean_user_data($google_user_info['givenName']) : '';
                                $lastname =  '';
                                $username = '';
                                $email = isset($google_user_info['email']) ? $utility_class_call->clean_user_data($google_user_info['email'],1) : '';
                                $phone = '';
                                $password = '';
                                $referedby = '';
                                $countrytid= 'NFYUS';
                                $hearfrom = '';
                                $utm_source = isset($_GET['utm_source'])&& !$utility_class_call->input_is_invalid($_GET['utm_source']) ? $utility_class_call->clean_user_data($_GET['utm_source'],1) : 'direct_source';
                                $utm_medium = isset($_GET['utm_medium'])&& !$utility_class_call->input_is_invalid($_GET['utm_medium']) ? $utility_class_call->clean_user_data($_GET['utm_medium'],1) : 'direct_medium';
                                $register_from = isset($_GET['sign_through'])&& !$utility_class_call->input_is_invalid($_GET['sign_through']) ? $utility_class_call->clean_user_data($_GET['sign_through'],1) : '';
                                $quarter= date("Y",time())."Q";
                                if(date("m")>=1&&date("m")<=3){
                                $quarter.="1"; 
                                }else if(date("m")>=6&&date("m")<=5){
                                $quarter.="2"; 
                                }else if(date("m")>=9&&date("m")<=8){
                                $quarter.="3"; 
                                }else if(date("m")>=10&&date("m")<=12){
                                $quarter.="4"; 
                                }
                                // Jan to March = 1-3 2023 Quarter 1
                                // April to june = 4-6  2023 Quarter 2
                                // july to september = 7-9 2023 Quarter 3
                                // october december =10-12 2023 Quarter 4
                                $utm_campaign = isset($_GET['utm_campaign']) && !$utility_class_call->input_is_invalid($_GET['utm_campaign']) ? $utility_class_call->clean_user_data($_GET['utm_campaign'],1) : $quarter;

                              
                                if ( $utility_class_call->input_is_invalid($email)) {
                                    $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
                                }elseif ($db_call_class->checkIfRowExistAndCount('users',
                                [   
                                    [
                                        ['column' =>'email', 'operator' =>'=', 'value' =>$email],
                                    ]
                                ])>0) {// checking if data is valid
                                    $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Email"));
                                } elseif (!$utility_class_call->isEmailValid($email)) {
                                    $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Email"));
                                } else{
                                    if($referedby=="undefined"){
                                        $referedby="";
                                    }
                                    if($register_from=="undefined"){
                                        $register_from="";
                                    }
                                    
                                    // getting system settings
                                    $systemname="";
                                    $systemData = $db_call_class->selectRows("systemsettings","name",
                                    [   
                                        [
                                            ['column' =>'id', 'operator' =>'=', 'value' =>1]
                                        ]
                                    ]
                                    ,['limit'=>1]);
                                    if (!$utility_class_call->input_is_invalid($systemData)) {
                                        $systemData = $systemData[0];
                                        $systemname=$systemData['name'];
                                    }
                                    // creating user details
                                    $status=1;
                                    // generating user pub key
                                    // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
                                    $public_key = $db_call_class->createUniqueRandomStringForATableCol(29,"users","userpubkey","$systemname",true,true,true);
                                    // generating user referal code
                                    // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
                                    $refcode=$db_call_class->createUniqueRandomStringForATableCol(5,"users","refcode","",true,true,false);
                                
                                    // Assign account officer
                                    $accountoffier_is="IOSD";
                                    if(Constants::ASSIGN_ACCOUNT_OFFICER==1){
                                        $accofficerdata=Users_Table::getAccountOfficierToAssign();
                                        $accountoffier_is=$accofficerdata;
                                    }

                                    $InsertResponseData =$db_call_class->insertRow("users",["email"=>$email,"fname"=>$firstname,'lname'=>$lastname,'password'=>'','username'=>$username,'userpubkey'=>$public_key,'status'=>$status,'phoneno'=>$phone,'referby'=>$referedby,'refcode'=>$refcode,'registervia'=>$registervia,'devicetype'=>$devicetype,'utm_medium'=>$utm_medium,'activate_2fa'=>2,'utm_source'=>$utm_source,'utm_campaign'=>$utm_campaign,'hear_about_us'=>$hearfrom,'account_officer'=>$accountoffier_is,'register_from'=>$register_from,'country_id'=>$countrytid,'register_via_google'=>1,'emailverified'=>1]);
                                    if($InsertResponseData>0){
                                        $last_id = $InsertResponseData;
                                        
                                        // Creating defualt currencies for user
                                        // getting the default currencies
                                        $systemData = $db_call_class->selectRows("currencysystem","currencytag",
                                        [   
                                            [
                                                ['column' =>'defaultforusers', 'operator' =>'=', 'value' =>1]
                                            ]
                                        ]
                                        );
                                        if (!$utility_class_call->input_is_invalid($systemData)) {
                                            for($i=0;$i<count($systemData);$i++){
                                                $getsys=$systemData[$i];
                                                $currencytag =	$getsys['currencytag'];
                                                // generating wallet track id for user and assigning user the currencies
                                                // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
                                                $track_id=$db_call_class->createUniqueRandomStringForATableCol(4,"userwallet","wallettrackid",$currencytag,true,true,true);
                                                $db_call_class->insertRow("userwallet",["userid"=>$last_id,'currencytag'=>$currencytag,'wallettrackid'=>$track_id]);
                                            }
                                        }
                                        // saving user login session
                                        $seescode = $db_call_class->createUniqueRandomStringForATableCol(20,"userloginsessionlog","sessioncode",time(),true,true,true);
                                        $ipaddress= $utility_class_call->getIpAddress();
                                        $browser = ' '.$utility_class_call->getBrowserInfo()['name'].' on '.ucfirst($utility_class_call->getBrowserInfo()['platform']);
                                        $location='';
                                            //Put sessioncode inside database
                                        $db_call_class->insertRow("userloginsessionlog",["email"=>$email,'sessioncode'=>$seescode,'ipaddress'=>$ipaddress,'browser'=>$browser,'username'=>$username,'forwho'=>1,'location'=>$location,]);

                                        
                                        // generating user access token
                                        $accesstoken=$api_status_code_class_call->getTokenToSendAPI($public_key);
                                        $maindata['access_token']=$accesstoken;
                                        $maindata['email_verified']=1;
                                        $maindata['phone_no']=$phone;
                                        $maindata['phone_no_verified']=0;
                                        $maindata['username_added']=0;
                                        $maindata['phone_no_added']=0;
                                        $maindata['pin_added']=0;
                                        $maindata['kyclevel']=0;
                                        $maindata['is_login_2fa_active'] =0;
                                        $maindata['the_2fa_type'] =0;//0 no, 1 Auth app(google,twilo etc), 2 Email 2fa, 3 SMS 2fa

                                        $functionname='sendRegisterSMSEmailPushNoti';
                                        $classPath='Config\Mail_SMS_Responses';
                                        $parameters=json_encode([$last_id]);
                                        $InsertCronData =$db_call_class->insertRow("cron_tasks",["function_name"=>$functionname,"parameters"=>$parameters,'classPath'=>$classPath]);
                                        
                                        $maindata=[$maindata];
                                        $text=API_User_Response::$registerSuccessful;
                                        $api_status_code_class_call->respondOK($maindata,$text);
                                    
                                    } else{
                                        $api_status_code_class_call->respondInternalError(API_User_Response::$error_creating_record,API_User_Response::$error_creating_record);
                                    }
                                }
                            }else{
                                $found = $requestData[0];
                                $user_id = $found['id'];
                                $emailverified=$found['emailverified'];
                                $phoneverified=$found['phoneverified'];
                                $dashunmae=$found['username'];
                                $phone=$found['phoneno'];
                                $pinadded=$found['pinadded'];
                                $islog_in2fa_active=$found['activate_login_2fa'];
                                $is_2fa_active=$found['activate_2fa'];
                                $statusis=$found['status'];
                                $dash_mail = $found['email'];
                                $userPubkey= $found['userpubkey'];
                                $userlevel= $found['userlevel'];



                                $banreason = 'You have been Banned';
                                if ($statusis==1) {
                                    $maindata=[];
                                    $maindata['kyclevel']=$userlevel;
                                    $maindata['email_verified']=$emailverified;
                                    $maindata['phone_no_verified']=$phoneverified;
                                    $maindata['username_added']=$utility_class_call->input_is_invalid($dashunmae)?0:1;
                                    $maindata['phone_no_added']=$utility_class_call->input_is_invalid($phone)?0:1;
                                    $maindata['pin_added']=$pinadded;
                                    $maindata['phone_no']=$phone;
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
                                        ] , );
        
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
                            }
                            $maindata['url']="";
                            $maindata=[$maindata];
                            $text=API_User_Response::$data_found;
                            $api_status_code_class_call->respondOK($maindata,$text);
                        }else{
                            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
                        }
                }else{
                    $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
                }
            }else{
                $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
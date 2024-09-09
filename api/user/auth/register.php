<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\Mail_SMS_Responses;
use DatabaseCall\Users_Table;
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
            $firstname = isset($_POST['firstname']) ? $utility_class_call->clean_user_data($_POST['firstname']) : '';
            $lastname = isset($_POST['lastname']) ? $utility_class_call->clean_user_data($_POST['lastname']) : '';
            $username = isset($_POST['username']) ? $utility_class_call->clean_user_data($_POST['username'],1) : '';
            $email = isset($_POST['email']) ? $utility_class_call->clean_user_data($_POST['email'],1) : '';
            $phone = isset($_POST['phone']) ? $utility_class_call->clean_user_data($_POST['phone']) : '';
            $password = isset($_POST['password']) ? $utility_class_call->clean_user_data($_POST['password'],1) : '';
            $referedby = isset($_POST['referedby']) ? $utility_class_call->clean_user_data($_POST['referedby']) : '';
            $countrytid= isset($_POST['country_tid']) ? $utility_class_call->clean_user_data($_POST['country_tid']) : 'UKYT';
            $registervia = isset($_POST['registervia']) ? $utility_class_call->clean_user_data($_POST['registervia']) : 1;// 1 web 2 App
            $devicetype = isset($_POST['devicetype']) ? $utility_class_call->clean_user_data($_POST['devicetype'],1) : 'Web';
            $hearfrom = isset($_POST['hearfrom']) ? $utility_class_call->clean_user_data($_POST['hearfrom']) : ' ';
            $utm_source = isset($_POST['utm_source'])&& !$utility_class_call->input_is_invalid($_POST['utm_source']) ? $utility_class_call->clean_user_data($_POST['utm_source'],1) : 'direct_source';
            $utm_medium = isset($_POST['utm_medium'])&& !$utility_class_call->input_is_invalid($_POST['utm_medium']) ? $utility_class_call->clean_user_data($_POST['utm_medium'],1) : 'direct_medium';
            $register_from = isset($_POST['sign_through'])&& !$utility_class_call->input_is_invalid($_POST['sign_through']) ? $utility_class_call->clean_user_data($_POST['sign_through'],1) : '';
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
            $utm_campaign = isset($_POST['utm_campaign']) && !$utility_class_call->input_is_invalid($_POST['utm_campaign']) ? $utility_class_call->clean_user_data($_POST['utm_campaign'],1) : $quarter;

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

            if ( $utility_class_call->input_is_invalid($email) || $utility_class_call->input_is_invalid($firstname) || $utility_class_call->input_is_invalid($lastname) || $utility_class_call->input_is_invalid($username) || $utility_class_call->input_is_invalid($password) || $utility_class_call->input_is_invalid($phone) || $utility_class_call->input_is_invalid($registervia)||$utility_class_call->input_is_invalid($countrytid) ||$utility_class_call->input_is_invalid($devicetype)) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
            } elseif ($db_call_class->checkIfRowExistAndCount('countries',
            [   
                [
                    ['column' =>'trackid', 'operator' =>'=', 'value' =>$countrytid],
                ]
            ]
            )==0) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Country"));
            } elseif (strlen($firstname)>25|| $utility_class_call->textHasEmojis($firstname)) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::lengthError('First Name',25));
            } elseif (strlen($lastname)>25|| $utility_class_call->textHasEmojis($lastname)) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::lengthError('Last Name',25));
            } elseif (strlen($username)>25) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::lengthError('Username',25));
            } elseif (strlen($phone)>$max_pno_value||strlen($phone)<$minpnovalue) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::lengthMinMaxError('Phone Number',$max_pno_value,$minpnovalue));
            } elseif (!$utility_class_call->isUsernameValid($username)) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidUsername);
            } elseif (!$utility_class_call->isPasswordStrong($password)) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::$invalidPassword);
            } elseif ($db_call_class->checkIfRowExistAndCount('users',
            [   
                [
                    ['column' =>'email', 'operator' =>'=', 'value' =>$email],
                ]
            ])>0) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Email"));
            } elseif ($db_call_class->checkIfRowExistAndCount('users',
            [   
                [
                    ['column' =>'username', 'operator' =>'=', 'value' =>$username],
                ]
            ])>0) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Username"));
            } elseif ($db_call_class->checkIfRowExistAndCount('users',
            [   
                [
                    ['column' =>'phoneno', 'operator' =>'=', 'value' =>$phone],
                    ['column' =>'phoneno', 'operator' =>'=', 'value' =>$newPhoneNumber],
                    ['column' =>'phoneno', 'operator' =>'=', 'value' =>$newPhoneNumber2],
                    'operator'=>'OR'
                ]
            ]
            )>0) {// checking if data is valid
                $api_status_code_class_call->respondBadRequest(API_User_Response::dataAlreadyExist("Phone Number"));
            } elseif (!$utility_class_call->isEmailValid($email)) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Email"));
            } else  if ($referedby != ""&&!$utility_class_call->input_is_invalid($referedby)&&$referedby!="undefined"&&$db_call_class->checkIfRowExistAndCount('users',
            [   
                [
                    ['column' =>'refcode', 'operator' =>'=', 'value' =>$referedby]

                ]
            ]
            )==0) {
                $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Referral Code"));
            } elseif (Constants::ALLOW_USER_TO_LOGIN_REGISTER==0) {
                API_User_Response::$serverUnderMaintainance;
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
                $password=$utility_class_call->Password_encrypt($password);
                // creating user details
                $status=1;
                // generating user pub key
                // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
                $public_key = $db_call_class->createUniqueRandomStringForATableCol(29,"users","userpubkey","$systemname",true,true,true);
                // generating user referal code
                // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
                $refcode=$db_call_class->createUniqueRandomStringForATableCol(5,"users","refcode","",true,true,false);
                $trackid=$db_call_class->createUniqueRandomStringForATableCol(5,"users","trackid","",true,true,false);
                // Assign account officer
                $accountoffier_is="IOSD";
                if(Constants::ASSIGN_ACCOUNT_OFFICER==1){
                    $accofficerdata=Users_Table::getAccountOfficierToAssign();
                    $accountoffier_is=$accofficerdata;
                }

                $InsertResponseData =$db_call_class->insertRow("users",["email"=>$email,"fname"=>$firstname,'lname'=>$lastname,'password'=>$password,'username'=>$username,'userpubkey'=>$public_key,'status'=>$status,'phoneno'=>$phone,'referby'=>$referedby,'refcode'=>$refcode,'registervia'=>$registervia,'devicetype'=>$devicetype,'utm_medium'=>$utm_medium,'utm_source'=>$utm_source,'utm_campaign'=>$utm_campaign,'activate_2fa'=>2,'hear_about_us'=>$hearfrom,'account_officer'=>$accountoffier_is,'register_from'=>$register_from,'country_id'=>$countrytid,'trackid'=>$trackid]);
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
                    $emailverified=0;
                    $tokentype=1;
                    if($emailverified==0){
                        $tokentype=2;
                    }
                    $accesstoken=$api_status_code_class_call->getTokenToSendAPI($public_key,$tokentype);
                    $maindata['access_token']=$accesstoken;
                    $maindata['email_verified']=0;
                    $maindata['phone_no_verified']=0;
                    $maindata['username_added']=1;
                    $maindata['phone_no_added']=1;
                    $maindata['kyclevel']=0;
                    $maindata['pin_added']=0;
                    $maindata['is_login_2fa_active'] =0;
                    $maindata['the_2fa_type'] =0;//0 no, 1 Auth app(google,twilo etc), 2 Email 2fa, 3 SMS 2fa

                    
                    $maindata=[$maindata];
                    $text=API_User_Response::$registerSuccessful;
                    $api_status_code_class_call->respondOK($maindata,$text);
                
                } else{
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
<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\API_User_Response;

$allowedDomainNames = Constants::BASE_URL;
$apimethod = "GET";
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
// header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
$utility_class_call->setTimeZoneForUser('');
header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call = new Config\API_Status_Code;
$db_call_class = new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try {

        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1,);
        $user_pubkey = $decodedToken->usertoken;
        $limittext = isset($_GET['limit_it']) ? $utility_class_call->clean_user_data($_GET['limit_it']) : '';
        $showvoucher = false;

        $getuserattached = $db_call_class->selectRows(

            "users",
            "users.id,users.documentlinks,users.account_verified,users.service_tid,users.bank_holder_name,users.bank_name,users.bank_route_no,users.bank_address,users.bank_acc_no,users.username,users.email,users.profile_pic,users.fname,users.lname,users.country_id,users.phoneno,users.dob,users.state_tid,users.address1,users.userlevel,users.nextkinfname,users.nextkinemail,users.nextkinpno,users.nextkinaddress,users.depositnotification,users.securitynotification,users.transfernotification,users.lastpassupdate,users.city_tid,users.emailverified,users.phoneverified,users.postalcode,users.referby,users.activate_biometric,users.refcode,users.email_noti,users.sms_noti,users.push_noti,users.job_position,users.signature_phoneno,users.company,users.activate_login_2fa,users.pinadded,users.activate_2fa,users.lastpinupdate,users.created_at,users.pin, CASE
        WHEN users.sex = 1 THEN 'Male'
        WHEN users.sex = 2 THEN 'Female'
        ELSE 'others'
    END AS gender_text, countries.name as country_name,countries_cities.name as city_name,countries_states.name AS state_name,services.name as servicename",
            [
                [
                    ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey],
                ]
            ],
            [
                'joins' => [

                    [

                        'type' => 'LEFT',

                        'table' => 'countries',

                        'condition' => 'users.country_id = countries.trackid'

                    ],
                    [

                        'type' => 'LEFT',

                        'table' => 'services',

                        'condition' => 'users.service_tid = services.trackid'

                    ],

                    
                    [

                        'type' => 'LEFT',

                        'table' => 'countries_cities',

                        'condition' => 'users.city_tid =  countries_cities.trackid'

                    ],
                    [

                        'type' => 'LEFT',

                        'table' => 'countries_states',

                        'condition' => 'users.state_tid= countries_states.trackid'

                    ],
                ]
            ]
        );
        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        } else {
            $getAllData = $getuserattached;

            if ($utility_class_call->input_is_invalid($getAllData)) {
                $text = API_User_Response::$data_not_found;
                $maindata['data'] = [];
                $maindata['pageno'] = $page_no;
                $maindata['perpage'] = $noperpage;
                $maindata['totalpage'] = 0;
            } else {
                $base_url = Constants::BASE_URL;
                $row= $getuserattached[0];
                $user_id = $row['id'];
                $email = $row['email'];
                $firstName = $row['fname'];
                $lastName = $row['lname'];
                $username = strtolower($row['username']);
                $fullname = $firstName . " " . $lastName;
                $country = $row['country_name'];
                $phoneno = $row['phoneno'];
                $dob = $row['dob'];
                $gender = $row['gender_text'];
                $address1 = $row['address1'];
                $userlevel = $row['userlevel'];
                $nextkinfname = $row['nextkinfname'];
                $nextkinemail = $row['nextkinemail'];
                $nextkinphonenumber = $row['nextkinpno'];
                $nextkinaddress = $row['nextkinaddress'];
                $depositnotification = $row['depositnotification'];
                $securitynotification = $row['securitynotification'];
                $transfernotification = $row['transfernotification'];
                $lastpasswordupdate = $row['lastpassupdate'];
                $emailverified = $row['emailverified'];
                $phoneverified = $row['phoneverified'];
                $postalcode = $row['postalcode'];
                $referby = $row['referby'];
                $activate_biometric = $row['activate_biometric'];
                $refcode = $row['refcode'];
                $email_noti = $row['email_noti'];
                $sms_noti = $row['sms_noti'];
                $push_noti = $row['push_noti'];
                $job_position = $row['job_position'];
                $sig_phoneno = $row['signature_phoneno'];
                $company = $row['company'];
                $activate_login_2fa = $row['activate_login_2fa'];
                $activate_2fa=$row['activate_2fa'];
                $pinadded = $row['pinadded'];
                $lastpinupdate = $row['lastpinupdate'];
                $kycverificationStage = 0;//1-Approved 0-Pending 3 submitted
                $created = $row['created_at'];
                $pinadded2 = strlen($row['pin']);
                $state = $row['state_name'];
                $city = $row['city_name'];
                $profile_pic=$row['profile_pic'];
                $bank_holder_name=$row['bank_holder_name'];
                $bank_name=$row['bank_name'];
                $bank_route_no=$row['bank_route_no'];
                $bank_address=$row['bank_address'];
                $bank_acc_no=$row['bank_acc_no'];
                $documentlinks=$row['documentlinks'];
                $servicename=$row['servicename'];
                $account_verified=$row['account_verified'];

                if (strlen(trim($sig_phoneno)) <= 3) {
                    $sig_phoneno =   $phoneno;
                }
                $systembaseurl=Constants::BASE_URL;
                $referallink = $systembaseurl . "/auth/register.html?ref=$refcode";
                $referallink = $systembaseurl . "/auth/register?ref=$username";
                $referallink = "$systembaseurl/referral/index?username=$username";
                $profilelink = "";
                $userKYCData= $db_call_class->selectRows("user_kyc_data", "passport,status", 
                [   
                    [
                        ['column' =>'user_id', 'operator' =>'=', 'value' =>$user_id],
                    ]
                ]
                );
                if (!$utility_class_call->input_is_invalid($userKYCData)) {
                    $userpassportdata=$userKYCData[0]['passport'];
                    $kycverificationStage=$userKYCData[0]['status'];
                    if (strlen($userpassportdata) >= 4) {
                        if(str_contains($userpassportdata,"images/userpassport")){
                            $profilelink = "$systembaseurl/" . $userpassportdata;
                        }else{
                            $profilelink = "$systembaseurl/assets/images/userpassport/" . $userpassportdata;
                        }
                    }
                }

                // getting system settings
                $intercomehash = $twaktohash ='';
            
                $negativeuser =0;
                $checkUserWalletBal =$db_call_class->selectRows("userwallet","id",
                [   
                    [
                        ['column' =>'walletbal', 'operator' =>'<', 'value' =>0],
                        ['column' =>'userid', 'operator' =>'=', 'value' =>$user_id],
                        'operator'=>'AND'
                    ]
                ],);
                if (!$utility_class_call->input_is_invalid( $checkUserWalletBal)) {
                    $negativeuser =1;
                    // notify admin and ban user
                    $updateddate= $db_call_class->updateRows("users",["status"=> 0],
                    [  
                        [
                            ['column' =>'id', 'operator' =>'=', 'value' =>$userid]
                        ]
                    ]  );
                }
                $totalrefered=0;
                $referalCount =$db_call_class->selectRows("users","COUNT(id) AS num",
                [   
                    [
                        ['column' =>'referby', 'operator' =>'=', 'value' =>$refcode],
                        ['column' =>'referby', 'operator' =>'=', 'value' =>$username],
                        'operator'=>'OR'
                    ]
                ],);
                if (!$utility_class_call->input_is_invalid($referalCount)) {
                    $totalrefered=$referalCount[0]['num'];
                }

                 // Fetch all user wallet data with a join on the currency system
                 $allUserWalletData = $db_call_class->selectRows(
                    "userwallet",
                    "userwallet.currencytag, wallettrackid, walletbal, walletpendbal, walletescrowbal, subwallet_type, currencysystem.sign, name, CONCAT('$base_url', currencysystem.imglink) AS coinimg, activatesend, activatereceive",
                    [
                        [
                            ['column' => 'userwallet.userid', 'operator' => '=', 'value' => $user_id],
                            ['column' => 'userwallet.currencytag', 'operator' => '!=', 'value' => 'PN76'],
                            ['column' => 'userwallet.subwallet_type', 'operator' => '=', 'value' => 0],
                        ]
                    ],
                    [
                        'joins' => [
                            [
                                'type' => 'LEFT',
                                'table' => 'currencysystem',
                                'condition' => 'currencysystem.currencytag = userwallet.currencytag'
                            ]
                        ],
                    ]
                );
                foreach ($allUserWalletData as &$item) {
                    if (isset($item['currencytag']) && $item['currencytag'] == 'USD256') {
                        $currencyTag = $item['currencytag'];

                        // Initialize balance additions
                        $mainBalToAdd = 0;
                        $mainPendBalToAdd = 0;
                        $mainEscrowBalToAdd = 0;

                        // Fetch subwallet data for the specific currency tag
                        $subwalletData = $db_call_class->selectRows(
                            "userwallet",
                            "userwallet.currencytag, walletbal, walletpendbal, walletescrowbal, subwallet_type, subwallet_tag",
                            [
                                [
                                    ['column' => 'userwallet.userid', 'operator' => '=', 'value' => $user_id],
                                    ['column' => 'userwallet.currencytag', 'operator' => '=', 'value' => $currencyTag],
                                    ['column' => 'userwallet.subwallet_type', 'operator' => '>', 'value' => 0],
                                ]
                            ]
                        );

                        foreach ($subwalletData as $subItem) {
                            $subMainBal = $subItem['walletbal'];
                            $subPendBal = $subItem['walletpendbal'];
                            $subEscrowBal = $subItem['walletescrowbal'];
                            $subwallet_type = $subItem['subwallet_type'];
                            $subwallet_tag = $subItem['subwallet_tag'];

                            if ($subMainBal > 0 || $subPendBal > 0) {
                                // Get live coin value and use it to convert
                                $subwalletCoinData = $db_call_class->selectRows(
                                    "coinproducts",
                                    "livecoinvalue",
                                    [
                                        [
                                            ['column' => 'subwallettag', 'operator' => '=', 'value' => $subwallet_tag],
                                            ['column' => 'subwallettype', 'operator' => '=', 'value' =>  $subwallet_type ],
                                        ]
                                    ]
                                );
                                if (!$utility_class_call->input_is_invalid($subwalletCoinData)){
                                    $liveValue= $subwalletCoinData[0]['livecoinvalue'];
                                    if ($liveValue != 0) {
                                        $mainBalToAdd += floor(($subMainBal * $liveValue) * 100) / 100;
                                        $mainPendBalToAdd += floor(($subPendBal * $liveValue) * 100) / 100;
                                        $mainEscrowBalToAdd += floor(($subEscrowBal * $liveValue) * 100) / 100;
                                    }
                                }
                            }
                        }

                        // Update the main wallet balances
                        $item['walletbal'] = strval($item['walletbal'] + $mainBalToAdd);
                        $item['walletpendbal'] = strval($item['walletpendbal'] + $mainPendBalToAdd);
                        $item['walletescrowbal'] = strval($item['walletescrowbal'] + $mainEscrowBalToAdd);
                    }
                }


           
              
                if(strlen($profile_pic)>4){
                    $profilelink="$systembaseurl" . $profile_pic;
                }

                $usercashback='0';
                $bankData =$db_call_class->selectRows("userwallet","walletbal",
                [   
                    [
                        ['column' =>'userid', 'operator' =>'=', 'value' =>$user_id],
                        ['column' =>'currencytag', 'operator' =>'=', 'value' =>'PN76'],
                        'operator'=>'AND'
                    ]
                ]);
                if (!$utility_class_call->input_is_invalid($bankData)) {
                    $usercashback=$bankData[0]['walletbal'];
                }

                if (isset($documentlinks)) {
                    // Split the documentlinks string by comma
                    $image_urls = explode(',', $documentlinks);

                   // Remove any empty elements in case the split left an empty string
                    $filtered_array = array_filter($image_urls, function($value) {
                        return !empty($value);
                    });

                    // Trim whitespace and add base URL
                    $full_urls = array_map(function($url) use ($base_url) {
                        return $base_url . trim($url);
                    }, $filtered_array);

                    $full_urls = array_filter($full_urls, function($url) {
                        return !empty($url);
                    });

                    // Update the documentlinks field
                    $documentlinks = $full_urls;
                }

                if ($negativeuser == 0) {
                    if ($emailverified == 1) {
                        $maindata = [
                            'bank_holder_name'=>$bank_holder_name,
                            'bank_name'=>$bank_name,
                            'account_verified'=>$account_verified,
                            'bank_route_no'=>$bank_route_no,
                            'bank_address'=>$bank_address,
                            'bank_acc_no'=>$bank_acc_no,
                            'documentlinks'=>$documentlinks,
                            'servicename'=>$servicename,
                            "user_wallets"=>$allUserWalletData ,
                            "referallink" => $referallink,
                            'cashbackbal'=>$usercashback,
                            "keytag" => $twaktohash,
                            "keytag1" => $intercomehash,
                            "referralcode" => $refcode,
                            "referralcount" =>$totalrefered,
                            "referedby" => $referby,
                            "email" => $email,
                            "firstname" => $firstName,
                            "lastname" => $lastName,
                            "username" => $username,
                            "fullname" => $fullname,
                            "country" => $country,
                            "dob" => $dob,
                            "phone" => $phoneno,
                            "gender" => $gender,
                            "state" => $state,
                            "address1" => $address1,
                            "next_of_kin_name" => $nextkinfname,
                            "next_of_kin_email" => $nextkinemail,
                            "next_of_kin_phoneno" => $nextkinphonenumber,
                            "next_of_kin_address" => $nextkinaddress,
                            "depositnotification" => $depositnotification,
                            "securitynotification" => $securitynotification,
                            "transfernotification" => $transfernotification,
                            "user_level" => $userlevel,
                            "lastpasswordupdate" => $lastpasswordupdate,
                            "lastpinupdate" => $lastpinupdate,
                            "is_phone_verified" => $phoneverified,
                            "is_pin_added" => $pinadded,
                            "kyclevel" => $kycverificationStage,
                            "two_fa_type" => $activate_2fa,
                            "login_with_2fa" =>$activate_login_2fa,
                            "id" => $user_id,
                            "postalcode" => $postalcode,
                            "city" => $city,
                            "created_at" => $created,
                            "activate_biometric" => $activate_biometric,
                            "email_notification" => $email_noti,
                            "sms_notification" => $sms_noti,
                            "push_notification" => $push_noti,
                            "company" => $company,
                            "job_position" => $job_position,
                            "signature_pno" => $sig_phoneno,
                            "passport" => $profilelink,
                        ];
                        $text = API_User_Response::$data_found;
                        $api_status_code_class_call->respondOK($maindata, $text);
                    } else {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$emailNotVerified);
                    }
                } else {
                    $api_status_code_class_call->respondUnauthorized();
                }
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

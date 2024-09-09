<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\Mail_SMS_Responses;
use Config\API_User_Response;

$allowedDomainNames = Constants::BASE_URL;
$apimethod = "POST";
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
header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
// header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
// $utility_class_call->setTimeZoneForUser('');
// header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call = new Config\API_Status_Code;
$db_call_class = new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try {
        $verifytype = isset($_POST['verifytype']) ? $utility_class_call->clean_user_data($_POST['verifytype']) : ''; // 1 is email 2 is phone number 3 forgot password , 4 is 2FA Email to confirm 2fa activate or deactivate, 5 is for BVN, 6 login 2fa email, 7 verify 2fa email
        $medthodis = isset($_POST['method']) ? $utility_class_call->clean_user_data($_POST['method']) : 0; // 1 sms/email 2-Call 3 whatsapp
        $timezone = isset($_POST['timezone']) ? $utility_class_call->clean_user_data($_POST['timezone']) : Constants::COMPANY_TIME_LOCATION_ZONE;

        $whichtoken = 1;
        if ($verifytype == 1||$verifytype == 6) {
            $whichtoken = 2;
        }
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: $whichtoken);
        $user_pubkey = $decodedToken->usertoken;

        $getuserattached = $db_call_class->selectRows(
            "users",
            "id,email,emailverified,phoneno,phoneverified",
            [
                [
                    ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey],
                ]
            ]
        );

        // check if method is 2 call and get time zone to check if the time is between 9-5 and not sunday, check if user have already sent sms like 3 times
        // Get the current time and day
        $utility_class_call->setTimeZoneForUser($timezone);
        $currentHour = date('G'); // 24-hour format of an hour without leading zeros (0 through 23)
        $currentDay = date('w'); // Numeric representation of the day of the week (0 for Sunday, 6 for Saturday)

        $getoldotpsent = [];
        $getoldotpsent2=[];
        $email_verified = 0;
        $phone_verified = 0;
        $totalfailedsms=0;
        if (!$utility_class_call->input_is_invalid($getuserattached)) {
            $userdata = $getuserattached[0];
            $userid = $userdata['id'];
            $email_verified = $userdata['emailverified'];
            $phone_verified = $userdata['phoneverified'];
            // checking if sms is sent is not older than 1 min
            $getoldotpsent = $db_call_class->selectRows(
                "system_otps",
                "created_at",
                [
                    [
                        ['column' => 'TIMESTAMPDIFF(MINUTE, created_at, NOW())', 'operator' => '<', 'value' => 1],
                        ['column' => 'user_id', 'operator' => '=', 'value' => $userid],
                        ['column' => 'verification_type', 'operator' => '=', 'value' =>$verifytype],
                        'operator' => 'AND'
                    ]
                ]
            );
            // check if already sent for the day
            if($medthodis==2||$medthodis==3){//call of whatsapp should be 1 per day
                $getoldotpsent2 = $db_call_class->selectRows(
                    "system_otps",
                    "created_at",
                    [
                        [
                            ['column' => 'method_used', 'operator' => '=', 'value' => $medthodis],
                            ['column' => 'user_id', 'operator' => '=', 'value' => $userid],
                            ['column' => 'DATE(created_at)', 'operator' => '=', 'value' =>'CURDATE()'],
                            'operator' => 'AND'
                        ]
                    ]
                );
            }

            
            $whereclause=    
            [   
                [
                    ['column' => 'user_id', 'operator' => '=', 'value' => $userid],
                ]
                ];
            //count how many rows
            $totalfailedsms =$db_call_class->checkIfRowExistAndCount("system_otps",$whereclause);
        }
        // send error if ur is not in the database
        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        } else  if ($utility_class_call->input_is_invalid($verifytype) || ($verifytype != 1 && $verifytype != 2&& $verifytype != 3&& $verifytype != 4&&$verifytype != 6&& $verifytype != 5&& $verifytype != 7)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid('Verify type'));
        }
        else  if (($medthodis==2||$medthodis==3)&& !$utility_class_call->input_is_invalid($getoldotpsent2)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$otpSendAlreadyToday);
        } 
         else  if ($medthodis==1 && !$utility_class_call->input_is_invalid($getoldotpsent)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$otpsentalready);
        }  else  if ($email_verified == 1 && $verifytype == 1) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$emailAlreadyVerified);
        } else  if ($phone_verified == 1 && $verifytype == 2) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$phonenumberAlreadyVerified);
        } else  if (Constants::PRESENT_CALL_OTP_SYSTEM == 1 &&$medthodis==2 && $currentDay == 0) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$phoneCallNotAllowedToday);
        }else  if (Constants::PRESENT_CALL_OTP_SYSTEM == 1 &&$medthodis==2 && ($currentHour < 9 || $currentHour > 17 )) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$phoneCallNotAllowedToday);
        }else  if (Constants::PRESENT_CALL_OTP_SYSTEM == 1 &&$medthodis==2 && $totalfailedsms<2) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$trysmsbeforephonecall);
        }else {
            $useridentity = "";
            $user_id = $userdata['id'];
            $user_email = $userdata['email'];
            $phone = $userdata['phoneno'];

            if ($verifytype == 1||$verifytype == 4||$verifytype == 6||$verifytype == 7) {
                $useridentity = $user_email;
            } else if ($verifytype == 2) {
                $useridentity = $phone;
            }
            // set expireTime of the token to 5 minutes
            $expiresin = 5;
            $totaldigit=4;
            if($verifytype==4){
            $totaldigit=6;
            }
            // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
            $otp = $db_call_class->createUniqueRandomStringForATableCol($totaldigit, "system_otps", "otp", "", true, false, false);
            // generating  token
            // $length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters
            $token =  $db_call_class->createUniqueRandomStringForATableCol(18, "system_otps", "token", "", true, true, true);
            $status = 0;
            $createdOtpId = $db_call_class->insertRow("system_otps", ["user_id" => $user_id, "useridentity" => $useridentity, 'token' => $token, 'verification_type' => $verifytype, "otp" => $otp, 'forwho' => 1, 'method_used' => $medthodis, 'status' => $status,]);
            if ($createdOtpId > 0) {
                if ($verifytype == 1||$verifytype == 4||$verifytype == 6||$verifytype == 7) {
                    $sent = Mail_SMS_Responses::sendMailOTP($user_id, $otp, $token, $user_email);
                    if ($sent) {
                        // update status as sent
                        $db_call_class->updateRows(
                            "system_otps",
                            ["status" => 1],
                            [
                                [
                                    ['column' => 'id', 'operator' => '=', 'value' => $createdOtpId]
                                ]
                            ]
                        );
                        # code...
                        $maindata['sentto'] = $user_email;
                        $maindata = [$maindata];
                        $text = API_User_Response::$emailotpSentSuccessfully;
                        $api_status_code_class_call->respondOK($maindata, $text);
                    } else {
                        $api_status_code_class_call->respondInternalError(API_User_Response::$errorSendingMail, API_User_Response::$errorSendingMail);
                    }
                } 
                // else if ($verifytype == 2) {
                //     $smssentni2 = false;
                //     if ($medthodis == 1) {
                //         $smssentni2 =  Mail_SMS_Responses::sendSMSOTP($user_id, $otp, $phone);
                //     }else if ($medthodis == 3) {
                //         $smssentni2 = Mail_SMS_Responses::sendWhatsappOTP($user_id, $otp, $phone);
                //     } else  if ($medthodis == 2) {
                //         TG OTP call OTP
                //         if (Constants::PRESENT_CALL_OTP_SYSTEM == 1) {
                //             $smssentni2 = Mail_SMS_Responses::call_user_the_otp_tg($user_id, $otp, $token, $phone, "$expiresin Minutes");
                //         } else {
                //             $smssentni2 = Mail_SMS_Responses::call_user_the_otp($user_id, $otp, $phone, "$expiresin Minutes");
                //         }
                //     }
                //     if ($smssentni2) {
                //         if (Constants::PRESENT_CALL_OTP_SYSTEM != 1) {
                //             $db_call_class->updateRows(
                //                 "system_otps",
                //                 ["status" => 1],
                //                 [
                //                     [
                //                         ['column' => 'id', 'operator' => '=', 'value' => $createdOtpId]
                //                     ]
                //                 ]
                //             );
                //         }
                //         $maindata['sentto'] = $phone;
                //         $maindata = [$maindata];
                //         $text = API_User_Response::$smsSentSuccessfully;
                //         $api_status_code_class_call->respondOK($maindata, $text);
                //     } else {
                //         $api_status_code_class_call->respondInternalError(API_User_Response::$errorSendingSms, API_User_Response::$errorSendingSms);
                //     }
                // }
            } else {
                $api_status_code_class_call->respondInternalError($createdOtpId);
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

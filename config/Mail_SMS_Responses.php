<?php

namespace Config;
use DatabaseCall\Users_Table;

/**
 * System Messages Class
 *
 * PHP version 5.4
 */
class Mail_SMS_Responses extends DB_Connect
{
    // ALL FUNCTIONS TO SEND SETUP
    public static function sendThePhpMailerMail($subject, $emailTo,  $text,$message, $toname){
        $mail = new \PHPMailer\PHPMailer\PHPMailer;
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = Constants::MAILHOST;
        $mail->SMTPAuth = true;
        $mail->Port = Constants::MAILPORT;
        $mail->SMTPSecure = 'ssl';
        $mail->Username = Constants::MAILSENDER;
        $mail->Password = Constants::MAILSENDERPASS;
        $mail->setFrom(Constants::MAILSENDER, Constants::APP_NAME);
        $mail->addReplyTo(Constants::MAILSENDER, Constants::APP_NAME);
        $mail->addAddress($emailTo, $toname);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody =$text;
        //$mail->addAttachment('/home/darth/star_wars.mp3', 'Star_Wars_music.mp3');
        if (!$mail->send()) {
                // return $mail->ErrorInfo;
                // echo 'Mailer Error: '.$mail->ErrorInfo;
            // exit;
            return false;

        } else {
            return true;
            //echo 'The email message was sent.';
        }
    }
    // for generic sms, like promotonal sms
  
    // for OTP sms

  
    // ALL FUNCTIONS TO SEND SETUP
    public static function sendUserMail($subject,$toemail,$msgintext,$messageinhtml){
        // 1 sendGrid, 2
        $mailsent=false;
        $activemailsystem=2;
        if($activemailsystem==1){
            // $mailsent=self::sendWithSenGrid($subject,$toemail,$msgintext,$messageinhtml);
        }else if($activemailsystem==2){
            $mailsent=self::sendThePhpMailerMail($subject,$toemail,$msgintext,$messageinhtml,''); 
        }
        return $mailsent;
    }
    public static function sendUserSMS($sendto,$smstosend){// send to is phone number, smsto send (call the function in the smstemplate)
        // 1 Termi, 2 kudi 3 smart solution
        $withoutplus= str_replace('+','', $sendto);
        $smssent=false;
        $activemailsystem=1;
        if($activemailsystem==1){

        }
        return $smssent;
    }
    public static function sendUserSMSOTP($sendto,$smstosend){// send to is phone number, smsto send (call the function in the smstemplate)
        // 1 Termi, 2 kudi 3 smart solution
        $withoutplus= str_replace('+','', $sendto);


        $smssent=false;
        $activemailsystem=1;
        if($activemailsystem==1){
        }
        return $smssent;
    }

    /**
     * Used for sending push notifications to devices
     * 
     */
    public static function isFireBasePushNotifiSent($userdata, $notibody, $notisubtitle, $notititle, $msgbody, $msgtitle) {
        $deviceToken=$userdata['fcm'];

      
        $url = 'https://fcm.googleapis.com/fcm/send';
        if(strlen($deviceToken)>=3){
            $serverKey = Constants::FIREBASE_SERVER_KEY;
            // 'image' => 'https://imagelink',
            $arr =  array(
                'data' =>
                array(
                    'title' => $msgtitle,
                    'body' => $msgbody,
                ),
                'notification' =>
                array(
                    'title' => $notititle,
                    'subtitle' => $notisubtitle,
                    'body' => $notibody,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default'
                ),
                'to' => $deviceToken,
            );
            $params =  json_encode($arr);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                //u change the url infront based on the request u want
                CURLOPT_URL => $url,
                CURLOPT_POSTFIELDS => $params,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //change this based on what u need post,get etc
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json",
                    "Authorization: " . $serverKey . ""
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return false;
            } else {
                $balresp = json_decode($response);
                $sent = $balresp->success;
                if ($sent == 1) {
                    return true;
                } 
            }
        }
        return false;
    }
    //  this function is used to get the system specific functions

    
    // Below functions are called whenever a user requets for reset password, where they would input their mail

    // LOGIN MAIL

    public static function loginHTML($sessioncode,$userdata){
        $usernameis =$userdata['username'];
        //  //  if you need to pick any data of the user , check above for the data field name and call it as seen below
        $appname =  Constants::APP_NAME;
        $baseurl =  Constants::BASE_URL;
        $supportemail = Constants::SUPPORT_EMAIL ;
        $logourl =  Constants::LOGO_URL;

        $systemData =DB_Calls_Functions::selectRows("userloginsessionlog","browser,ipaddress",
        [   
            [
                ['column' =>'sessioncode', 'operator' =>'=', 'value' =>$sessioncode],
                ['column' =>'username', 'operator' =>'=', 'value' =>$usernameis],
                'operator'=>'AND'
            ]
        ],['limit'=>1]);
        if (!Utility_Functions::input_is_invalid($systemData)) {
            $ddatafound = $systemData[0];
            $browser=$ddatafound['browser'];
            $ipaddress=$ddatafound['ipaddress'];
        }

        $messagetitle="Login Notification";
        $greetingText = "Hello $usernameis.";
        $headtext = "We noticed you just logged in from a new ip address. If this was not you, kindly check your account integrity.";
        $bottomtext = "If you have any questions, don't hesitate to reach us via our several support channels, or open a support ticket by sending a mail to <a href='mailto:$supportemail' style='text-decoration: none; color: #0ab930; letter-spacing: .2px; font-weight: 600;  font-size: 14px;'>$supportemail</a>.";
        // adding link and button of link use below
        $calltoaction = false; // set as true and add details below
        $calltoactionlink = "";
        $calltoactiontext = "";
        // adding link and button of link use below
        $buttonis = "";
        
        $mailtemplate = '
                        <!DOCTYPE html>
                    <html lang="en" style="--white: #fff; --green-dark: #0a6836; --green-light: #0ab930; --green-lighter: #12a733; --black: #000;">
                        <head>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <meta http-equiv="Content-Type" content="text/html;text/css; charset=UTF-8">
                            
                            <title>'.$messagetitle.'</title>
                            
                        </head>
                        <body style="font-family: system-ui !important;" bgcolor="#f5f6fa">
                                <div class="wrapper" style="position: relative; background-color: #f5f6fa; min-height: 100vh;">
                                <div class="wrapper__inner" style="min-height: 100%; margin-inline: auto; padding: 2.5rem 0;max-width: 620px;margin:auto !important;">
                                    <div class="template__top d-none d-md-block" style="margin-bottom: 1.7rem;" align="start">
                                        <div class="template__top__inner logo" style="" align="center"><a href="#" style="text-decoration: none;"><img src="'.$logourl.'" alt="'.$appname.' logo" class="img-fluid" loading="lazy" style="max-width: 200px;"></a></div>
                                    </div>
                                    <div class="template__body" style="margin-top: 3rem; background-color: #fff; padding: 1.8rem 1.5rem 1.5rem;">
                                        <div class="template__body__inner">
                                            
                                            <div class="body__content" style="color:black;">
                                                <div class="head"><h3 style="font-weight: 900; color: #0ab930; font-size: 1.3rem; letter-spacing: .4px; margin: 0;">'.$messagetitle.'</h3></div> <br>
                                                <div class="body"><p style="font-weight: bolder; font-size: 1rem; color: #000; margin: 0;">Hi '.$usernameis.',</p></div> <br>
                                                <div class="text__content" style="font-size: 14px; letter-spacing: -0.1px; word-spacing: 1px;">
                                                    <span>We have detected a new login to your '.$appname.' account</span> <br><br>
                                                    IP Address: '.$ipaddress.' <br>
                                                    Device: '.$browser.' <br>
                                                    <span>For security reasons, we want to make sure it was you. If so, kindly disregard this notice. If you didn\'t login this time, try to change the password and contact<a href="mailto:'.$supportemail.'" style="text-decoration: none; color: #0ab930; letter-spacing: .2px; font-weight: 600; font-size: 14px;">'.$supportemail.'</a></span>
                                                </div> <br>
                                                <div class="body__pre__foot" style="margin-top: 2rem; font-size: 15px;"><p style="font-weight: 600; margin: 0;">Best Regards,</p></div>
                                                <div class="body__foot" style="font-size: 15px;">The '.$appname.' Team.</div>
                                            </div>
                                            <div class="template__footer" style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem;">
                                                <div class="copyright"><small>Copyright © 2022-2023. All rights reserved</small></div>
                                                <div class="logo">
                                                    <a href="" style="text-decoration: none;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveaspectratio="xMidYMid meet" viewbox="0 0 32 32"><path fill="currentColor" d="M31.937 6.093a13.359 13.359 0 0 1-3.765 1.032a6.603 6.603 0 0 0 2.885-3.631a13.683 13.683 0 0 1-4.172 1.579a6.56 6.56 0 0 0-11.178 5.973c-5.453-.255-10.287-2.875-13.52-6.833a6.458 6.458 0 0 0-.891 3.303a6.555 6.555 0 0 0 2.916 5.457a6.518 6.518 0 0 1-2.968-.817v.079a6.567 6.567 0 0 0 5.26 6.437a6.758 6.758 0 0 1-1.724.229c-.421 0-.823-.041-1.224-.115a6.59 6.59 0 0 0 6.14 4.557a13.169 13.169 0 0 1-8.135 2.801a13.01 13.01 0 0 1-1.563-.088a18.656 18.656 0 0 0 10.079 2.948c12.067 0 18.661-9.995 18.661-18.651c0-.276 0-.557-.021-.839a13.132 13.132 0 0 0 3.281-3.396z"></path></svg>
                                                    </a>
                                                    <a href="" style="text-decoration: none;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveaspectratio="xMidYMid meet" viewbox="0 0 32 32"><path fill="currentColor" d="M0 0v32h32V0zm26.583 7.583l-1.714 1.646a.49.49 0 0 0-.193.479v12.089a.497.497 0 0 0 .193.484l1.672 1.646v.359h-8.427v-.359l1.734-1.688c.172-.172.172-.219.172-.479v-9.776l-4.828 12.26h-.651l-5.62-12.26v8.219c-.047.344.068.693.307.943l2.26 2.74v.359H5.087v-.359l2.26-2.74c.24-.25.349-.599.286-.943v-9.5A.816.816 0 0 0 7.362 10L5.357 7.583v-.365h6.229l4.818 10.568l4.234-10.568h5.943z"></path></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </body>       
                    </html>
    ';

        return $mailtemplate;
    }
    public static function loginText($sessioncode,$userdata){      
        $usernameis =$userdata['username'];
        //  //  if you need to pick any data of the user , check above for the data field name and call it as seen below
        $appname =  Constants::APP_NAME;
        $baseurl =  Constants::BASE_URL;
        $supportemail = Constants::SUPPORT_EMAIL ;
        $logourl =  Constants::LOGO_URL;

        $systemData =DB_Calls_Functions::selectRows("userloginsessionlog","browser,ipaddress",
        [   
            [
                ['column' =>'sessioncode', 'operator' =>'=', 'value' =>$sessioncode],
                ['column' =>'username', 'operator' =>'=', 'value' =>$usernameis],
                'operator'=>'AND'
            ]
        ],['limit'=>1]);
        if (!Utility_Functions::input_is_invalid($systemData)) {
            $ddatafound = $systemData[0];
            $browser=$ddatafound['browser'];
            $ipaddress=$ddatafound['ipaddress'];
        }
        $mailtext = "Dear $usernameis,We noticed you just logged in from a new ip address. If this was not you, kindly check your account integrity. \r\nIP Address:$ipaddress \r\nBrowser:$browser. Powered by $appname";

        return $mailtext;
    }
    public static function loginSubject($userdata){
        $usernameis =$userdata['username'];

        //  //  if you need to pick any data of the user , check above for the data field name and call it as seen below
        $appname =  Constants::APP_NAME;
        $baseurl =  Constants::BASE_URL;
        $supportemail = Constants::SUPPORT_EMAIL ;
        $logourl =  Constants::LOGO_URL;

        $subject="Login Notifications - $appname";
        return $subject;
    }
    public static function sendLoginSMSEMail($userid,$seescode){

        //get user data
        $userdata=DB_Calls_Functions::selectRows("users",'fcm,username,country_id,email,phoneno,email_noti,sms_noti,push_noti',        [   
            [
                ['column' =>'id', 'operator' =>'=', 'value' =>$userid],
            ]
        ]);
        $userdata=$userdata[0];
        $country_id=$userdata['country_id'];
        $sendToEmail=$userdata['email'];
        $sendToPhoneNo=$userdata['phoneno'];
        $email_noti=$userdata['email_noti'];
        $sms_noti=$userdata['sms_noti'];
        $push_noti=$userdata['push_noti'];
        $countrydata=DB_Calls_Functions::selectRows("countries",'phonecode',        [   
            [
                ['column' =>'trackid', 'operator' =>'=', 'value' =>$country_id],
            ]
        ]);
        $phonecode=$countrydata[0]['phonecode'];
        // get country code stuff add country code at phone number
        // its only if zero starts the number we need to remove it and add the country code
        if ($sendToPhoneNo[0] === '0') {
            $sendToPhoneNo = substr_replace($sendToPhoneNo, $phonecode, 0, 1);
        }else{
            $sendToPhoneNo="$phonecode$sendToPhoneNo";
        }

        $subject = self::loginSubject($userdata); 
        $messageText = self::loginText($seescode,$userdata);
        $messageHTML = self::loginHTML($seescode,$userdata);
        $notisubtitle=$subject;
        $notititle=$subject;
        $msgtitle=$subject;
        $msgbody=$messageText;
        $notibody=$messageText;
        if($email_noti==1){
            self::sendUserMail($subject,$sendToEmail,$messageText, $messageHTML);
        }
        if($sms_noti==1){
            self::sendUserSMS($sendToPhoneNo,$messageText);
        }
        if($push_noti==1){
            self:: isFireBasePushNotifiSent($userdata, $notibody, $notisubtitle, $notititle, $msgbody, $msgtitle);
        }
        $noticode=DB_Calls_Functions::createUniqueRandomStringForATableCol(5,"usernotifications","notificationcode","",true,true,false);
        DB_Calls_Functions::insertRow("usernotifications",['userid '=>$userid,'notificationtext'=>$messageText,'notificationtitle'=>$notititle,'notificationtype'=>1,'orderrefid'=>'','notificationstatus'=>1,'notificationcode'=> $noticode,'forwho'=>1,'seenbyuser'=>0]);
    }


 
     // Resent Password Mail
     public static function resetPasswordSuccessHTML($userdata)
     {
         $usernameis =$userdata['username'];
         //  //  if you need to pick any data of the user , check above for the data field name and call it as seen below
         $appname = Constants::APP_NAME;
         $baseurl = Constants::BASE_URL;
         $supportemail = Constants::SUPPORT_EMAIL;
         $logourl = Constants::LOGO_URL;
 
         
         $messagetitle = "Account recovery";
         $greetingText = "Hello $usernameis.";
         $headtext = "Password reset successfully";
 
         $bottomtext = "If you have any questions, don't hesitate to reach us via our several support channels, or open a support ticket by sending a mail to <a href='mailto:$supportemail' style='text-decoration: none; color: #0ab930; letter-spacing: .2px; font-weight: 600;  font-size: 14px;'> $supportemail</a>. <br> We are excited to have you Cardified.";
         // adding link and button of link use below
         $calltoaction = false; // set as true and add details below
         $calltoactionlink = "";
         $calltoactiontext = "";
         // adding link and button of link use below
         $buttonis = "";
 
         $mailtemplate = '
         <!DOCTYPE html>
                     <html lang="en" style="--white: #fff; --green-dark: #0a6836; --green-light: #0ab930; --green-lighter: #12a733; --black: #000;">
                         <head>
                             <meta charset="utf-8">
                             <meta name="viewport" content="width=device-width, initial-scale=1.0">
                             <meta http-equiv="Content-Type" content="text/html;text/css; charset=UTF-8">
                             <title>' . $messagetitle . '</title>
                              
                         </head>
                         <body style="font-family: system-ui !important;" bgcolor="#f5f6fa">
                                 <div class="wrapper" style="position: relative; background-color: #f5f6fa; min-height: 100vh;">
                                 <div class="wrapper__inner" style="min-height: 100%; margin-inline: auto; padding: 2.5rem 0;max-width: 620px;margin:auto !important;">
                                     <div class="template__top d-none d-md-block" style="margin-bottom: 1.7rem;" align="start">
                                         <div class="template__top__inner logo" style="" align="center"><a href="#" style="text-decoration: none;"><img src="' . $logourl . '" alt="' . $appname . ' logo" class="img-fluid" loading="lazy" style="max-width: 200px;"></a></div>
                                     </div>
                                     <div class="template__body" style="margin-top: 3rem; background-color: #fff; padding: 1.8rem 1.5rem 1.5rem;">
                                         <div class="template__body__inner">
                                             <div class="body__content" style="color:black;">
                                                 <div class="head"><h3 style="font-weight: 900; color: #0ab930; font-size: 1.3rem; letter-spacing: .4px; margin: 0;">' . $messagetitle . '</h3></div> <br>
                                                 <div class="body"><p style="font-weight: bolder; font-size: 1rem; color: #000; margin: 0;">Hi ' . $usernameis . ',</p></div> <br>
                                                 <div class="text__content" style="font-size: 14px; letter-spacing: -0.1px; word-spacing: 1px;">
                                                     <span>' . $headtext . '</span>
                                                     <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
                                                       <tbody>
                                                         <tr>
                                                             ' . $buttonis . '
                                                         </tr>
                                                       </tbody>
                                                     </table>
                                                     <span>' . $bottomtext . '</span></div> <br>
                                                     
                                                 <div class="body__pre__foot" style="margin-top: 2rem; font-size: 15px;"><p style="font-weight: 600; margin: 0;">Thanks,</p></div>
                                                 <div class="body__foot" style="font-size: 15px;">The ' . $appname . ' Team.</div>
                                             </div>
                                             <div class="template__footer" style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem;">
                                                 <div class="copyright"><small>Copyright © 2022-2023. All rights reserved</small></div>
                                                 <div class="logo">
                                                     <a href="" style="text-decoration: none;">
                                                         <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveaspectratio="xMidYMid meet" viewbox="0 0 32 32"><path fill="currentColor" d="M31.937 6.093a13.359 13.359 0 0 1-3.765 1.032a6.603 6.603 0 0 0 2.885-3.631a13.683 13.683 0 0 1-4.172 1.579a6.56 6.56 0 0 0-11.178 5.973c-5.453-.255-10.287-2.875-13.52-6.833a6.458 6.458 0 0 0-.891 3.303a6.555 6.555 0 0 0 2.916 5.457a6.518 6.518 0 0 1-2.968-.817v.079a6.567 6.567 0 0 0 5.26 6.437a6.758 6.758 0 0 1-1.724.229c-.421 0-.823-.041-1.224-.115a6.59 6.59 0 0 0 6.14 4.557a13.169 13.169 0 0 1-8.135 2.801a13.01 13.01 0 0 1-1.563-.088a18.656 18.656 0 0 0 10.079 2.948c12.067 0 18.661-9.995 18.661-18.651c0-.276 0-.557-.021-.839a13.132 13.132 0 0 0 3.281-3.396z"></path></svg>
                                                     </a>
                                                     <a href="" style="text-decoration: none;">
                                                         <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveaspectratio="xMidYMid meet" viewbox="0 0 32 32"><path fill="currentColor" d="M0 0v32h32V0zm26.583 7.583l-1.714 1.646a.49.49 0 0 0-.193.479v12.089a.497.497 0 0 0 .193.484l1.672 1.646v.359h-8.427v-.359l1.734-1.688c.172-.172.172-.219.172-.479v-9.776l-4.828 12.26h-.651l-5.62-12.26v8.219c-.047.344.068.693.307.943l2.26 2.74v.359H5.087v-.359l2.26-2.74c.24-.25.349-.599.286-.943v-9.5A.816.816 0 0 0 7.362 10L5.357 7.583v-.365h6.229l4.818 10.568l4.234-10.568h5.943z"></path></svg>
                                                     </a>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     
                                 </div>
                             </div>
                         </body>       
                     </html>';
 
         return $mailtemplate;
     }
     public static function resetPasswordSuccessText($userdata)
     {
         $usernameis =$userdata['username'];
         //  //  if you need to pick any data of the user , check above for the data field name and call it as seen below
         $appname = Constants::APP_NAME;
         $baseurl = Constants::BASE_URL;
         $supportemail = Constants::SUPPORT_EMAIL;
         $logourl = Constants::LOGO_URL;
 
         $mailtext = "Dear $usernameis, You have successfully reset your password. Powered by $appname";
 
         return $mailtext;
     }
     public static function resetPasswordSuccessSubject($userdata)
     {
         $usernameis =$userdata['username'];
         //  //  if you need to pick any data of the user , check above for the data field name and call it as seen below
         $appname = Constants::APP_NAME;
         $baseurl = Constants::BASE_URL;
         $supportemail = Constants::SUPPORT_EMAIL;
         $logourl = Constants::LOGO_URL;
 
         $subject = "$appname - Password recovered successfully";
         return $subject;
     }
     public static function sendResetPasswordSuccessSMSEMail($userid)
     {
         //get user data
         $userdata=DB_Calls_Functions::selectRows("users",'fcm,username,country_id,email,phoneno,email_noti,sms_noti,push_noti',        [   
             [
                 ['column' =>'id', 'operator' =>'=', 'value' =>$userid],
             ]
         ]);
         $userdata=$userdata[0];
         $country_id=$userdata['country_id'];
         $sendToEmail=$userdata['email'];
         $sendToPhoneNo=$userdata['phoneno'];
         $email_noti=$userdata['email_noti'];
         $sms_noti=$userdata['sms_noti'];
         $push_noti=$userdata['push_noti'];
         $countrydata=DB_Calls_Functions::selectRows("countries",'phonecode',        [   
             [
                 ['column' =>'trackid', 'operator' =>'=', 'value' =>$country_id],
             ]
         ]);
         $phonecode=$countrydata[0]['phonecode'];
         // get country code stuff add country code at phone number
         // its only if zero starts the number we need to remove it and add the country code
         if ($sendToPhoneNo[0] === '0') {
             $sendToPhoneNo = substr_replace($sendToPhoneNo, $phonecode, 0, 1);
         }else{
             $sendToPhoneNo="$phonecode$sendToPhoneNo";
         }
 
         $subject = self::resetPasswordSuccessSubject($userdata);
         $messageText = self::resetPasswordSuccessText($userdata);
         $messageHTML = self::resetPasswordSuccessHTML($userdata);
         $notisubtitle = $subject;
         $notititle = $subject;
         $msgtitle = $subject;
         $msgbody = $messageText;
         $notibody = $messageText;
         if($email_noti==1){
             self::sendUserMail($subject,$sendToEmail,$messageText, $messageHTML);
         }
         if($sms_noti==1){
             self::sendUserSMS($sendToPhoneNo,$messageText);
         }
         if($push_noti==1){
             self:: isFireBasePushNotifiSent($userdata, $notibody, $notisubtitle, $notititle, $msgbody, $msgtitle);
         }
     }
 

    
    // OTP SYSTEM
    public static function sendOTPtoEmailHTML($userdata, $token,$otp){
        $usernameis =$userdata['username'];
        $appname =  Constants::APP_NAME;
        $baseurl =  Constants::BASE_URL;
        $supportemail = Constants::SUPPORT_EMAIL ;
        $logourl =  Constants::LOGO_URL;
  
          $resetlink=$baseurl."auth/verify.html?token=".$token."&code=".$otp;
          $messagetitle="Verification";
          $greetingText = "Hello $usernameis.";
          $headtext = "Kindly use the verification code below, If this wasn't initiated by you, kindly check your $appname account integrity.<br>Your OTP is <h5 align='center' style='font-size:23px;letter-spacing:1.5px;'>$otp</h5>";
          $bottomtext = "If you have any questions, don't hesitate to reach us via our several support channels, or open a support ticket by sending a mail to <a href='mailto:$supportemail' style='text-decoration: none; color: #0ab930; letter-spacing: .2px; font-weight: 600;  font-size: 14px;'>$supportemail</a>.";
          // adding link and button of link use below
          $calltoaction = false; // set as true and add details below
          $calltoactionlink = "$resetlink";
          $calltoactiontext = "Verify";
          // adding link and button of link use below
          $buttonis = "";
       if ($calltoaction == true) {
              $buttonis = ' <td align="center">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tbody>
                  <tr>
                    <td> <a style="background-color:#0ab930;border: solid 1px #0ab930;
                                  border-radius: 5px;
                                  box-sizing: border-box;
                                  color: white;
                                  display: inline-block;
                                  font-size: 14px;
                                  font-weight: bold;
                                  margin: 0;
                                  padding: 12px 25px;
                                  text-decoration: none;
                                  text-transform: capitalize;" href="' . $calltoactionlink . '" target="_blank">' . $calltoactiontext . '</a> </td>
                  </tr>
                </tbody>
              </table>
            </td>';
          }
  
          $mailtemplate = '
          <!DOCTYPE html>
                      <html lang="en" style="--white: #fff; --green-dark: #0a6836; --green-light: #0ab930; --green-lighter: #12a733; --black: #000;">
                          <head>
                              <meta charset="utf-8">
                              <meta name="viewport" content="width=device-width, initial-scale=1.0">
                              <meta http-equiv="Content-Type" content="text/html;text/css; charset=UTF-8">
                              <title>'.$messagetitle.'</title>
                               
                          </head>
                          <body style="font-family: system-ui !important;" bgcolor="#f5f6fa">
                                  <div class="wrapper" style="position: relative; background-color: #f5f6fa; min-height: 100vh;">
                                  <div class="wrapper__inner" style="min-height: 100%; margin-inline: auto; padding: 2.5rem 0;max-width: 620px;margin:auto !important;">
                                      <div class="template__top d-none d-md-block" style="margin-bottom: 1.7rem;" align="start">
                                          <div class="template__top__inner logo" style="" align="center"><a href="#" style="text-decoration: none;"><img src="'.$logourl.'" alt="'.$appname.' logo" class="img-fluid" loading="lazy" style="max-width: 200px;"></a></div>
                                      </div>
                                      <div class="template__body" style="margin-top: 3rem; background-color: #fff; padding: 1.8rem 1.5rem 1.5rem;">
                                          <div class="template__body__inner">
                                              <div class="body__content" style="color:black;">
                                                  <div class="head"><h3 style="font-weight: 900; color: #0ab930; font-size: 1.3rem; letter-spacing: .4px; margin: 0;">'.$messagetitle.'</h3></div> <br>
                                                  <div class="body"><p style="font-weight: bolder; font-size: 1rem; color: #000; margin: 0;">Hello '.$usernameis.',</p></div> <br>
                                                  <div class="text__content" style="font-size: 14px; letter-spacing: -0.1px; word-spacing: 1px;">
                                                      <span>'.$headtext.'</span>
                                                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
                                                        <tbody>
                                                          <tr style="margin-bottom:10px">
                                                              ' . $buttonis . '
                                                          </tr>
                                                        </tbody>
                                                      </table>
                                                      <br> <span style="display:block;">'.$bottomtext.'</span></div> 
                                                      
                                                  <div class="body__pre__foot" style="margin-top: 2rem; font-size: 15px;"><p style="font-weight: 600; margin: 0;">Thanks,</p></div>
                                                  <div class="body__foot" style="font-size: 15px;">The '.$appname.' Team.</div>
                                              </div>
                                              <div class="template__footer" style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem;">
                                                  <div class="copyright"><small>Copyright © 2022-2023. All rights reserved</small></div>
                                                  <div class="logo">
                                                      <a href="" style="text-decoration: none;">
                                                          <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveaspectratio="xMidYMid meet" viewbox="0 0 32 32"><path fill="currentColor" d="M31.937 6.093a13.359 13.359 0 0 1-3.765 1.032a6.603 6.603 0 0 0 2.885-3.631a13.683 13.683 0 0 1-4.172 1.579a6.56 6.56 0 0 0-11.178 5.973c-5.453-.255-10.287-2.875-13.52-6.833a6.458 6.458 0 0 0-.891 3.303a6.555 6.555 0 0 0 2.916 5.457a6.518 6.518 0 0 1-2.968-.817v.079a6.567 6.567 0 0 0 5.26 6.437a6.758 6.758 0 0 1-1.724.229c-.421 0-.823-.041-1.224-.115a6.59 6.59 0 0 0 6.14 4.557a13.169 13.169 0 0 1-8.135 2.801a13.01 13.01 0 0 1-1.563-.088a18.656 18.656 0 0 0 10.079 2.948c12.067 0 18.661-9.995 18.661-18.651c0-.276 0-.557-.021-.839a13.132 13.132 0 0 0 3.281-3.396z"></path></svg>
                                                      </a>
                                                      <a href="" style="text-decoration: none;">
                                                          <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveaspectratio="xMidYMid meet" viewbox="0 0 32 32"><path fill="currentColor" d="M0 0v32h32V0zm26.583 7.583l-1.714 1.646a.49.49 0 0 0-.193.479v12.089a.497.497 0 0 0 .193.484l1.672 1.646v.359h-8.427v-.359l1.734-1.688c.172-.172.172-.219.172-.479v-9.776l-4.828 12.26h-.651l-5.62-12.26v8.219c-.047.344.068.693.307.943l2.26 2.74v.359H5.087v-.359l2.26-2.74c.24-.25.349-.599.286-.943v-9.5A.816.816 0 0 0 7.362 10L5.357 7.583v-.365h6.229l4.818 10.568l4.234-10.568h5.943z"></path></svg>
                                                      </a>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                      
                                  </div>
                              </div>
                          </body>       
                      </html>';
  
          return $mailtemplate;
  
    }
    public static function sendOTPText($otp){
        $appname =  Constants::APP_NAME;
        $mailtext = "Your $appname confirmation code is $otp. It expires in 5 minutes.";
        return $mailtext;
    }
    public static function sendOTPSubject(){
        $appname =  Constants::APP_NAME;
    
        $subject="$appname - OTP Code";
        return $subject;
    }
    public static function sendMailOTP($userid,$otp,$token,$sendToEmail){
        // for all mail OTP it verify email otp or forgot password otp
        $userdata=DB_Calls_Functions::selectRows("users",'username',        [   
            [
                ['column' =>'id', 'operator' =>'=', 'value' =>$userid],
            ]
        ]);
        $userdata=$userdata[0];
        $subject = self::sendOTPSubject(); 
        $messageText = self::sendOTPText($otp);
        $messageHTML = self::sendOTPtoEmailHTML($userdata, $token,$otp);
        $sentit=self::sendUserMail($subject,$sendToEmail,$messageText, $messageHTML);
        return $sentit;
    } 
   
}
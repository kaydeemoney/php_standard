<?php

namespace Config;

/**
 * System Messages Class
 *
 * PHP version 5.4
 */
class API_User_Response
{

    /**
     * Welcome message
     *
     * @var string
     */
    // General errors
    public  static $invalidUserDetail="Invalid username or password";
    public  static $invalidreCAPTCHA="reCAPTCHA challenge Verification failed";
    public  static $loginSuccessful="LogIn Successful";
    public  static $level1verification="Level 1 Verification is not yet done";
    public  static $level2verification="Level 2 Verification is not yet done";
    public  static $alreadyVerified="You are already Verified";
    public  static $verificationProcessing="Verification still under processing, try again later";
    public  static $registerSuccessful="User account created successfully, kindly verify your account...";
    public  static $userNotFound="User not found";
    public  static $unauthorized_token="Unauthorized";
    public  static $url_not_valid="URL changed, try again later";
    public  static $user_has_no_access="You are not allowed to access this resource";

    public  static $bvn_pno_not_found="We noticed your BVN does not have a phone number attached, kindly visit the bank to update your BVN";

    public  static $user_permanetly_banned="You Have Been Permanently Banned From this platform with the name associated to your bank account details flagged<br>Contact Support with your user details if you think this was done in error.";

    public static $otpsentalready="You need to wait for at least 1 minute before you can resend";
    public static $otpSendAlreadyToday="You can only use this method once a day.";
    public static $invalidOtporExpired="Invalid or expire OTP inputted";
    public  static $user_account_deleted="Account details deleted from our server";
    public  static $toomanyrequest="Too Many Request";
    public  static $request_method_invalid="Request method used not allowed.";
    public  static $request_not_processed="Request not processed.";
    public  static $request_processed="Request processed successfully.";
    public  static $request_body_invalid="Ensure to input valid details in all fields";
    public  static $wait_small_try_again="Wait for some seconds and try again";
    public  static $coin_not_found="Currency Not Found";
    public  static $invalid_amount="Invalid amount";
    public  static $name_cannot_be_more_than_15="Name cannot be more than 15";
    public  static $error_generating_address="Error generating address, try again later.";
    public  static $max_wallet_reached="You have reached the maximum amount of address you can generate for this Coin";

    
    public  static $bank_type_not_active="Bank type selected is not active";
    public  static $invalid_acc_no="Invalid account number";
    public  static $fileSizeTooLarge="File Size is too Large (Max 2MB)";

    public static function fileSizeTooBig($dataName) { return "$dataName File Size is too Large (Max 2MB)"; }
    public static function minimumWithdrawal($amount) { return "The minumum you can withdraw is $amount"; }

    public  static $onFileAtATime="One file at a time is allowed";
    public  static $fileTypeNotAllowed="File type not allowed";
    public  static $fileinvalid="File Uploaded is not valid";
    public  static $errorUploadingData="Error Uploading data";

    public  static $datasubmttedforcheck="Form submitted successfully, kindly wait while we process your submission.";
    public  static $invalid_house_address="Please provide your house's complete address.";
    public  static $data_found="Data found";
    public  static $data_Valid="Data Valid";
    public  static $data_InValid="Data Invalid";
    public  static $data_not_found="Data Not found";
    public  static $bank_not_found="Bank account not found";
    public  static $wallet_not_found="Wallet not found";
    public  static $user_not_found="User not found";
    public  static $user_wallet_not_found="User wallet not found";
    public  static $security_error=" Sorry, a security error occured, try again later";

  
    public  static $data_updated="Data Updated successfully";
    public  static $data_generated="Data Generated successfully";
    public  static $data_deleted="Data Deleted successfully";
    public  static $error_deleting_record="Date Already Deleted";
    public  static $error_updating_record="Data already updated";
    public  static $data_created="Data Created successfully";
    public  static $error_creating_record="Error Creating Record";
    public  static $already_created_record="Data Already Created";
    public  static $maxBankReached="You have reached max,maximum allowed bank account is 3";
    public  static $internal_error="Oops an error occured, try again later";
    public static $invalidUsername = "Username cannot have whitespace, dot, special characters, or emojis";
    public static $serverUnderMaintainance = "Server Under Maintainance, Try Again Later";
    public static $emailAlreadyVerified = "Email already verified";
    public static $emailVerifiedSuccessFully = "Email verified successfully";
    public static $phoneNotVerified= "Please verify phone number first";
    public static $restrictionLevel= "You need to be level 1 verified";
    public static $restrictionLevel2= "You need to be level 2 verified";
    public static $youCanNotSendToYourself= "You can not send to yourself";
    public static $alreadygenerated_bank_type= "You have already generated this bank type";
    public static $refcodeNotFound= "This refcode does not exist.";
    public static $alreadyredeeemed= "Already Reedeemed a referral code.";
    public static $referalcodeRedeemed= "Referral Code was successfully recorded.";
    public static $couponRedeemed= "Coupon successfully redeemed.";
    public static $failedtoaccesswallet= "Failed to access wallet system";
    public static $waletNotFound= "Wallet system not found";
    public static $insufficientfund= "Insufficient fund";
    public static $invalid_2fa_code= "Invalid 2FA code";
    public static $invalidAddress= "Address is invalid";

    
    public static $cannotRegisterYourRefcode= "Cannot register your own referral code.";

    
    public static $counponcodenotactive= "Code is not active";
    public static $counponcodeExpired= "Code has expired";
    public static $counponUsed= "Coupon already used";
    public static $walletNotFound= "Opps you dont have the wallet, kindly visit the wallet page to generate the wallet";

    
    public static $counponNotForYou= 'You are not the user the code is meant for.';
    

    

    
    public static $couponCategoryUsed='You can only use coupon code of the same category once';
    public static $accountDataNotFound= "Account data not found";
    public static $emailNotVerified = "Unverified email";

    
    public static $verifiedSuccessFully = "Verified successfully";
    public static $phoneNoVerifiedSuccessFully = "Phone number verified successfully";
    public static $phonenumberAlreadyVerified = "Phone number already verified";
    public static $phoneCallNotAllowedToday = "Phone call is currently not available";
    public static $trysmsbeforephonecall= "You need to try SMS before phone call";
    public static $errorSendingMail ="Error sending email. Try again later!";
    public static $errorGeneratingAcc ="Failed to generate account. Try again later!";
    public static $errorSendingSms ="Error sending SMS. Try again later!";
    public static $emailotpSentSuccessfully ="Email Sent Successfully, please check your mail inbox or spam";
    public static $smsSentSuccessfully ="OTP Sent Successfully, please check your phone";
    public static function dataAlreadyExist($dataName) { return "$dataName already exists"; }
    public static function dataNotFound($dataName) { return "$dataName not found"; }
    public static function dataInvalid($dataName) { return "$dataName is invalid"; }
    public static function lengthError($dataName, $length) { return "$dataName cannot be more than $length characters long and cannot contain emojis"; }
    public static function lengthMinMaxError($dataName, $length,$minlen) { return "$dataName cannot be more than $length or lesser than $minlen"; }
    public static $invalidPassword ="For security purpose,Password requires at least 1 lower and upper case character, 1 number, 1 special character and must be at least 6 characters long";

    public static $invalidPin ="The 4-digit PIN must not have repeated or consecutive numbers";

    public static $passwordIncorrect ="Password is not correct";
    public static $oldpasswordIncorrect ="Old Password is not correct";
    public static $pinIncorrect ="Pin is not correct";
    public static $newpasswordCanNotBeOld ="New password can not be the same as old password";
    public static $newpinCanNotBeOld ="New pin can not be the same as old pin";
    public static $welcomeMessage = "Welcome to " . Constants::APP_NAME;
    public  static $errorOccured="An Error occured, Please contact support";


  


    
}
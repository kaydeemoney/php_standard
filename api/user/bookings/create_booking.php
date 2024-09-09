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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1, whocalled: 1);
        $user_pubkey = $decodedToken->usertoken;
        [$getuserattached] = $db_call_class->selectRows("users", "trackid", [[
            ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);
        $service = isset($_POST['service_tid']) ? $utility_class_call->clean_user_data($_POST['service_tid']) : '';
        $state_tid = isset($_POST['state_tid']) ? $utility_class_call->clean_user_data($_POST['state_tid']) : '';
        $address = isset($_POST['address']) ? $utility_class_call->clean_user_data($_POST['address']) : '';
        $description = isset($_POST['description']) ? $utility_class_call->clean_user_data($_POST['description']) : '';
        $job_title = isset($_POST['job_title']) ? $utility_class_call->clean_user_data($_POST['job_title']) : '';
        $coupon_code = isset($_POST['coupon_code']) ? $utility_class_call->clean_user_data($_POST['coupon_code']) : ''; 
        $location_long = isset($_POST['location_long']) ? $utility_class_call->clean_user_data($_POST['location_long']) : ''; 
        $location_lat = isset($_POST['location_lat']) ? $utility_class_call->clean_user_data($_POST['location_lat']) : ''; 
        $time= isset($_POST['time']) ? $utility_class_call->clean_user_data($_POST['time']) : ''; 
        $date = isset($_POST['date']) ? $utility_class_call->clean_user_data($_POST['date']) : ''; 
        $price = isset($_POST['price']) ? $utility_class_call->clean_user_data($_POST['price']) : ''; 
        $images = isset($_FILES['images']) ? $_FILES['images'] : '';

      
        if ($utility_class_call->input_is_invalid($service) || $utility_class_call->input_is_invalid($state_tid) ||  $utility_class_call->input_is_invalid($address) || $utility_class_call->input_is_invalid($description) ||  $utility_class_call->input_is_invalid($job_title) || $utility_class_call->input_is_invalid($coupon_code) || $utility_class_call->input_is_invalid($location_long) || $utility_class_call->input_is_invalid($location_lat) ||  $utility_class_call->input_is_invalid($time)||  $utility_class_call->input_is_invalid($date)||  $utility_class_call->input_is_invalid($price)||  $utility_class_call->input_is_invalid($images)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } else   if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        } else if ($db_call_class->checkIfRowExistAndCount('services', [[['column' => 'trackid', 'operator' => '=', 'value' => $service]]]) === 0) {
            // checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Service"));
        } else if ($db_call_class->checkIfRowExistAndCount('coupons', [[['column' => 'coupoun_code', 'operator' => '=', 'value' => $coupon_code]]]) === 0) {
            // checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Coupon"));
        } else if ($db_call_class->checkIfRowExistAndCount('countries_cities', [[['column' => 'trackid', 'operator' => '=', 'value' =>$state_tid]]]) === 0) {
            // checking if data is valid
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("City"));
        } else{
            $user_tid = $getuserattached['trackid'];
            $taxpaid=0;
            $commision=0;
            $profitCommision=0;
            $profitMade=0;
            // get city commission
            $getAllData = $db_call_class->selectRows("countries_cities","commission",
            [ 
                [
                    ['column' => 'trackid', 'operator' => '=', 'value' =>$state_tid]
                ]
            ]
            );
            if (!$utility_class_call->input_is_invalid($getAllData)) {
                $commision=$getAllData[0]['commission'];
                $taxpaid= round(($commision/100)*$price,2);
            }
            $getAllData = $db_call_class->selectRows("services","commission",[[['column' => 'trackid', 'operator' => '=', 'value' => $service]]]);
            if (!$utility_class_call->input_is_invalid($getAllData)) {
                $profitCommision=$getAllData[0]['commission'];
                $profitMade= round(($profitCommision/100)*$price,2);
            }
            $amouttopay=$profitMade+$price+$taxpaid;
            $allowed =  array('jpg', 'jpeg', 'svg', 'png', 'gif', 'webp', 'jiff');
            $imagelocation = "$folderPath/assets/images/bookings/";
            $imagelocationName = "/assets/images/bookings/";
            $tmpimagelocation = "$folderPath/assets/images/tmp/";
            $imagesarevalid=0;
            $name="booking$user_tid";
            if (!empty($images['name'])) {
                // multiple file upload
                foreach ($images['name'] as $key => $val) {
                    // File upload path
                    $fileData = $images;
                    $fileName = $fileData['name'][$key];
                    $targetFilePath = $imagelocation . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                    $fileType_lc = strtolower($fileType);
                    $fileName = $utility_class_call->clean_user_data(preg_replace("#[^a-z0-9.]#i", "", $fileName), 1);
                    $new_img_name = $utility_class_call->generateRandomFileName($name) . "." . $fileType;
                    $img_upload_path =  $imagelocation . $new_img_name;

                    if ($utility_class_call->isImageTooLarge($fileData, 1)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileSizeTooLarge);
                    }else if (!$utility_class_call->isImageUploadedValid($fileData, 1)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileinvalid);
                    } else if (!in_array($fileType_lc, $allowed)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileTypeNotAllowed);
                    }
                }
                $imagesarevalid=1;
            }
            if($imagesarevalid==1){
                $imagetoSave =$utility_class_call->uploadImage($images, $imagelocation, $tmpimagelocation, $imagelocationName, $name,true);
            }
            $trackid = $db_call_class->createUniqueRandomStringForATableCol(5,"bookings", "trackid", "", true, true, false);
            $insertData= $db_call_class->insertRow("bookings", ["title"=>$job_title,"longitude"=>$location_long,"latitude"=> $location_lat,"user_tid" => $user_tid,'service_tid' => $service,'provider_tid' => '','tax_paid' => $taxpaid,'address' => $address,'description' => $description,'status' => 0,'commission_percent'=>$commision,'trackid' => $trackid,'used_coupon' => $coupon_code,'profit' => $profitMade,'amt_paid' => $amouttopay, 'provider_fee' => $price,'paid_with' =>0,'imageslink' => $imagetoSave,"the_time"=>$time,"the_date"=>$date]);
            if($insertData > 0){
                $text="Payment URL";//API_User_Response::$data_created;
                $maindata=[];
                $api_status_code_class_call->respondOK($maindata, $text);
            }else{
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
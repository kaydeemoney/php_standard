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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken:1,whocalled:2);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached= $db_call_class->selectRows("admin", "id, email", [[
            ['column' => 'adminpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);
    
        $name = isset($_POST['name']) ? $utility_class_call->clean_user_data($_POST['name']) : '';
        $duration= isset($_POST['duration']) ? $utility_class_call->clean_user_data($_POST['duration']) : '';
        $commission = isset($_POST['commission']) ? $utility_class_call->clean_user_data($_POST['commission']) : '';
        $category_tid = isset($_POST['category_tid']) ? $utility_class_call->clean_user_data($_POST['category_tid']) : '';
        $status = isset($_POST['status']) ? $utility_class_call->clean_user_data($_POST['status']) : '';
        $featured = isset($_POST['featured']) ? $utility_class_call->clean_user_data($_POST['featured']) : '';
        $description = isset($_POST['description']) ? $utility_class_call->clean_user_data($_POST['description']) : '';
        $images = isset($_FILES['images']) ? $_FILES['images'] : '';
        $gallery = isset($_FILES['gallery']) ? $_FILES['gallery'] : '';

        $total_numRow = $db_call_class->checkIfRowExistAndCount("services", [[
            ['column' => 'name', 'operator' => '=', 'value' =>$name]]
        ]);

        $total_numRow2 = $db_call_class->checkIfRowExistAndCount("categories", [[
            ['column' => 'trackid', 'operator' => '=', 'value' => $category_tid]]
        ]);

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else if ( $utility_class_call->input_is_invalid($name) || $utility_class_call->input_is_invalid($duration) || $utility_class_call->input_is_invalid($commission) ||   $utility_class_call->input_is_invalid($category_tid) || $utility_class_call->input_is_invalid($description)|| $utility_class_call->input_is_invalid($images)|| $utility_class_call->input_is_invalid( $gallery)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }else if($total_numRow>0){
            $api_status_code_class_call->respondBadRequest(API_User_Response::$already_created_record);
        }else if($total_numRow2==0){
            $api_status_code_class_call->respondBadRequest(API_User_Response::dataInvalid("Category"));
        } else if (count($images['name']) > 1) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$onFileAtATime);
        } else{
            $allowed =  array('jpg', 'jpeg', 'svg', 'png', 'gif', 'webp', 'jiff');
            $bannerimaglinks = '';
            $galleryimaglinks = '';

            $bannerimagelocation = "$folderPath/assets/images/services_banner/";
            $bannerimagelocationName = "/assets/images/services_banner/";
            $tmpimagelocation = "$folderPath/assets/images/tmp/";
            $galleryimagelocation = "$folderPath/assets/images/services_gallery/";
            $galleryimagelocationName = "/assets/images/services_gallery/";

            //CHECK IF ALL THE IMAGES ARE VALID
            $imagesarevalid = 0;
            $imagesarevalid1 = 0;
            if (!empty($images['name'])) {
                // multiple file upload
                foreach ($images['name'] as $key => $val) {
                    // File upload path
                    $fileData = $images;
                    $fileName = $fileData['name'][$key];
                    $targetFilePath = $bannerimagelocation . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                    $fileType_lc = strtolower($fileType);
                    $fileName = $utility_class_call->clean_user_data(preg_replace("#[^a-z0-9.]#i", "", $fileName), 1);
                    $new_img_name = $utility_class_call->generateRandomFileName($name) . "." . $fileType;
                    $img_upload_path =  $bannerimagelocation . $new_img_name;
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

            if (!empty($gallery['name'])) {
                // multiple file upload
                foreach ($gallery['name'] as $key => $val) {
                    // File upload path
                    $fileData = $gallery;
                    $fileName = $fileData['name'][$key];
                    $targetFilePath = $galleryimagelocation . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                    $fileType_lc = strtolower($fileType);
                    $fileName = $utility_class_call->clean_user_data(preg_replace("#[^a-z0-9.]#i", "", $fileName), 1);
                    $new_img_name = $utility_class_call->generateRandomFileName($name) . "." . $fileType;
                    $img_upload_path =  $galleryimagelocation . $new_img_name;
                    if ($utility_class_call->isImageTooLarge($fileData, 1)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileSizeTooLarge);
                    }else if (!$utility_class_call->isImageUploadedValid($fileData, 1)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileinvalid);
                    } else if (!in_array($fileType_lc, $allowed)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileTypeNotAllowed);
                    }
                }

                $imagesarevalid1 = 1;
            }
            if($imagesarevalid == 1 && $imagesarevalid1 == 1){
                $bannerimaglinks = $utility_class_call->uploadImage($images, $bannerimagelocation, $tmpimagelocation, $bannerimagelocationName, $name);
                $galleryimaglinks = $utility_class_call->uploadImage($gallery, $galleryimagelocation, $tmpimagelocation, $galleryimagelocationName, $name);

                $trackid = $db_call_class->createUniqueRandomStringForATableCol(5, "coupons", "trackid", "", true, true, false);       
                $insertData = $db_call_class->insertRow("services", ["cat_tid" => $category_tid, 'trackid' => $trackid, 'name' => $name, 'commission' => $commission, 'min_duration' => $duration, 'banner_image' => $bannerimaglinks, 'status' => $status, 'feature_front_page' => $featured, 'description' => $description, 'gallery' => $galleryimaglinks]);
                
                if($insertData>0){
                    $text=API_User_Response::$data_created;
                    $maindata=[];
                    $api_status_code_class_call->respondOK($maindata,$text);
                }else{
                    $api_status_code_class_call->respondInternalError(API_User_Response::$error_creating_record,API_User_Response::$error_creating_record);
                }
            }   
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
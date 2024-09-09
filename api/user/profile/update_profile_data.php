<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached = $db_call_class->selectRows("users", "id, email", [[
            ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey],]
        ]);

        // $name = isset($_POST['title']) ? $utility_class_call->clean_user_data($_POST['title']) : '';
        $imagesData = isset($_FILES['image']) ? $_FILES['image'] : '';

        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        } else if ($utility_class_call->input_is_invalid($imagesData)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        } else {
            $allowed =  array('jpg', 'jpeg', 'svg', 'png', 'gif', 'webp', 'jiff');
            $imagelocation = "$folderPath/assets/images/userpassport/";
            $imagelocationName = "/assets/images/userpassport/";
            $tmpimagelocation = "$folderPath/assets/images/imgholder/";

            //CHECK IF ALL THE IMAGES ARE VALID
            $imagesarevalid=0;
            $images=[];
            array_push($images,$imagesData);
                // multiple file upload
                foreach ($images as $key => $val) {
                    // File upload path
                    $fileData =$images[$key];
                    $fileName = $fileData['name'];
                    $targetFilePath = $imagelocation . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                    $fileType_lc = strtolower($fileType);
                    $fileName = $utility_class_call->clean_user_data(preg_replace("#[^a-z0-9.]#i", "", $fileName), 1);
                    if ($utility_class_call->isImageTooLarge($fileData)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileSizeTooLarge);
                    }else if (!$utility_class_call->isImageUploadedValid($fileData)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileinvalid);
                    } else if (!in_array($fileType_lc, $allowed)) {
                        $api_status_code_class_call->respondBadRequest(API_User_Response::$fileTypeNotAllowed);
                    }
                    $imagesarevalid=1;

                }

            if($imagesarevalid==1){
                foreach($images as $key => $val){
                    $fileData =$images[$key];
                    $imagetoSaveIs =$utility_class_call->uploadImage($fileData, $imagelocation, $tmpimagelocation, $imagelocationName, 'TaskColo');
                    $imagetoSave =$imagetoSaveIs['imagepath'];
                }
                $updateddate= $db_call_class->updateRows("users",['profile_pic'=>$imagetoSave],
                [  
                    [
                        ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey]
                    ]
                ] );
                if ($updateddate) {
                    $text = API_User_Response::$data_updated;
                    $maindata = [];
                    $api_status_code_class_call->respondOK($maindata, $text);
                } else {
                    $api_status_code_class_call->respondInternalError(API_User_Response::$error_updating_record, API_User_Response::$error_updating_record);
                }
             }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

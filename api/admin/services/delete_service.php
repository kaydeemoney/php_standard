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

        $data_tid = isset($_POST['data_tid']) ? $utility_class_call->clean_user_data($_POST['data_tid']) : '';
        $getSliderData = $db_call_class->selectRows("services", "banner_image,gallery", [[
            ['column' => 'trackid', 'operator' => '=', 'value' => $data_tid]]
        ]);

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else if ($utility_class_call->input_is_invalid($data_tid)) { 
            $api_status_code_class_call->respondBadRequest(API_User_Response::$request_body_invalid);
        }else if ($utility_class_call->input_is_invalid($getSliderData)) {
            $api_status_code_class_call->respondBadRequest(API_User_Response::$data_deleted);
        }else{   

            $galleryimages=$getSliderData[0]['gallery'];
            $bannerimages=$getSliderData[0]['banner_image'];
            $getallimages=explode(",",$galleryimages);

            for($i=0; $i<count($getallimages); $i++){
                if(!empty($getallimages[$i])){
                    $assetspath='/assets/images/services_gallery/';
                    // /assets/images/sliders/taskcolony665bf151eea224.67851064loveddd.webp,
                    $filename=str_replace($assetspath,'',$getallimages[$i]);
                    $fullpath="$folderPath$assetspath";
                    //Delete from temp directory after compressing
                    $utility_class_call->deleteAFileinFolder($filename, $fullpath);
                }
            }

            $getallimages=explode(",", $bannerimages);

            for($i=0; $i<count($getallimages); $i++){
                if(!empty($getallimages[$i])){
                    $assetspath='/assets/images/services_banner/';
                    // /assets/images/sliders/taskcolony665bf151eea224.67851064loveddd.webp,
                    $filename=str_replace($assetspath,'',$getallimages[$i]);
                    $fullpath="$folderPath$assetspath";
                    //Delete from temp directory after compressing
                    $utility_class_call->deleteAFileinFolder($filename, $fullpath);
                }
            }

            $insertData= $db_call_class->deleteRows("services", [[
                ['column' => 'trackid','operator' => '=', 'value' => $data_tid]]
            ]);
            
            if($insertData>0){
                $text=API_User_Response::$data_deleted;
                $maindata=[];
                $api_status_code_class_call->respondOK($maindata,$text);
            }else{
                $api_status_code_class_call->respondInternalError(API_User_Response::$error_deleting_record,API_User_Response::$error_deleting_record);
            }
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
<?php
require_once '../../../bootstrap_file.php';

use Config\Constants;
use Config\API_User_Response;
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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken:1,whocalled:2);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached= $db_call_class->selectRows("admin", "id, email", [[
            ['column' => 'adminpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);

        $page_no = isset($_GET['page_no']) ? $utility_class_call->clean_user_data($_GET['page_no']) : 1;
        $noperpage = isset($_GET['no_perpage']) ? $utility_class_call->clean_user_data($_GET['no_perpage']) : 15;
        $search = isset($_GET['search']) ? $utility_class_call->clean_user_data($_GET['search']) :'';

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else{
            // is search active
            $whereclause=[];

            if(!$utility_class_call->input_is_invalid(($search))){
                $search="%$search%";
                $whereclause = [[
                ['column' =>'name', 'operator' =>'LIKE', 'value' =>$search],
                ['column' =>'trackid', 'operator' =>'LIKE', 'value' =>$search],
                ['column' =>'commission', 'operator' =>'LIKE', 'value' =>$search],
                'operator'=>'OR']];
            }
            
            //count how many rows
            $total_numRow =$db_call_class->checkIfRowExistAndCount("countries_cities",$whereclause);
            // get the dataf
            $getAllData= $db_call_class->selectRows("countries_cities", "trackid, name, commission, status", $whereclause,['limit' => $noperpage, 'orderBy' => 'id', 'orderDirection' => 'DESC', 'pageno' => $page_no]);

            if($utility_class_call->input_is_invalid($getAllData)){
                $text=API_User_Response::$data_not_found;
            }else{
                $text=API_User_Response::$data_found;
            }
            
            $total_pages = ceil($total_numRow / $noperpage);
            $maindata['data']=$getAllData;
            $maindata['pageno']=$page_no;
            $maindata['perpage']=$noperpage;
            $maindata['totalpage']=$total_pages;
            $api_status_code_class_call->respondOK($maindata,$text);
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
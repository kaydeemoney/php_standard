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

        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken:1, whocalled:1);
        $user_pubkey = $decodedToken->usertoken;
        [ $getuserattached ] = $db_call_class->selectRows("users", "id, email, trackid", [[
            ['column' =>'userpubkey', 'operator' =>'=', 'value' => $user_pubkey]]
        ]);

        $page_no = isset($_GET['page_no']) ? $utility_class_call->clean_user_data($_GET['page_no']) : 1;
        $noperpage = isset($_GET['no_perpage']) ? $utility_class_call->clean_user_data($_GET['no_perpage']) : 15;
        $search = isset($_GET['search']) ? $utility_class_call->clean_user_data($_GET['search']) : "";

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else{
            // is search active
            $whereclause = [[ 
            ['column' => 'reviews.user_tid', 'operator' => '=', 'value' => $getuserattached['trackid'] ],
            ['column' => 'reviews.status', 'operator' => '=', 'value' => 1 ],
            'operator' => "AND" ] ];

            if( !$utility_class_call->input_is_invalid($search) ){
                $search = "%$search%";
                array_push($whereclause, [
                ['column' => 'reviews.review', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'p.fname', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'p.lname', 'operator' => 'LIKE', 'value' => $search],
                'operator' => 'OR']);
            }

            $options = [[
              'table' => 'users u',
              'type' => 'LEFT',
              'condition' => 'reviews.provider_tid = u.trackid'
            ], [
              'table' => 'users p',
              'type' => 'LEFT',
              'condition' => 'reviews.user_tid = p.trackid'
            ]];

            //count how many rows
            $total_numRow = count($db_call_class->selectRows("reviews", "reviews.id", $whereclause, ['joins' => [...$options]]));

            // get the dataf
            $getAllData = $db_call_class->selectRows(
              "reviews",
              "reviews.id, reviews.review, reviews.trackid, reviews.rating, reviews.status, reviews.provider_tid, p.fname AS pfname, p.lname AS plname, u.fname AS ufname, u.lname AS ulname",
              $whereclause,
              ['limit' => $noperpage, 'orderBy' => 'reviews.id', 'orderDirection' => 'ASC', 'pageno' => $page_no, 'joins' => [...$options]]
            );

            if ($utility_class_call->input_is_invalid($getAllData)) {
                $text = API_User_Response::$data_not_found;
                $maindata['data'] = [];
                $maindata['pageno'] = $page_no;
                $maindata['perpage'] = $noperpage;
                $maindata['totalpage'] = 0;
            } else {
                foreach ($getAllData as $item) {
                  if (isset($item['status'])) {
                    $item['statustext'] = $item['status'] == 1
                    ? "Active" : "In Active";
                  }
                }

                $text = API_User_Response::$data_found;
                $total_pages = ceil($total_numRow / $noperpage);
                $maindata['data'] = $getAllData;
                $maindata['pageno'] = $page_no;
                $maindata['perpage'] = $noperpage;
                $maindata['totalpage'] = $total_pages;
            }
            
            $api_status_code_class_call->respondOK($maindata, $text);
        } 
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
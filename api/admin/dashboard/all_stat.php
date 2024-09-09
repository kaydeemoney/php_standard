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
header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
// header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
// $utility_class_call->setTimeZoneForUser('');
// header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call = new Config\API_Status_Code;
$db_call_class = new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try {
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1, whocalled: 2);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached = $db_call_class->selectRows( "admin", "id, email", [[
            ['column' => 'adminpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);
         
        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        } else {
            $text = API_User_Response::$data_found;
            $maindata['active_users'] = $db_call_class->checkIfRowExistAndCount("users", [[
            ['column' => 'status', 'operator' => '=', 'value' =>1],
            ['column' => 'am_a_provider', 'operator' => '=', 'value' => 0]]], []);

            $maindata['inactive_users'] = $db_call_class->checkIfRowExistAndCount("users",[[
            ['column' => 'status', 'operator' => '=', 'value' => 0],
            ['column' => 'am_a_provider', 'operator' => '=', 'value' => 0]]], []);

            $maindata['verified_providers'] =  $db_call_class->checkIfRowExistAndCount("users",[[
            ['column' => 'account_verified', 'operator' => '=', 'value' => 1],
            ['column' => 'am_a_provider', 'operator' => '=', 'value' => 1]]], []);

            $maindata['unverified_providers'] =  $db_call_class->checkIfRowExistAndCount("users",[[
            ['column' => 'account_verified', 'operator' => '=', 'value' => 0],
            ['column' => 'am_a_provider', 'operator' => '=', 'value' => 1]]], []);

            $maindata['active_admin'] =$db_call_class->checkIfRowExistAndCount("admin",[[
            ['column' => 'status', 'operator' => '=', 'value' => 1]]], []);

            $maindata['inactive_admin'] =$db_call_class->checkIfRowExistAndCount("admin",[[
            ['column' => 'status', 'operator' => '=', 'value' => 0],]], []);

            $maindata['total_catgeories'] = $db_call_class->checkIfRowExistAndCount("categories",[], []);
            $maindata['total_services'] = $db_call_class->checkIfRowExistAndCount("services",[], []);
            $maindata['total_paid'] = 0;
            $maindata['total_profit'] = 0;

            $totalpaidis=$db_call_class->selectRows("bookings", "SUM(amt_paid) AS total,SUM(profit) as total_profit", [[
            ['column' => 'status', 'operator' => '=', 'value' => 1]]], []);

            if ($utility_class_call->input_is_invalid($totalpaidis)){
                $maindata['total_paid'] = $totalpaidis[0]['total'];
                $maindata['total_profit'] = $totalpaidis[0]['total_profit'];
            }

            $maindata['completed_bookings'] =$db_call_class->checkIfRowExistAndCount("bookings",[[
            ['column' => 'status', 'operator' => '=', 'value' =>1],]], []);

            $maindata['ongoing_bookings'] =$db_call_class->checkIfRowExistAndCount("bookings",[[
            ['column' => 'status', 'operator' => '=', 'value' =>3],]], []);

            $maindata['pending_bookings'] =$db_call_class->checkIfRowExistAndCount("bookings",[[
            ['column' => 'status', 'operator' => '=', 'value' =>0],]], []);

            $maindata['total_gigs'] =$db_call_class->checkIfRowExistAndCount("bookings",[], []);//total bookings

            $maindata['unassigned_gigs'] =$db_call_class->checkIfRowExistAndCount("bookings",[[
            ['column' => 'provider_tid', 'operator' => '=', 'value' =>''],]], []);//total bookings

            $maindata['assigned_gigs'] =$db_call_class->checkIfRowExistAndCount("bookings",[[
            ['column' => 'provider_tid', 'operator' => '!=', 'value' =>''],]], []);//total bookings

            $maindata['recent_users'] = $db_call_class->selectRows("users", "users.id,users.trackid,users.fname,users.lname,users.username,users.email,COALESCE(userwallet.walletbal,'0.00') AS walletbal", [[
            ['column' => 'am_a_provider', 'operator' => '=', 'value' =>0]]], [ 'joins' => [[
            'type' => 'LEFT',
            'table' => 'userwallet',
            'condition' => 'users.id = userwallet.userid AND userwallet.currencytag="USD256"']],
            'limit' => 4, 'orderBy' => 'users.id', 'orderDirection' => 'DESC', 'pageno' =>1]);

            $maindata['recent_providers'] = $db_call_class->selectRows("users", "users.id, users.trackid, users.account_verified, users.fname, users.service_tid, users.lname, users.username, users.email, COALESCE(userwallet.walletbal,'0.00') AS walletbal, COALESCE(services.name,'') as service_name", [[
            ['column' => 'am_a_provider', 'operator' => '=', 'value' =>1]]], [ 'joins' => [[
            'type' => 'LEFT',
            'table' => 'userwallet',
            'condition' => 'users.id = userwallet.userid AND userwallet.currencytag="USD256"' ], [
            'type' => 'LEFT',
            'table' => 'services',
            'condition' => 'users.service_tid = services.trackid']],
            'limit' => 4, 'orderBy' => 'users.id', 'orderDirection' => 'DESC', 'pageno' => 1]);

            $getAllData=$db_call_class->selectRows("bookings", "bookings.trackid, bookings.amt_paid, bookings.status, bookings.provider_tid, bookings.service_tid, services.name as service_name, up.lname as plname, up.fname as pfname, uu.fname as ufname, uu.lname as ulname", [], [ 'joins' => [[
            'type' => 'LEFT',
            'table' => 'services',
            'condition' => 'bookings.service_tid = services.trackid'], [
            'type' => 'LEFT',
            'table' => 'users up',
            'condition' => 'bookings.provider_tid = up.trackid' ], [
            'type' => 'LEFT',
            'table' => 'users uu',
            'condition' => 'bookings.user_tid = uu.trackid' ]],
            'limit' =>4, 'orderBy' => 'bookings.id', 'orderDirection' => 'DESC', 'pageno' =>1], "AND");

            foreach ($getAllData as &$item) {
                $item['status_text'] = '';
            
                if (isset($item['status'])) {
                    $mystatus=$item['status'];
                    // 0 Pending 2 Accepted 3- Ongoing 4 hold 5 Done 6 Rejected 1 completed 7 cancle
                    if($mystatus==0){
                        $item['status_text']='Pending';
                    }else if($mystatus==1){
                        $item['status_text']='Completed';
                    }else if($mystatus==2){
                        $item['status_text']='Accepted';
                    }else if($mystatus==3){
                        $item['status_text']='Ongoing';
                    }else if($mystatus==4){
                        $item['status_text']='Hold';
                    }else if($mystatus==5){
                        $item['status_text']='Done';
                    }else if($mystatus==6){
                        $item['status_text']='Rejected';
                    }else if($mystatus==7){
                        $item['status_text']='Cancle';
                    }
                }
            }

        $maindata['recent_bookings'] =$getAllData;
        $api_status_code_class_call->respondOK($maindata, $text);
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

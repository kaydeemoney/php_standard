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
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1 ,whocalled: 2);
        $user_pubkey = $decodedToken->usertoken;
        $todaysum = $lastmonthsum = $currentmonthsum = $alltimesum = 0;
        $getuserattached= $db_call_class->selectRows("admin", "id, email", [[
            ['column' => 'adminpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);

        $page_no = isset($_GET['page_no']) ? $utility_class_call->clean_user_data($_GET['page_no']) : 1;
        $noperpage = isset($_GET['no_perpage']) ? $utility_class_call->clean_user_data($_GET['no_perpage']) : 15;
        $search = isset($_GET['search']) ? $utility_class_call->clean_user_data($_GET['search']) :'';

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else{
            // stat calculation
            $getAllData= $db_call_class->selectRows("bookings", "SUM(profit) as total_profit",
            [[['column' => 'DATE(created_at)','operator' => '=','value' => 'CURDATE()'], ['column' => 'status', 'operator' => '=', 'value' => '1']] ]);

            if(!$utility_class_call->input_is_invalid($getAllData)){
                $todaysum= $getAllData[0]['total_profit'] ?? "0"; 
            }

            $getAllData= $db_call_class->selectRows("bookings", "SUM(profit) as total_profit", [ [
                ['column' =>'created_at','operator' =>'>=','value' =>'DATE_SUB(DATE_SUB(LAST_DAY(NOW()), INTERVAL 1 MONTH), INTERVAL DAY(LAST_DAY(NOW())) - 1 DAY)'],
                ['column' =>'created_at','operator' =>'<','value' =>'DATE_SUB(DATE(NOW()), INTERVAL DAYOFMONTH(NOW()) - 1 DAY)'],
                ['column' =>'status','operator' =>'=','value' =>'1'],
                'operator'=>'AND' ],
            ]);

            if(!$utility_class_call->input_is_invalid($getAllData)){
                $lastmonthsum= $getAllData[0]['total_profit']??"0"; 
            }

            // MONTH(created_at) = MONTH(CURDATE()) 
            // AND YEAR(created_at) = YEAR(CURDATE())
            $getAllData= $db_call_class->selectRows("bookings", "SUM(profit) as total_profit", [[
                ['column' =>'MONTH(created_at)','operator' =>'=','value' =>'MONTH(CURDATE())'],
                ['column' =>'YEAR(created_at)','operator' =>'=','value' =>'YEAR(CURDATE())'],
                ['column' =>'status','operator' =>'=','value' =>'1'],
                'operator'=>'AND']
            ]);

            if(!$utility_class_call->input_is_invalid($getAllData)){
                $currentmonthsum= $getAllData[0]['total_profit']??"0"; 
            }

            $getAllData= $db_call_class->selectRows("bookings", "SUM(profit) as total_profit", [[
                ['column' => 'status', 'operator' => '=', 'value' => '1']]
            ]);

            if(!$utility_class_call->input_is_invalid($getAllData)){
                $alltimesum= $getAllData[0]['total_profit'] ?? "0"; 
            }

            $whereclause=[[
                ['column' => 'status','operator' => '=','value' => '1']]
            ];

            if(!$utility_class_call->input_is_invalid(($search))){
                $search="%$search%";
                $whereclause = [[ 
                ['column' =>'description', 'operator' =>'LIKE', 'value' =>$search],
                ['column' =>'address', 'operator' =>'LIKE', 'value' =>$search],
                ['column' =>'created_at','operator' =>'LIKE','value' =>$search],
                'operator'=>'OR'], [ 
                ['column' =>'status','operator' =>'=','value' =>'1']]];
            }

            //count how many rows
            $total_numRow =$db_call_class->checkIfRowExistAndCount("bookings", $whereclause);
            // get the dataf
            $getAllData= $db_call_class->selectRows("bookings", " DATE(created_at) as date, SUM(profit) as total_profit, SUM(tax_paid) as total_tax_paid,COUNT(*) as total_services ", $whereclause,['limit'=>$noperpage,'orderBy' => 'DATE(created_at)', 'orderDirection' => 'ASC', 'pageno' => $page_no, 'groupBy' => 'DATE(created_at)']);
            if ($utility_class_call->input_is_invalid($getAllData)) {
                $text = API_User_Response::$data_not_found;
                $maindata['data'] = [];
                $maindata['pageno'] = $page_no;
                $maindata['perpage'] = $noperpage;
                $maindata['totalpage'] = 0;
            } else {
                $text = API_User_Response::$data_found;
                $total_pages = ceil($total_numRow / $noperpage);
                $maindata['data'] = $getAllData;
                $maindata['pageno'] = $page_no;
                $maindata['perpage'] = $noperpage;
                $maindata['totalpage'] = $total_pages;
            }

            $maindata['today_sum']=$todaysum;
            $maindata['lastmonth_sum']=$lastmonthsum;
            $maindata['currentmonth_sum']=$currentmonthsum;
            $maindata['all_time_sum']=$alltimesum;
            $api_status_code_class_call->respondOK($maindata,$text);
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

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
          ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey]]
        ]);

        $page_no = isset($_GET['page_no']) ? $utility_class_call->clean_user_data($_GET['page_no']) : 1;
        $noperpage = isset($_GET['no_perpage']) ? $utility_class_call->clean_user_data($_GET['no_perpage']) : 15;
        $search = isset($_GET['search']) ? $utility_class_call->clean_user_data($_GET['search']) : "";
        $sort = isset($_GET['sort']) ? $utility_class_call->clean_user_data($_GET['sort']) : "";

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else{
          // is search active
          $whereclause = [[
            ['column' => 'bookings.user_tid', 'operator' => '=', 'value' => $getuserattached['trackid'] ]]
          ];
          if(!$utility_class_call->input_is_invalid($search)){
            $search = "%$search%";
            array_push($whereclause, [
            ['column' => 's.name', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'u.fname', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'u.lname', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.address', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.description', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.amt_paid', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.provider_fee', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.used_coupon', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.paid_with', 'operator' => 'LIKE', 'value' => $search ],
            ['column' => 'bookings.commission_percent', 'operator' => 'LIKE', 'value' => $search ],
            'operator' => 'OR']);
          }
          if(!$utility_class_call->input_is_invalid($sort)){
            array_push($whereclause, [
            ['column' => 'bookings.status', 'operator' => '=', 'value' =>$sort ],]);
          }

          $options = [[
            'table' => 'services s',
            'type' => 'LEFT',
            'condition' => 'bookings.service_tid = s.trackid'], [
            'table' => 'users u',
            'type' => 'LEFT',
            'condition' => 'bookings.user_tid = u.trackid'
          ]];

          //count how many rows
          $total_numRow = count($db_call_class->selectRows("bookings", "bookings.id", $whereclause, ['joins' => [...$options]]));

          // get the dataf
          $getAllData = $db_call_class->selectRows(
            "bookings",
            "bookings.id, bookings.address, bookings.description, bookings.imageslink, bookings.amt_paid,  bookings.used_coupon, bookings.paid_with, bookings.the_date,bookings.the_time,bookings.title,bookings.longitude, bookings.latitude,bookings.status, bookings.trackid, s.name AS service_name, u.fname AS ufname, u.lname AS ulname",
            $whereclause, ['limit' => $noperpage, 'orderBy' => 'bookings.id', 'orderDirection' => 'ASC', 'pageno' => $page_no, 'joins' => [...$options]
          ]);

          if ($utility_class_call->input_is_invalid($getAllData)) {
            $text = API_User_Response::$data_not_found;
            $maindata['data'] = [];
          } else {
            $status = ["Pending", "Completed", "Accepted", "Ongoing", "Hold", "Done", "Rejected", "Cancled"];
            $base_url=Constants::BASE_URL;
              
            foreach ($getAllData as &$itm) {
              if (isset($itm['paid_with'])) {
                if($itm['paid_with']==0){
                  $itm['paid_with'] ='Not paid';
                }else{
                  $itm['paid_with'] = $itm['paid_with'] == 2 ? "Card" : "Wallet";
                }
              }

              if (isset($itm['status'])) {
                $itm['status'] = $status[$itm['status']];
              }

              if (isset($itm['imageslink'])) {
                // Split the imagelinks string by comma
                $image_urls = explode(',', $itm['imageslink']);

               // Remove any empty elements in case the split left an empty string
                $filtered_array = array_filter($image_urls, function($value) {
                    return !empty($value);
                });

                // Trim whitespace and add base URL
                $full_urls = array_map(function($url) use ($base_url) {
                    return $base_url . trim($url);
                }, $filtered_array);

                $full_urls = array_filter($full_urls, function($url) {
                    return !empty($url);
                });

                // Update the imagelinks field
                $itm['imageslink'] = $full_urls;
              }
            }

            $text = API_User_Response::$data_found;
            $maindata['data'] = $getAllData;
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
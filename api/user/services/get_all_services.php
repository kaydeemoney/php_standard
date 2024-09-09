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
        $getuserattached= $db_call_class->selectRows("users", "id, email", [[
            ['column' =>'userpubkey', 'operator' =>'=', 'value' => $user_pubkey]]
        ]);

        $page_no = isset($_GET['page_no']) ? $utility_class_call->clean_user_data($_GET['page_no']) : 1;
        $noperpage = isset($_GET['no_perpage']) ? $utility_class_call->clean_user_data($_GET['no_perpage']) : 605;
        $search = isset($_GET['search']) ? $utility_class_call->clean_user_data($_GET['search']) : "";
        $sort = isset($_GET['sort']) ? $utility_class_call->clean_user_data($_GET['sort']) : "";
        $featured = isset($_GET['featured']) ? $utility_class_call->clean_user_data($_GET['featured']) : "";

        if ($utility_class_call->input_is_invalid($getuserattached)){
            $api_status_code_class_call->respondUnauthorized();
        }else{
            $base_url=Constants::BASE_URL;

            // is search active
            $whereclause = [ ['column' => 'services.status', 'operator' => '=', 'value' => 1] ];

            if( !$utility_class_call->input_is_invalid($sort) && $sort != "" ){
                array_push($whereclause, [[
                    'column' => 'services.cat_tid', 'operator' => '=', 'value' => $sort]
                ]);
            }
            if( !$utility_class_call->input_is_invalid($featured) && $featured != "" ){
                array_push($whereclause, [[
                    'column' => 'services.feature_front_page', 'operator' => '=', 'value' => $featured]
                ]);
            }
            
            if(!$utility_class_call->input_is_invalid($search)){
                $search="%$search%";
                array_push($whereclause, [
                ['column' => 'services.name', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'services.commission', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'services.min_duration', 'operator' => 'LIKE', 'value' => $search],
                'operator'=>'OR' ]);
            }
            

            $options =[[
                'table' => 'categories c',
                'type' => 'LEFT',
                'condition' => 'c.trackid = services.cat_tid'
            ]];

            //count how many rows
            $total_numRow = count($db_call_class->selectRows("services", "services.id", $whereclause));

            // get the dataf
            $getAllData = $db_call_class->selectRows(
              "services", 
              "services.id, services.name, services.commission, services.min_duration,services.description, services.trackid, services.cat_tid,services.banner_image,services.gallery, c.name as category_name",
              $whereclause,
              ['limit' => $noperpage, 'orderBy' => 'id', 'orderDirection' => 'ASC', 'pageno' => $page_no, 'joins' => [...$options]]
            );

            if ($utility_class_call->input_is_invalid($getAllData)) {
                $text = API_User_Response::$data_not_found;
                $maindata['data'] = [];
                $maindata['pageno'] = $page_no;
                $maindata['perpage'] = $noperpage;
                $maindata['totalpage'] = 0;
            } else {

                foreach ($getAllData as &$itm) {
                    if( isset($itm['trackid']) ){
                        $itm['reviews'] = $db_call_class->selectRows( "reviews", "reviews.id, u.fname AS ufname, u.lname AS ulname, p.fname AS pfname, p.lname AS plname, reviews.review, reviews.rating", [[ ['column' => 'reviews.service_tid', 'operator' => '=', 'value' => $itm['trackid']] ]],
                            ['joins' => [[
                                'table' => 'users u',
                                'type' => 'INNER',
                                'condition' => 'u.trackid = reviews.user_tid' ], [
                                'table' => 'users p',
                                'type' => 'LEFT',
                                'condition' => 'p.trackid = reviews.provider_tid'
                            ]]]
                        );
                        $totalratings= $db_call_class->selectRows( "reviews", "CAST(SUM(rating) AS FLOAT) / COUNT(rating) AS overall_rating", [[ ['column' => 'reviews.service_tid', 'operator' => '=', 'value' => $itm['trackid']] ]]);
                        $itm['overall_rating']=0;
                        if (!$utility_class_call->input_is_invalid($totalratings)) {
                            $itm['overall_rating']=$totalratings[0]['overall_rating']??0;
                        }
                    }
                    if (isset($itm['gallery'])) {
                        // Split the imagelinks string by comma
                        $image_urls = explode(',', $itm['gallery']);

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
                        $itm['gallery'] = $full_urls;
                    }
                    if (isset($itm['banner_image'])) {
                        // Split the imagelinks string by comma
                        $image_urls = explode(',', $itm['banner_image']);

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
                        $itm['banner_image'] = $full_urls;
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
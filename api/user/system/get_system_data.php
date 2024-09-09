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
// header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
$utility_class_call->setTimeZoneForUser('');
header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call = new Config\API_Status_Code;
$db_call_class = new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try {

        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1,);
        $user_pubkey = $decodedToken->usertoken;
        $limittext = isset($_GET['limit_it']) ? $utility_class_call->clean_user_data($_GET['limit_it']) : '';
        $showvoucher = false;

        $getuserattached = $db_call_class->selectRows(

            "users",
            "users.id",
            [
                [
                    ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey],
                ]
            ],
        );
        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        } else {
            $userdata= $getuserattached[0];
            $user_id=$userdata['id'];
                $base_url = Constants::BASE_URL;
                $catgeories=[];
                $selectData =$db_call_class->selectRows("categories","trackid,icon,name",
                [   
                    [
                        ['column' =>'status', 'operator' =>'=', 'value' =>1],
                    ]
                ]);
                if (!$utility_class_call->input_is_invalid($selectData)) {
                    $catgeories=$selectData;
                }

                $dashboardtopslider=[];
                $selectData =$db_call_class->selectRows("sliders","title,description,imagelinks,urltogo",
                [   
                    [
                        ['column' =>'location', 'operator' =>'=', 'value' =>1],
                    ]
                ]);
                if (!$utility_class_call->input_is_invalid($selectData)) {
                    foreach ($selectData as &$item) {
                        if (isset($item['imagelinks'])) {
                            // Split the imagelinks string by comma
                            $image_urls = explode(',', $item['imagelinks']);
    
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
                            $item['imagelinks'] = $full_urls;
                        }
                    }
                    $dashboardtopslider=$selectData;
                }
                $dashboardMiddleslider=[];
                $selectData =$db_call_class->selectRows("sliders","title,description,imagelinks,urltogo",
                [   
                    [
                        ['column' =>'location', 'operator' =>'=', 'value' =>2],
                    ]
                ]);
                if (!$utility_class_call->input_is_invalid($selectData)) {
                    foreach ($selectData as &$item) {
                        if (isset($item['imagelinks'])) {
                            // Split the imagelinks string by comma
                            $image_urls = explode(',', $item['imagelinks']);
    
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
                            $item['imagelinks'] = $full_urls;
                        }
                    }
                    $dashboardMiddleslider=$selectData;
                }
                $cities=[];
                $selectData =$db_call_class->selectRows("countries_cities","trackid,name,commission",
                [   
                    [
                        ['column' =>'status', 'operator' =>'=', 'value' =>2],
                    ]
                ]);
                if (!$utility_class_call->input_is_invalid($selectData)) {
                   
                    $cities=$selectData;
                }

                $coupons=[];
                $selectData =$db_call_class->selectRows("coupons","name,trackid,coupoun_code,type,coupon_value",
                [   
                    [
                        ['column' =>'display_on_app', 'operator' =>'=', 'value' =>1],
                    ]
                ]);
                if (!$utility_class_call->input_is_invalid($selectData)) {
                   
                    $coupons=$selectData;
                }



                $maindata = [
                    "categories"=>$catgeories,
                    'dashboard_top_slider'=>$dashboardtopslider,
                    'dashboard_middle_slider'=>$dashboardMiddleslider,
                    'cities'=>$cities,
                    'coupons'=>$coupons,
                   
                    'support_no'=>'+000',
                    'support_email'=>'support@taskcolony.co',
                    'social_media'=>'@taskolony'
                ];

                
                $text = API_User_Response::$data_found;
                $api_status_code_class_call->respondOK($maindata, $text);
            
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

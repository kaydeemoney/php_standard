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
// header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
$utility_class_call->setTimeZoneForUser('');
header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call= new Config\API_Status_Code;
$db_call_class= new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try{

        $cache = new Config\CacheSystem('phpfastcache');
        // Define a cache key
        $cacheKey = 'all_countries';
        // Try to get the cached data
        $cachedUsers = $cache->getCache($cacheKey);

            if (!$cachedUsers) {
                $baseurl=Constants::BASE_URL.'/';
                $getAllData= $db_call_class->selectRows("countries", "trackid,name,phonecode,CONCAT('$baseurl', flag_url) AS flag_url,flag_url as flag_assets,code,min_pno_value,max_pno_value", 
                [   
                    [
                        ['column' =>'status', 'operator' =>'=', 'value' =>1],
                    ]
                ]
                );
                // Serialize and store the data in Redis with an expiry of 10 seconds
                $cache->setCache($cacheKey, 2678400, serialize($getAllData));
            } else {
                // Cache exists, retrieve users from the cache
                // Cache hit, unserialize the data
                $getAllData = unserialize($cachedUsers);
            }
            if($utility_class_call->input_is_invalid($getAllData)){
                $text=API_User_Response::$data_not_found;
            }else{
                $text=API_User_Response::$data_found;
            }
                $maindata=$getAllData;
                $api_status_code_class_call->respondOK($maindata,$text);
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
}else { 
    $api_status_code_class_call->respondMethodNotAlowed();
}
?>
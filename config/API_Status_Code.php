<?php

namespace Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class API_Status_Code
{
    /* Status Code 201 – This is the status code that confirms that the request was successful and, as a result, a new resource was created. 
    // Typically, this is the status code that is sent after a POST/PUT request.
    // 100	Continue	[RFC7231, Section 6.2.1]
    // 101	Switching Protocols	[RFC7231, Section 6.2.2]
    // 102	Processing	[RFC2518]
    // 103	Early Hints	[RFC8297]
    // 104-199	Unassigned	
    // 200	OK	[RFC7231, Section 6.3.1]
    // 201	Created	[RFC7231, Section 6.3.2]
    // 202	Accepted	[RFC7231, Section 6.3.3]
    // 203	Non-Authoritative Information	[RFC7231, Section 6.3.4]
    // 204	No Content	[RFC7231, Section 6.3.5]
    // 205	Reset Content	[RFC7231, Section 6.3.6]
    // 206	Partial Content	[RFC7233, Section 4.1]
    // 207	Multi-Status	[RFC4918]
    // 208	Already Reported	[RFC5842]
    // 209-225	Unassigned	
    // 226	IM Used	[RFC3229]
    // 227-299	Unassigned	
    // 300	Multiple Choices	[RFC7231, Section 6.4.1]
    // 301	Moved Permanently	[RFC7231, Section 6.4.2]
    // 302	Found	[RFC7231, Section 6.4.3]
    // 303	See Other	[RFC7231, Section 6.4.4]
    // 304	Not Modified	[RFC7232, Section 4.1]
    // 305	Use Proxy	[RFC7231, Section 6.4.5]
    // 306	(Unused)	[RFC7231, Section 6.4.6]
    // 307	Temporary Redirect	[RFC7231, Section 6.4.7]
    // 308	Permanent Redirect	[RFC7538]
    // 309-399	Unassigned	
    // 400	Bad Request	[RFC7231, Section 6.5.1]
    // 401	Unauthorized	[RFC7235, Section 3.1]
    // 402	Payment Required	[RFC7231, Section 6.5.2]
    // 403	Forbidden	[RFC7231, Section 6.5.3]
    // 404	Not Found	[RFC7231, Section 6.5.4]
    // 405	Method Not Allowed	[RFC7231, Section 6.5.5]
    // 406	Not Acceptable	[RFC7231, Section 6.5.6]
    // 407	Proxy Authentication Required	[RFC7235, Section 3.2]
    // 408	Request Timeout	[RFC7231, Section 6.5.7]
    // 409	Conflict	[RFC7231, Section 6.5.8]
    // 410	Gone	[RFC7231, Section 6.5.9]
    // 411	Length Required	[RFC7231, Section 6.5.10]
    // 412	Precondition Failed	[RFC7232, Section 4.2][RFC8144, Section 3.2]
    // 413	Payload Too Large	[RFC7231, Section 6.5.11]
    // 414	URI Too Long	[RFC7231, Section 6.5.12]
    // 415	Unsupported Media Type	[RFC7231, Section 6.5.13][RFC7694, Section 3]
    // 416	Range Not Satisfiable	[RFC7233, Section 4.4]
    // 417	Expectation Failed	[RFC7231, Section 6.5.14]
    // 418-420	Unassigned	
    // 421	Misdirected Request	[RFC7540, Section 9.1.2]
    // 422	Unprocessable Entity	[RFC4918]
    // 423	Locked	[RFC4918]
    // 424	Failed Dependency	[RFC4918]
    // 425	Too Early	[RFC8470]
    // 426	Upgrade Required	[RFC7231, Section 6.5.15]
    // 427	Unassigned	
    // 428	Precondition Required	[RFC6585]
    // 429	Too Many Requests	[RFC6585]
    // 430	Unassigned	
    // 431	Request Header Fields Too Large	[RFC6585]
    // 432-450	Unassigned	
    // 451	Unavailable For Legal Reasons	[RFC7725]
    // 452-499	Unassigned	
    // 500	Internal Server Error	[RFC7231, Section 6.6.1]
    // 501	Not Implemented	[RFC7231, Section 6.6.2]
    // 502	Bad Gateway	[RFC7231, Section 6.6.3]
    // 503	Service Unavailable	[RFC7231, Section 6.6.4]
    // 504	Gateway Timeout	[RFC7231, Section 6.6.5]
    // 505	HTTP Version Not Supported	[RFC7231, Section 6.6.6]
    // 506	Variant Also Negotiates	[RFC2295]
    // 507	Insufficient Storage	[RFC4918]
    // 508	Loop Detected	[RFC5842]
    // 509	Unassigned	
    // 510	Not Extended	[RFC2774]
    // 511	Network Authentication Required	[RFC6585]
    // 512-599	Unassigned	
    */


    //  ALL RESPONSE CODE
    // 405 Method Not Allowed
    function respondMethodNotAlowed()
    {
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();
        $errordata = [
            "code" => API_Error_Code::$internalUserBadRequestOrMethod, 
            "text" => "The request method used is not valid.",
            "link" => "https://", 
            "hint" => [
                "Ensure you use the method stated in the documentation.",
                "Check your environment variable",
                "Missing or Incorrect Headers"
            ]
        ];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$request_method_invalid, "data" => [], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 405 Method Not allowed");
        http_response_code(405);

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    function respondBadRequest($userErrMessage)
    {
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalUserBadRequestOrMethod, "text" => "The body request is not valid, missing compulsory parameter or invalid data sent.", "link" => "https://", "hint" => [
            "Ensure you use the request data stated in the documentation.",
            "Check your environment variable",
            "Missing or Incorrect Headers"
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => $userErrMessage,"data" => [], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 400 Bad request");
        http_response_code(400);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    function respondUnauthorized()
    {
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalUserBadRequestOrMethod, "text" => "Access token invalid or not sent", "link" => "https://", "hint" => [
            "Check your environment variable",
            "Missing or Incorrect Headers",
            "Change access token",
            "Ensure access token is sent and its valid",
            "Follow the format stated in the documentation", "All letters in upper case must be in upper case",
            "Ensure the correct method is used","Ensure authorization is sent with capital A"
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$unauthorized_token, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 401 Unauthorized");
        http_response_code(401);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    function respondInternalError($errorText,$usererr = null) {
        // If user error response is not provided, use the default internal error message
        if ($usererr === null) {
            $usererr = API_User_Response::$internal_error;
        }
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalErrorFromCode, "text" => $errorText, "link" => "https://", "hint" => [
            "Check the code",
            "Make sure data type needed is sent",
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" =>$usererr, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 500 Internal Error");
        http_response_code(500);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    function respondOK($maindata, $text)
    {
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = [];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => true, "text" => "$text", "data" => $maindata, "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 200 OK");
        http_response_code(200);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    function respondTooManyRequest(){
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalHackerWarning, "text" => "Too Many Requests", "link" => "https://", "hint" => [
            "Your server is calling the API contineously",
            "Your server is calling the API contineously",
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$toomanyrequest, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 429 Too Many Requests");
        http_response_code(429);
            // 405 Method Not Allowed
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }
    function respondNotCompleted(){
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$stopCodeFromProcessing, "text" => "Request did not get completed", "link" => "https://", "hint" => [
            "Check server",
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$request_not_processed, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header('HTTP/1.1 202 Not Completed');
        http_response_code(202);
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            // 202 Accepted Indicates that the request has been received but not completed yet.
        exit;
    }

    function respondURLChanged($data){
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalUserBadRequestOrMethod, "text" => "URL changed", "link" => "https://", "hint" => [
            "Check documentation",
            "Invalid URL"
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$url_not_valid, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header('HTTP/1.1 302 URL changed');
        http_response_code(302);
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
           // The URL of the requested resource has been changed temporarily
        exit;
    }
    function respondNotFound($data){
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalUserBadRequestOrMethod, "text" => "Too Many Requests", "link" => "https://", "hint" => [
            "URL not valid",
            "Check data sent",
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$data_not_found, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header('HTTP/1.1 404 Not found');
        http_response_code(404);
          //  Not found
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }
    function respondForbiddenAuthorized($data){
        $method = getenv('REQUEST_METHOD');
        $endpoint = Utility_Functions::getCurrentFileFullURL();

        $errordata = ["code" => API_Error_Code::$internalUserBadRequestOrMethod, "text" => "Authorization Not allowed", "link" => "https://", "hint" => [
            "Make sure to have the permission",
            "Read documentation",
        ]];
        Utility_Functions::setTimeZoneForUser('');
        $data = ["status" => false, "text" => API_User_Response::$user_has_no_access, "data" =>[], "time" => date("d-m-y H:i:sA", time()), "method" => $method, "endpoint" => $endpoint, "error" => $errordata];
        header("HTTP/1.1 403 Forbidden");
        http_response_code(403);
            // 403 Forbidden
        // Unauthorized request. The client does not have access rights to the content. Unlike 401, the client’s identity is known to the server.
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }
    // Generated a unique pub key for all users
    function getTokenToSendAPI($userPubkey,$whichtoken=1)
    {
       
        try {
            $systemData = DB_Calls_Functions::selectRows("apidatatable","privatekey,servername,tokenexpiremin",
            [   
                [
                    ['column' =>'id', 'operator' =>'=', 'value' =>$whichtoken]
                ]
            ],['limit'=>1]);
            if (!Utility_Functions::input_is_invalid($systemData)) {
                $systemData = $systemData[0];
            }
                $companyprivateKey = isset($systemData['privatekey']) ? $systemData['privatekey'] : null;
                $serverName = isset($systemData['servername']) ? $systemData['servername'] : null;
                $minutetoend =isset($systemData['tokenexpiremin']) ? $systemData['tokenexpiremin'] : null;
                $issuedAt = new \DateTimeImmutable();
                $expire = $issuedAt->modify("+$minutetoend minutes")->getTimestamp();
                $username = "$userPubkey";
                $data = [
                    'iat' => $issuedAt->getTimestamp(),
                    // Issued at: time when the token was generated
                    'iss' => $serverName,
                    // Issuer
                    'nbf' => $issuedAt->getTimestamp(),
                    // Not before
                    'exp' => $expire,
                    // Expire
                    'usertoken' => $username, // User name
                ];

                // Encode the array to a JWT string.
                //  get token below
                $auttokn = JWT::encode(
                    $data,
                    $companyprivateKey,
                    'HS512'
                );
                return $auttokn;
        } catch (\Exception $e) {
            self::respondInternalError(Utility_Functions::get_details_from_exception($e));
        }
    }
    // $whocalled=1 user 2 admin
    function ValidateAPITokenSentIN($whichtoken=1,$whocalled=1)
    {
        try {
            $systemData = DB_Calls_Functions::selectRows("apidatatable","privatekey,servername",
            [   
                [
                    ['column' =>'id', 'operator' =>'=', 'value' =>$whichtoken]
                ]
            ],['limit'=>1]);
            if (!Utility_Functions::input_is_invalid($systemData)) {
                $systemData = $systemData[0];
            }
            $companyprivateKey = isset($systemData['privatekey']) ? $systemData['privatekey'] : null;
            $serverName = isset($systemData['servername']) ? $systemData['servername'] : null;
  
            $headerName = 'Authorization';
            $headers = getallheaders();
 
            $signraturHeader = isset($headers[$headerName]) ? $headers[$headerName] : null;
            if ($signraturHeader == null) {
                $signraturHeader = isset($_SERVER['Authorization']) ? $_SERVER['Authorization'] : "";
            } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
                $signraturHeader = trim($_SERVER["HTTP_AUTHORIZATION"]);
            }
       
            if (!preg_match('/Bearer\s(\S+)/', $signraturHeader, $matches)) {
                self::respondUnauthorized();
            }

            $jwt = $matches[1];

            if (!$jwt) {
                self::respondUnauthorized();
            }
            $secretKey = $companyprivateKey;
            $token = JWT::decode($jwt, new Key($secretKey,'HS512'));
            $now = new \DateTimeImmutable();

            if ($token->iss !== $serverName || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp() || Utility_Functions::input_is_invalid($token->usertoken)) {
                self::respondUnauthorized();
            }
            $usertoken= $token->usertoken;
            // in 60 min 300 max API call
            if(self::userHasCalledAPIToMaxLimit($usertoken, 300 ,  60)){
                self::respondTooManyRequest();
            }
            if($whocalled==1){// think on if admin should be allowed to call many API
            self::prevent_api_race_condition($usertoken);
            }
            return $token;
        } catch (\Exception $e) {
            if(str_contains($e->getMessage(),'Expired')){
                self::respondUnauthorized();

            }else{
                self::respondInternalError(Utility_Functions::get_details_from_exception($e));
            }
        }
    }
    function prevent_api_race_condition($usertoken){
        try{
            $method = getenv('REQUEST_METHOD');
            $fullurl=Utility_Functions::getCurrentFileFullURL();
            DB_Calls_Functions::insertRow("apicalllog",["user_id"=>$usertoken,"apilink"=>$fullurl,'apimethod'=>$method]);
        
            // API urls that users should rest 5 seconds before recalling
            $api_not_allowed_to_becalled_sametime=Constants::TOO_MANY_API_CALLS;
            if(in_array($fullurl,$api_not_allowed_to_becalled_sametime)){
                if(Constants::CACHE_API_CALL_INTO_FILE==1){
                    // Create an instance of RedisCache
                    $cache = new CacheSystem("phpfastcache");
                    // Define a cache key
                    $saveurl=$_SERVER['PHP_SELF'];
                    $saveurl=str_replace("/","",$saveurl);
                    $saveurl=str_replace("\/","",$saveurl);

                    $cacheKey = "api_limit"."$usertoken"."$saveurl";
                    // Try to get the cached data
                    $cachedUsers = $cache->getCache($cacheKey);

                    if (!$cachedUsers) {
                        $maxseconds=5;
                        $cache->setCache($cacheKey,$maxseconds, 1);
                    }else{
                        self::respondTooManyRequest();
                        return;
                    }
                }
                // Check for recent calls to the same API
                $maxSeconds=5;
                $result = DB_Calls_Functions::selectRows("apicalllog","created_at",
                [   
                    [
                        ['column' =>'apilink', 'operator' =>'=', 'value' =>$fullurl],
                        ['column' =>'user_id', 'operator' =>'=', 'value' =>$usertoken],
                        'operator'=>'AND'
                    ]
                ]
                ,['orderBy'=>'id', 'orderDirection'=>'DESC','limit'=>2]);
                $timestamps = [];
                for ($i=0;$i<count($result);$i++) {
                    $row=$result[$i];
                    $timestamps[] = strtotime($row['created_at']);
                }
                if (count($timestamps) == 2 && ($timestamps[0] - $timestamps[1]) < $maxSeconds) {
                    self::respondTooManyRequest();
                    return;
                }
            }
    
            // delete user last 30 calls that has passed 10min
            $last30=70;
            $howmanyminutepass=5;
            DB_Calls_Functions::deleteRows( "apicalllog",
            [
                [
                    ['column' =>'TIMESTAMPDIFF(MINUTE, created_at, NOW())','operator' =>'>=','value' =>$howmanyminutepass]
                ]
            ]
            , ["limit"=>$last30,'orderBy'=>'id','orderDirection'=>'ASC']);


            // delete all OTP token that is more than 5min
            DB_Calls_Functions::deleteRows("system_otps",
            [
                [
                    ['column' =>'TIMESTAMPDIFF(MINUTE, created_at, NOW())','operator' =>'>=','value' =>1440]//1day
                ]
            ]
            , ["limit"=>$last30,'orderBy'=>'id','orderDirection'=>'ASC']);  
            //6 months time
            DB_Calls_Functions::deleteRows("responsesfromapicalllog",
            [
                [
                    ['column' =>'TIMESTAMPDIFF(MINUTE, created_at, NOW())','operator' =>'>=','value' =>262800],
                    ['column' =>'user_id','operator' =>'=','value' =>0],
                    'operator'=>'AND'
                ]
            ]
            , ["limit"=>$last30,'orderBy'=>'id','orderDirection'=>'ASC']);      
        } catch (\Exception $e) {
            self::respondInternalError(Utility_Functions::get_details_from_exception($e));
        }
    }
    function userHasCalledAPIToMaxLimit($userId, $limit = 100, $interval = 3600) {
        try{
                    // preventing too many request at a time
            // Define the maximum number of requests allowed per minute
            $maxRequestsPerMinute =$limit;
            // Create a unique identifier for the client, e.g., based on the client's IP address
            $clientIdentifier = 'rate_limit_' .$userId;
            // Retrieve the current timestamp
            $currentTimestamp = time();
            $folderPath = dirname(__DIR__);
            $filename =  $folderPath . "/logs/cache_call/rate_limit_data.json";
            // Set the path to the rate limit data file
            $rateLimitDataFile =  $filename ;
            // Initialize an empty array for rate limit data
            $requestData = [];
            
            // Check if the rate limit data file exists
            if (file_exists($rateLimitDataFile)) {
                // Load existing data from the file
                $requestData = json_decode(file_get_contents($rateLimitDataFile), true);
            }
            
            // Check if the client identifier exists in the request data
            if (!isset($requestData[$clientIdentifier])) {
                $requestData[$clientIdentifier] = [
                    'timestamp' => $currentTimestamp,
                    'count' => 1,
                ];
            } else {
                $lastTimestamp = $requestData[$clientIdentifier]['timestamp'];
                
                // Check if the time window has elapsed (1 minute in this case)
                if (($currentTimestamp - $lastTimestamp) > $interval) {
                    $requestData[$clientIdentifier] = [
                        'timestamp' => $currentTimestamp,
                        'count' => 1,
                    ];
                } else {
                    // Increment the request count
                    $requestData[$clientIdentifier]['count']++;
                }
            }
            
            // Save the updated request data
            file_put_contents( $filename , json_encode($requestData));
            
            // Check if the client has exceeded the allowed number of requests
            if ($requestData[$clientIdentifier]['count'] > $maxRequestsPerMinute) {
                //reset
                $requestData[$clientIdentifier]['count']=0;
                return true;
            }
         return false;
        } catch (\Exception $e) {
            self::respondInternalError(Utility_Functions::get_details_from_exception($e));
        }
    }
}
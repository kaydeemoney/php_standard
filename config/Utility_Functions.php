<?php

namespace Config;

use Exception;

/**
 * View 
 *
 * PHP version 5.4
 */
class Utility_Functions  extends DB_Connect
{
    // date format "d/M/Y h:ia" H 24 hours
     /**
     * Check user input and clean it
     * 
     */
    public static String $onlyNumbers="1234567890";
    public static String $onlyLetters="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    public static String $alphaNumeric="1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    public static $allowedImages = array('jpg','jpeg','svg','png','gif');
    // BOT ID WOUD BE CALLED FROM DB
    public static $allowedFileType = array('image/png', 'image/x-png', 'image/jpeg', 'image/pjpeg');

    public static function setTimeZoneForUser($timeZoneToUse=''){
        if(strlen($timeZoneToUse)>2){
            date_default_timezone_set($timeZoneToUse);
        }else{
            date_default_timezone_set(Constants::COMPANY_TIME_LOCATION_ZONE);
        }
    }
    public static function remove_pointzero($amount) {
        // Remove trailing zeros and decimal point
        $formatted_amount = rtrim($amount, '0');
        $formatted_amount = rtrim($formatted_amount, '.');
    
        return $formatted_amount;
    }
    public static function get_details_from_exception(Exception $e){
        $errorMessage = sprintf(
            "Error: %s in %s on line %d",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        return $errorMessage;
    }

    public static function clean_user_data($data,$specialchar_isallowed=0)
    {
        $conn = static::getDB();
        $input = $data;
        // remove all special chracters
        if($specialchar_isallowed==0){
            $input =preg_replace('/[^a-zA-Z0-9_.@-]/',  ' ', $input);
        }
        // This removes all the HTML tags from a string. This will sanitize the input string, and block any HTML tag from entering into the database.
        // filter_var($geeks, FILTER_SANITIZE_STRING);
        $input = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $input = trim($input, " \t\n\r");
        // htmlspecialchars() convert the special characters to HTML entities while htmlentities() converts all characters.
        // Convert the predefined characters "<" (less than) and ">" (greater than) to HTML entities:
        $input = htmlspecialchars($input, ENT_QUOTES,'UTF-8');
        // prevent javascript codes, Convert some characters to HTML entities:
        $input = htmlentities($input, ENT_QUOTES, 'UTF-8');
        $input = stripslashes(strip_tags($input));
        $input = mysqli_real_escape_string($conn, $input);

        return $input;
    }
    /**
     * Get full current file URL
     * response string
     */
    public static function getCurrentFileFullURL(){
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
        // Get the server name and port
        $servername = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        // Get the path to the current script
        $path = $_SERVER['PHP_SELF'];
        // Combine the above to form the full URL
        $endpoint = $protocol . $servername . ":" . $port . $path;
        return $endpoint;
    }
    /**
     * Check if input is empty,valid and ok
     * response Bool
     */
    public static function input_is_invalid($data)
    {
        // Check if data is null
        if (is_null($data)) {
            return true;
        }
    
        // Trim and check for string data
        if (is_string($data)) {
            $data = trim($data);
            if (strlen($data) == 0 || empty($data)) {
                return true;
            }
        }
    
        // Check for arrays
        if (is_array($data)) {
            if (empty($data)||count($data)==0) {
                return true;
            }
            // foreach ($data as $item) {
            //     if (self::input_is_invalid($item)) {
            //         return true;
            //     }
            // }
        }
    
        // Check for objects
        if (is_object($data)) {
            if ($data instanceof \stdClass) {
                // Convert stdClass to array and check if empty
                if (empty((array)$data)) {
                    return true;
                }
            } else {
                // Handle other object types
                foreach ($data as $key => $value) {
                    if (self::input_is_invalid($value)) {
                        return true;
                    }
                }
            }
        }

        
    
        // For other data types, consider them invalid if they are empty
        if (empty($data)) {
            return true;
        }
    
        return false;
    }
    public static function convertAndTruncateCryptoAddress($address) {
        $retainFirst = 5;
        $retainLast = 5;
        $replacement = '...';
    
        $addressLength = strlen($address);
        $truncated = substr($address, 0, $retainFirst) . $replacement . substr($address, -$retainLast);
    
        return $truncated;
    }
    public static function removeWhitespace($string) {
        return preg_replace('/\s+/', '', $string);
    }
    public static function isamount_valid($amount) {
        $valid=true;
        if($amount<=0){
            $valid=false;
        }else if(!is_numeric($amount)){
            $valid=false;
        }
        return $valid;
    }
    public static function generateRandomFileName($attach=''){
       return uniqid(strtolower(self::removeWhitespace(Constants::APP_NAME)), true).self::removeWhitespace($attach);
    }
    /**
     * Check if input is empty,valid and ok
     * response String
     */
    public static  function greetUsers($timeZoneToUse){
        $welcome_string="Welcome!";
        self::setTimeZoneForUser($timeZoneToUse);
        $numeric_date=date("G");

        //Start conditionals based on military time
        if($numeric_date>=0&&$numeric_date<=11)
        $welcome_string="🌅 Good Morning";
        else if($numeric_date>=12&&$numeric_date<=17)
        $welcome_string="☀️ Good Afternoon";
        else if($numeric_date>=18&&$numeric_date<=23)
        $welcome_string="😴 Good Evening";

        return $welcome_string;
    }
    /**
     * Function to handle exceptions, either to log or show it
     * 
     */
    public static function exceptionHandler($exception)
    {
        // Code is 404 (not found) or 500 (general error)
        $code = $exception->getCode();
        if ($code != 404) {
            $code = 500; 
        }
        http_response_code($code);

        $error = error_get_last();
        $errno   ="";
        $errfile = "";
        $errline = "";
        $errstr  = "";
        if ($error !== null) {
            $errno   = $error["type"];
            $errfile = $error["file"];
            $errline = $error["line"];
            $errstr  = $error["message"];
        }
 
        if (Constants::SHOW_ERRORS) {
            echo "<h1>Fatal error</h1>";
            echo "<p>Uncaught exception: '" . get_class($exception) . "'</p>";
            echo "<p>Message: '" . $exception->getMessage() . "'</p>";
            echo "<p>Stack trace:<pre>" . $exception->getTraceAsString() . "</pre></p>";
            echo "<p>Thrown in '" . $exception->getFile() . "' on line " . $exception->getLine() . "</p>";
        } else {
            self::setTimeZoneForUser('');// since its for the company , so it can be comapny location time zone
            $log = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '.txt';
            ini_set('error_log', $log);
            
            $message = "Uncaught exception: '" . get_class($exception) . "'";
            $message .= " with message '" . $exception->getMessage() . "'";
            $message .= "\nStack trace: " . $exception->getTraceAsString();
            $message .= "\nThrown in '" . $exception->getFile() . "' on line " . $exception->getLine();
            $message .=  "\nOTHER ERRORS'" .$errno." ".$errfile." ".$errline." ". $errstr;
        

            error_log($message);
        }
    }
    /**
     * Send error out
     * 
     */
    public static function errorHandler($level, $message, $file, $line)
    {
        if (error_reporting() !== 0) {  // to keep the @ operator working
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }
    /**
     * Redirect to a URL
     * 
     */
    public static function redirectToURL($new_location) {
        header("location: ".$new_location);
        exit;
    }
    /**
     * Format larger numbers to have K for thousands, M for million and B for billon
     * 
     */
    public static function formatLargeNumber($number) {
        if ($number> 1000) {
            $x = round($number);
            $x_number_format = number_format($x);
            $x_array = explode(',', $x_number_format);
            $x_parts = array('K', 'M', 'B', 'T');
            $x_count_parts = count($x_array) - 1;
            $x_display = $x;
            $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
            $x_display .= $x_parts[$x_count_parts - 1];
            $number=$x_display;
        }
        return $number;

    }
    /**
     * Used for encrypting data that one wnats to hide
     * 
     */
    public static function encryptData($code, $key) {
        // Choose the encryption cipher
        $cipher = "AES-256-CBC"; 
    
        // Get the appropriate IV length for the chosen cipher
        $ivlen = openssl_cipher_iv_length($cipher);
    
        // Generate a random initialization vector (IV)
        $iv = openssl_random_pseudo_bytes($ivlen);
    
        // Encrypt the data
        $encrypted = openssl_encrypt($code, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    
        // Encode the IV and encrypted data together using base64
        $encrypted_data = base64_encode($iv . $encrypted);
    
        // Return the encrypted data
        return $encrypted_data;
    }
    /**
     * Used for decrypting data that one wnats to hide
     * 
     */
    public static function decryptData($encrypted_data, $key) {
        // Choose the encryption cipher
        $cipher = "AES-256-CBC"; 
    
        // Decode the base64-encoded encrypted data
        $data = base64_decode($encrypted_data);
    
        // Get the IV length for the chosen cipher
        $ivlen = openssl_cipher_iv_length($cipher);
    
        // Extract the IV and the encrypted data
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
    
        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    
        // Return the decrypted data
        return $decrypted;
    }

    /**
     *     clean a string by replacing any non-alphanumeric characters with spaces.
     * 
     */
 
     public static function replace_nonalphanumeric_WithSpace($string){
         // Define a pattern to match any non-alphanumeric characters
        $pattern = '/[^a-zA-Z0-9]+/u';

        // Define a replacement string (a single space)
        $replacement = ' ';

        // Use preg_replace to replace non-alphanumeric characters with a space
        $cleaned_string = preg_replace($pattern, $replacement, $string);

        // Return the cleaned string
        return $cleaned_string;
    }
    /**
     *     SEnd response on telegram
     * 
     */

    /**
     *    Delete all files and folder in a folder
     * 
     */
    public static function deleteAllInAFolder($dir,$dontdelete){
        $folderPath = realpath(dirname(__DIR__));
        $dir="$folderPath/$dir";
        $dontdelete="$folderPath/$dontdelete";
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                self::deleteAllInAFolder($file,$dontdelete);
            } else {
                unlink($file);
            }
        }
        if($dir!=$dontdelete && file_exists($dir)){
            rmdir($dir);
        }
   }
       /**
     *    Delete all files and folder in a folder
     * 
     */
    public static function deleteAFileinFolder($name, $dir){
    // $folderPath = realpath(dirname(__DIR__));

    $data=$name;
    $dirHandle = opendir("$dir");
    while ($file = readdir($dirHandle)) {
        if ($file==$data) {
            unlink($dir."/".$file);
        }
    }
    closedir($dirHandle);
   }
          /**
     *    Compress image and create a new imae as output
     * 
     */
    public static function getOptimalMaxWidth($originalWidth) {
        // Define your width thresholds and corresponding maximum widths
        $widthThresholds = [
            4000 => 2000,
            3000 => 1500,
            2000 => 1000,
            1000 => 800,
            0 => 600 // Default width for smaller images
        ];
    
        // Determine the best max width based on the original width
        foreach ($widthThresholds as $threshold => $maxWidth) {
            if ($originalWidth >= $threshold) {
                return $maxWidth;
            }
        }
    
        // Default to the smallest width if no threshold is met
        return 600;
    }
    
    public static function compressImage($pathToImages, $pathToThumbs, $fname) {
        $jpegQuality = 85; // Quality for JPEG images
        $pngQuality = 9; // Quality for PNG images (0-9, with 0 being no compression and 9 being maximum compression)
        $webpQuality = 85; // Quality for WebP images
        $maxFileSize = 500 * 1024; // Maximum file size (500 KB)
        $info = pathinfo($pathToImages . $fname);
        $extension = strtolower($info['extension']);
    
        // Handle SVG files separately
        if ($extension === 'svg') {
            return self::minifySvg($pathToImages, $pathToThumbs, $fname);
        }
    
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'jfif':
                $img = @imagecreatefromjpeg("{$pathToImages}{$fname}");
                break;
            case 'png':
                $img = @imagecreatefrompng("{$pathToImages}{$fname}");
                break;
            case 'gif':
            case 'jiff':
                $img = @imagecreatefromgif("{$pathToImages}{$fname}");
                break;
            case 'webp':
                $img = @imagecreatefromwebp("{$pathToImages}{$fname}");
                break;
            default:
                return false; // Unsupported file type
        }
    
        // Get original image dimensions
        if (!$img) {
            throw new Exception("Failed to create image from file: {$pathToImages}{$fname}");

            return false;
        }
    
        $width = imagesx($img);
        $height = imagesy($img);
    
        // Determine the best max width
        $maxWidth = self::getOptimalMaxWidth($width);
    
        // Calculate the new width while maintaining aspect ratio
        $new_width = min($width, $maxWidth);
        $new_height = floor($height * ($new_width / $width));
    
        // Create a new temporary image
        $tmp_img = imagecreatetruecolor($new_width, $new_height);
    
        // Preserve transparency for PNG, GIF, and WebP images
        if ($extension == 'png' || $extension == 'gif' || $extension == 'jiff' || $extension == 'webp') {
            imagecolortransparent($tmp_img, imagecolorallocatealpha($tmp_img, 0, 0, 0, 127));
            imagealphablending($tmp_img, false);
            imagesavealpha($tmp_img, true);
        }
    
        // Copy and resize old image into new image with better quality
        imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
        // Save thumbnail into a file with proper compression
        $outputPath = "{$pathToThumbs}{$fname}";
        $saved = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'jfif':
                $saved = imagejpeg($tmp_img, $outputPath, $jpegQuality);
                break;
            case 'png':
                $saved = imagepng($tmp_img, $outputPath, $pngQuality);
                break;
            case 'gif':
            case 'jiff':
                $saved = imagegif($tmp_img, $outputPath);
                break;
            case 'webp':
                $saved = imagewebp($tmp_img, $outputPath, $webpQuality);
                break;
        }
    
        // Ensure the file was saved correctly
        if (!$saved) {
            throw new Exception("Failed to save image to path: {$outputPath}");

            return false;
        }
    
        // Ensure file size is reduced
        while (filesize($outputPath) > $maxFileSize) {
            if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'jfif') {
                $jpegQuality -= 5;
                if ($jpegQuality < 10) break; // Avoid too low quality
                imagejpeg($tmp_img, $outputPath, $jpegQuality);
            } elseif ($extension == 'png') {
                $pngQuality += 1;
                if ($pngQuality > 9) break; // Max compression level for PNG
                imagepng($tmp_img, $outputPath, $pngQuality);
            } elseif ($extension == 'webp') {
                $webpQuality -= 5;
                if ($webpQuality < 10) break; // Avoid too low quality
                imagewebp($tmp_img, $outputPath, $webpQuality);
            }
    
            // Check if the file exists and its size
            if (!file_exists($outputPath)) {
                throw new Exception("File does not exist after compression attempt: {$outputPath}");
                return false;
            }
        }
    
        // Free up memory
        imagedestroy($img);
        imagedestroy($tmp_img);
    
        return true;
    }
    
    public static function minifySvg($pathToImages, $pathToThumbs, $fname) {
        $svgContent = file_get_contents("{$pathToImages}{$fname}");
    
        // Minify SVG content
        $minifiedSvg = preg_replace('/>\s+</', '><', $svgContent); // Remove spaces between tags
        $minifiedSvg = preg_replace('/\s+/', ' ', $minifiedSvg); // Collapse multiple spaces into one
    
        // Save minified SVG content to the target path
        file_put_contents("{$pathToThumbs}{$fname}", $minifiedSvg);
    
        return true;
    }

   /**
     *    Make a text redable after it was converted into non redable format
     * 
     */
    public static function normalizeText($text) {
        $text = str_replace("\\r\\n", "", $text);
        $text = trim(preg_replace('/\t+/', '', $text));
        
        $text = htmlspecialchars_decode($text, ENT_QUOTES);
        $text =html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars_decode($text, ENT_QUOTES);
        // Remove "&nbsp;" entities
        $text = str_replace("&nbsp;", '', $text);
        // Strip HTML tags
        $text = strip_tags($text);

        $text = nl2br($text);
        return $text;
   }
      /**
     *    Truncate text
     * 
     */
    public static function  truncateText($text,$maxlength,$textReplacement="...") {
       // If the text is already shorter than the maximum length, return it as is
       if (strlen($text) <= $maxlength) {
            return $text;
        }
        
        // Truncate the text to the maximum length
        $reduced = substr($text, 0, $maxlength);

        // Ensure the truncation doesn't cut off a word by finding the last space
        $lastSpacePosition = strrpos($reduced, " ");

        // If a space was found, truncate to that position
        if ($lastSpacePosition !== false) {
            $reduced = substr($reduced, 0, $lastSpacePosition);
        }

        // Append the replacement text (e.g., ellipsis)
        return $reduced . $textReplacement;
   }
         /**
     *     calculates the absolute difference in days between two dates. 
     * date format is Y-m-d e.g "2023-05-20"
     * 
     */
    public static function getDayDifferenceBetweenTwoDays($startDay,$timeZoneToUse,$endDay=null) {
        // Create DateTime objects for the end date and today's date
        self::setTimeZoneForUser($timeZoneToUse);
        if ($endDay === null) {
            $endDay = date("Y-m-d");
        }
        $earlier = new \DateTime($startDay);
        $later = new \DateTime($endDay);

        // Calculate the difference between the two dates
        $abs_diff = $later->diff($earlier)->format("%a");

        // Return the absolute difference in days
        return $abs_diff;
   }
            /**
     *    Returns IP address
     * 
     */
    public static function getIpAddress(){  
    

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }


        return $ipaddress;  
    }
                /**
     *    Get user current location details
     *  send what you need, 0 means location, 1 country, 2 region , 3 city
     */
    public static function getCurrentLocation($userIp,$whatisneeded=0){
        $url = "http://ipinfo.io/".$userIp."/geo";
        // $json     = file_get_contents($url);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $json = curl_exec($curl);
        curl_close($curl);
        $json     = json_decode($json, true);
        $country  = isset($json['country']) ?  $json['country'] : "";
        $region   = isset($json['region']) ? $json['region'] : "";
        $city     = isset($json['city']) ? $json['city'] : "";
        $location = isset($json['loc']) ? $json['loc'] : "";

        if($whatisneeded==1){
            return $country;
        }else  if($whatisneeded==2){
            return $region;
        }else  if($whatisneeded==3){
            return $city;
        }else{
            return $location;
        }

       
    }
    /**
     *    Returns Browser Info
     * 
     */
    public static function getBrowserInfo() {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";

        // Determine the platform
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'Mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'Windows';
        }

        // Determine the browser
        $ub = "Unknown";
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/OPR/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Chrome/i', $u_agent) && !preg_match('/Edge/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent) && !preg_match('/Edge/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        } elseif (preg_match('/Edge/i', $u_agent)) {
            $bname = 'Edge';
            $ub = "Edge";
        } elseif (preg_match('/Trident/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        }

        // Get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (preg_match_all($pattern, $u_agent, $matches)) {
            $version = $matches['version'][0];
        }

        // If version is empty or null, set it to "?"
        if (self::input_is_invalid($version)) {
            $version = "?";
        }

        // Return an array containing browser information
        return array(
            'userAgent' => $u_agent,
            'name'      => $bname,
            'version'   => $version,
            'platform'  => $platform,
            'pattern'   => $pattern
        );
    }
                  /**
     *    Get How many minute has passed between two Unix timestamp
     * 
     */
    public static function getMinBetweentimes($latesttime, $oldtime) {
        // Initialize the variable to store the difference in minutes
        $minbtwis = 0;
    
        // Calculate the difference between the latest time and the old time
        $subtractit = $latesttime - $oldtime;
    
        // Convert the time difference to minutes
        // The result is rounded to the nearest whole number
        $minbtwis = round($subtractit / (60));
    
        // Return the difference in minutes
        return $minbtwis;
    }
    /** check if text contains special charactwrs
     *   will return either 1 or 0, indicating whether the input string contains any special characters according to the defined pattern.
     * 
     */
    public static function textContainsSpecialCharacters($string) {
         // Define a regular expression pattern for special characters
        $pattern = '/[\'^£$%&*()}{@#~?><>,|=+¬\-\/.]/';
        // Check if the string contains any special characters
        return preg_match($pattern, $string);
    }
    /** check if text contains emoji
     *   will return either 1 or 0, indicating whether the input string contains any special characters according to the defined pattern.
     * 
     */
    public static function textHasEmojis($string){
        $emojis_regex =
            '/[\x{0080}-\x{02AF}'
            .'\x{0300}-\x{03FF}'
            .'\x{0600}-\x{06FF}'
            .'\x{0C00}-\x{0C7F}'
            .'\x{1DC0}-\x{1DFF}'
            .'\x{1E00}-\x{1EFF}'
            .'\x{2000}-\x{209F}'
            .'\x{20D0}-\x{214F}'
            .'\x{2190}-\x{23FF}'
            .'\x{2460}-\x{25FF}'
            .'\x{2600}-\x{27EF}'
            .'\x{2900}-\x{29FF}'
            .'\x{2B00}-\x{2BFF}'
            .'\x{2C60}-\x{2C7F}'
            .'\x{2E00}-\x{2E7F}'
            .'\x{3000}-\x{303F}'
            .'\x{A490}-\x{A4CF}'
            .'\x{E000}-\x{F8FF}'
            .'\x{FE00}-\x{FE0F}'
            .'\x{FE30}-\x{FE4F}'
            .'\x{1F000}-\x{1F02F}'
            .'\x{1F0A0}-\x{1F0FF}'
            .'\x{1F100}-\x{1F64F}'
            .'\x{1F680}-\x{1F6FF}'
            .'\x{1F910}-\x{1F96B}'
            .'\x{1F980}-\x{1F9E0}]/u';
        preg_match($emojis_regex, $string, $matches);
        
        // Remove all non-ASCII characters (including emojis)
        $cleanString = preg_replace('/[^\x20-\x7F]/', '', $string);
        
        // If the cleaned string is different from the original, it contained emojis
        return !empty($matches) || $cleanString !== $string;
    }
    /**
     * Add days to Unix timestamp and return  Unix timestamp
     * 
    */
    public static function addDaysToUnixTimestamp($day,$time){
        $currentTime = $time;
       //The amount of hours that you want to add.
       $daysToAdd = $day;
       //Convert the hours into seconds.
       $secondsToAdd = $daysToAdd * (24 * 60* 60);
       //Add the seconds onto the current Unix timestamp.
       $newTime = $currentTime + $secondsToAdd;
       return $newTime;
    }
    /**
     * Randomize input and generate a new string of certain length
     * 
    */
    public static function generate_string_from_chars($input, $length) {
        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
     

        
        return $random_string;
    }
    /**
     * Encrypt password
     * 
    */
    public static function Password_encrypt($user_pass){
        $BlowFish_Format="$2y$10$";
        $salt_len=24;
        $salt=self::Get_Salt($salt_len);
        $the_format=$BlowFish_Format . $salt;
        
        $hash_pass=crypt($user_pass, $the_format);
        return $hash_pass;
    }
    /**
     * Form password salt
     * 
    */
    public static function Get_Salt($size) {
        $Random_string= md5(uniqid(mt_rand(), true));
        
        $Base64_String= base64_encode($Random_string);
        
        $change_string=str_replace('+', '.', $Base64_String);
        
        $salt=substr($change_string, 0, $size);
        
        return $salt;
    }
    /**
     *  verify if password has is valid
     * 
    */
    public static function is_password_hash_valid($pass, $storedPass) {
        $Hash=crypt($pass, $storedPass);
        if ($Hash===$storedPass) {
            return(true);
        } else {
            return(false);
        }
    }
    /**
     *  is phone number valid
     * 
    */
    public static function isPhoneNumbervalid($phone) {
        $regExp = '/^[0-9]{11}+$/';
        if (preg_match($regExp, $phone)){
            return true;
        }else{
            return false;
        }
    }
    public static function isValueNumberOnly($data) {

        if(preg_match("/^([0-9' ]+)$/",$data)){

            return true;

        }else{

            return false;

        }

    }
    /**
     *  Check if email is valid
     * 
    */
    public static function isEmailValid($email) {
        if ( filter_var($email, FILTER_VALIDATE_EMAIL) ){
            return true;
        }else{
            return false;
        }
    }
        /**
     *  Check if username is valid
     * 
    */
    public static function isUsernameValid($username) {

        if ( preg_match('/\s/',$username)||preg_match('/\./',$username)||preg_match('/[^a-z0-9 ]+/i',$username)|| self::textHasEmojis($username) ){
            return false;
        }else{
            return true;
        }
    }
    /**
     *  Check if date follows a pattern like  'Y-m-d', 'Y-m-d H:i:s'
     * 
    */
    public static function isDateValid($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    /**
     *  Check if password is string
     * 
    */
    public static function isPasswordStrong($password){
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

       
        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 6) {
            return false;
        }else  if (!preg_match("((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,100})",$password)) {
            return false;
        }else{
            return true;
        }
    }
    /**
     *  Check if pin is valid
     * 
    */
    public static function isPinValid($pin){
        // Check for consecutive numbers
        $consecutive1 = '0123456789';
        $consecutive2 = '9876543210';

        if (strpos($consecutive1, $pin) !== false || strpos($consecutive2, $pin) !== false) {
            return false;
        }else if(strlen($pin) < 4||strlen($pin) > 4) {
            return false;
        }else  if (preg_match('/(\d)\1{3}/', $pin)) {
            return false;
        }else{
            return true;
        }
    }
    /**
     *  Check if image uploaded is valid
     * 
    */
    public static function isImageUploadedValid($file,$isMultiple=0){
        $error = $file['error'];
        if ($isMultiple==1) {
            foreach ($error as $err) {
                if ($err !== UPLOAD_ERR_OK) {
                    return false;
                }
            }
            return true;
        } else {
            return $error === UPLOAD_ERR_OK;
        }
    }
        /**
     *  Check if image uploaded is larger than a value
     * maximum defaul is 2MB
     * second parameter is how mnay megabtye 
     *  $max_size = 2 * 1024 * 1024; // 2MB in bytes
    */
    public static function isImageTooLarge($file,$isMultiple=0){
        $imgSize = $file['size'];
        $error = $file['error'];
        $maxByte = Constants::MAX_FILE_UPLOAD * 1024 * 1024;
  
        if ($isMultiple) {
            foreach ($error as $err) {
                if ($err !== UPLOAD_ERR_OK) {
                    return true;
                }
            }
            foreach ($imgSize as $size) {
                if ($size > $maxByte) {
                    return true;
                }
            }
        } else {
            if ($error !== UPLOAD_ERR_OK) {
                return true;
            }
            if ($imgSize > $maxByte) {
                return true;
            }
        }
    
        return false;
    }
    public static function uploadImage($file, $fullimglocation,$fulltmpimglocation,$assetimgloc,$nametoattachthefile,$ismultiple=false){
        $imagetoSave ='';
        $imageNames ='';
        if($ismultiple){
            if (!empty($file['name'])) {
                // multiple file upload
                foreach ($file['name'] as $key => $val) {
                    // File upload path
                    $fileName = $file['name'][$key];
                    $targetFilePath = $fullimglocation . $fileName;
                    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
                    $fileType_lc = strtolower($fileType);
                    $fileName = self::clean_user_data(preg_replace("#[^a-z0-9.]#i", "", $fileName), 1);
                    $new_img_name = self::generateRandomFileName($nametoattachthefile) . "." . $fileType;
                    $img_upload_path =  $fulltmpimglocation . $new_img_name;

                    // MOVE TO TEM DIRECTORY FOR COM
                    move_uploaded_file($file["tmp_name"][$key], $img_upload_path);
                    // Upload file to server
                    self::compressImage($fulltmpimglocation, $fullimglocation, $new_img_name);
                    //Delete from temp directory after compressing
                    self::deleteAFileinFolder($new_img_name, $fulltmpimglocation);
                    $imagetoSave .= "$assetimgloc$new_img_name,";
                    $imageNames.="$new_img_name,";
                }
            }
        }else{
              // File upload path
              $fileName = $file['name'];
              $targetFilePath = $fullimglocation . $fileName;
              $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
              $fileType_lc = strtolower($fileType);
              $fileName = self::clean_user_data(preg_replace("#[^a-z0-9.]#i", "", $fileName), 1);
              $new_img_name = self::generateRandomFileName($nametoattachthefile) . "." . $fileType;
              $img_upload_path =  $fulltmpimglocation . $new_img_name;

              // MOVE TO TEM DIRECTORY FOR COM
              move_uploaded_file($file["tmp_name"], $img_upload_path);
              // Upload file to server
              self::compressImage($fulltmpimglocation, $fullimglocation, $new_img_name);
              //Delete from temp directory after compressing
              self::deleteAFileinFolder($new_img_name, $fulltmpimglocation);
              $imagetoSave .= "$assetimgloc$new_img_name";
              $imageNames.=$new_img_name;
        }
        return  ['imagename'=>$imageNames,'imagepath'=>$imagetoSave] ;
    }
    public static function getBase64ImageSize($base64Image){ //return memory size in B, KB, MB
        try{
            $size_in_bytes = (int) (strlen(rtrim($base64Image, '=')) * 3 / 4);
            $size_in_kb    = $size_in_bytes / 1024;
            $size_in_mb    = $size_in_kb / 1024;

            return $size_in_kb;
        }  catch(\Exception $e){
            return $e;
        }
    }
            /**
     *  Check if String is HTML, or contains HTML
    */
    public static function isStringHTML($string){
        return $string != strip_tags($string) ? true:false;
    }
                /**
     * returns how mnay weeks is in a month
    */
    function howManyWeeksInMonth($year, $month,$timeZoneToUse) {
        // Get the number of days in the given month
        self::setTimeZoneForUser($timeZoneToUse);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
        // Get the week number of the last day of the month
        $lastDayOfWeek = date("W", strtotime("$year-$month-$daysInMonth"));
    
        // Get the week number of the first day of the month
        $firstDayOfWeek = date("W", strtotime("$year-$month-01"));
    
        // Calculate the difference between the week numbers
        $weeksInMonth = $lastDayOfWeek - $firstDayOfWeek + 1;
    
        return $weeksInMonth;
    }
                  /**
     * round up to nearest round to
    */
    function roundToTheNearestAnything($value, $roundTo) {
        $value = floor($value);
        $mod = $value % $roundTo;
        return $value + ($mod < ($roundTo / 2) ? -$mod : $roundTo - $mod);
    }
                  /**
     *Validate Google Captacha
    */
    function is_theGoogleCaptchaValid($token) {
        $isTokenValid=false;
        $secret = '6LdXo4YiAAAAAETHal4ANulB3J50cxNM1UnD-UrR';
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$token;
        $curld = curl_init();
        $data_string = array();
        curl_setopt($curld, CURLOPT_POST, true);
        curl_setopt($curld, CURLOPT_POSTFIELDS, http_build_query($data_string));
        curl_setopt($curld, CURLOPT_URL, $url);
        curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($curld);
        curl_close($curld);
        $verifyResponse = $output;
        $responseData = json_decode($verifyResponse);
        if(isset($responseData->success) && $responseData->success){
            $isTokenValid=true;
        }
        return $isTokenValid;
    }


}




?>
<?php

namespace Config;


class Constants
{
   /*
   KEY POINTS
   1) Make sure you have setup system settings table
   2) Make sure to have added all API credentials
   2) Make sure to have added API data table for token genertaion 1 for long lasting token and 2 fr short
   2) Make sure to have added tgbotids if you qant to use tg bots

   */
    // LIVE DB
       /**
     * Database host
     * @var string
     */
    const BASE_URL = "http://localhost/taskcolony";// dont add / in front "https://test.com.ng";// dont add / in front
  
       /**
     * 0 for local database and 1 for live database
     * @var int
     */
    const LIVE_OR_LOCAL = 0; /**
 * App Name
 * @var string
 */
    const LIVE_DB_HOST = 'localhost';
       /**
     * Database name
     * @var string
     */
    const LIVE_DB_NAME = 'ogvblog_taskcolony';
       /**
     * Database Username
     * @var string
     */
    const LIVE_DB_USER = 'ogvblog_taskcolony';
           /**
     * Database Password
     * @var string
     */
    const LIVE_DB_PASSWORD = 'c.h~G4@uF4l6';


    // LOCAL DB
   /**
     * Database host
     * @var string
     */
    const DB_HOST = '127.0.0.1';
   /**
     * Database name
     * @var string
     */
    const DB_NAME = 'taskcolony';
   /**
     * Database username
     * @var string
     */
    const DB_USER = 'root';
               /**
     * Database Password
     * @var string
     */
    const DB_PASSWORD = '';

    /**
     * Show or hide error messages on screen
     * @var boolean
     */
    const SHOW_ERRORS =false;
    /**
     * BASE URL APP domain name
     * @var string
     */

	const APP_NAME = "Task Colony";
   //      /**
   //   * Where cached data should be stored
   //   * @var string
   //   */
	// const CACHE_DIRECTORY = 'D:\Temp';
    /**
     * How long should a long cached data be stored, used for data that should be stored for a very long time 
     * @var int
     */
	const LONG_CACHE = 2628288;//1 month
        /**
     * How long should a short cached data be stored, used for data that should be stored for a very short time 
     * @var int
     */
	const SHORT_CACHE = 60;// 1 minute
    /**
     * Current app version
     * @var string
     */
    const CURRENT_VERSION = "1.0";// where all assets is
        /**
     *AAll API link that too much call on is not allowed
     */
    const TOO_MANY_API_CALLS=array("http://localhost:80/taskcolony/api/test/apicache.php");
       /**
     *Short app name for creating tags, it woul be joined with text to form e.gwwdw
     */
    const APP_INITIAL = "NG";// where all assets is
   /**
     * Firebase server key
     * @var string
     */
    const FIREBASE_SERVER_KEY = '';// 1 minute
     /**
     * Activate Caching of API calls into file, for preventing double API call, once activated the use of DB for prventing would not work, if deactivated DB would be used
     */
    const CACHE_API_CALL_INTO_FILE = 0;// where all assets is
      /**
     * if 1 then account officer would be assigned to all users that register from point in which its set to one, if its 1 you want to set it make sure the marketer table is filled up with valid data
     */
    const ASSIGN_ACCOUNT_OFFICER = 1;// where all assets is
          /**
     * if 1 THEN USERS would be allowed to login/register
     */
    const ALLOW_USER_TO_LOGIN_REGISTER = 1;// where all assets is
             /**
     * Company location timezone
     */
    const COMPANY_TIME_LOCATION_ZONE = "Africa/Lagos";
                 /**
     *Call OTP 
     */
    const PRESENT_CALL_OTP_SYSTEM = 1;//1 TG 2 API
    const MAX_FILE_UPLOAD = 2;
    const SUPPORT_EMAIL = "support@taskcolony.com";
    const LOGO_URL = self::BASE_URL."/assets/images/taskcolnylogo.png";

    const MAILHOST ='mail.taskcolony.com';
    const MAILPORT ='465';
    const  MAILSENDER='help@taskcolony.com';

    const  MAILSENDERPASS='v4mapimail20+';

   }
?>
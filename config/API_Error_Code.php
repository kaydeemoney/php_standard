<?php

namespace Config;

class API_Error_Code
{
    // Error code that starts with 1 is from us,2 is from third party

    // FROM WHAT SYSTEM____FROM WHERE__ERRORTYPE

    // FROM WHAT SYSTEM
    // internal(our code) -1
    // external(Third party API) -2

    // FROM WHERE INTERNAL
    // database insert error-->1
    // databse update error-->2
    // database delete error-->3
    // user wrong action error ---> 4 (insufficient fund, empty data,authorization)
    // Hacker attempt--->5 (wrong method/user not found)
    // Code error,a line crashed--->6 
    // intentional code stop--->7 

    // FROM WHERE EXTERNAL
    // Call to API failed -->6
    // Sent wrong data to API->7
    // Failed to satisfy API need on their dashboard ->8(Insufficinet fund)

    // ERRORTYPE
    // 1--Fatal
    // 2--Warning

    // General errors
    public  static $internalHackerWarning=151;
    public  static $internalUserBadRequestOrMethod=141;
    public  static $internalErrorFromCode=161;
    public  static $stopCodeFromProcessing=171;

    
}
<?php

namespace DatabaseCall;

use Config;

/**
 * Post model
 *
 * PHP version 5.4
 */
class System_Defaults extends Config\DB_Connect
{
    /**
     * Get all the posts as an associative array
     *
     * @return array
     */

    /*
    If a data is not needed send empty to it, bank name and namk code should be join as bankname^bankcode

     */
    // APi functions

    public static function getAllSystemSetting(){
        $connect = static::getDB();
        $alldata=[];
        $active=1;
        $getdataemail =  $connect->prepare("SELECT * FROM systemsettings WHERE id=?");
        $getdataemail->bind_param("s",$active);
        $getdataemail->execute();
        $getresultemail = $getdataemail->get_result();
        if( $getresultemail->num_rows> 0){
            $getthedata= $getresultemail->fetch_assoc();
            $alldata=$getthedata;
        }
        return $alldata;
    }
    public static function  GetActiveSendGridApi(){
        $connect = static::getDB();
        $alldata=[];
        $active=1;
        $getdataemail =  $connect->prepare("SELECT * FROM sendgridapidetails WHERE status=?");
        $getdataemail->bind_param("s",$active);
        $getdataemail->execute();
        $getresultemail = $getdataemail->get_result();
        if( $getresultemail->num_rows> 0){
            $getthedata= $getresultemail->fetch_assoc();
            $alldata=$getthedata;
        }
        return $alldata;
    }
    public static function  GetActiveTermiApi(){
        $connect = static::getDB();
        $alldata=[];
        $active=1;
        $getdataemail =  $connect->prepare("SELECT * FROM termiapidetails WHERE status=?");
        $getdataemail->bind_param("s",$active);
        $getdataemail->execute();
        $getresultemail = $getdataemail->get_result();
        if( $getresultemail->num_rows> 0){
            $getthedata= $getresultemail->fetch_assoc();
            $alldata=$getthedata;
        }
        return $alldata;
    } 
    public static function  GetActiveKudiApi(){
        $connect = static::getDB();
        $alldata=[];
        $active=1;
        $getdataemail =  $connect->prepare("SELECT * FROM kudiapidetails WHERE status=?");
        $getdataemail->bind_param("s",$active);
        $getdataemail->execute();
        $getresultemail = $getdataemail->get_result();
        if( $getresultemail->num_rows> 0){
            $getthedata= $getresultemail->fetch_assoc();
            $alldata=$getthedata;
        }
        return $alldata;
    }
    public static function  GetActiveSmartSolutionApi(){
        $connect = static::getDB();
        $alldata=[];
        $active=1;
        $getdataemail =  $connect->prepare("SELECT * FROM smartsolutionapidetails WHERE status=?");
        $getdataemail->bind_param("s",$active);
        $getdataemail->execute();
        $getresultemail = $getdataemail->get_result();
        if( $getresultemail->num_rows> 0){
            $getthedata= $getresultemail->fetch_assoc();
            $alldata=$getthedata;
        }
        return $alldata;
    }

}

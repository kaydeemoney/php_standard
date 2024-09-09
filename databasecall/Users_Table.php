<?php

namespace DatabaseCall;

use Config;
use Config\DB_Calls_Functions;
use Config\Utility_Functions;

/**
 * Post model
 *
 * PHP version 5.4
 */
class Users_Table extends Config\DB_Connect
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
    public static function getAccountOfficierToAssign()
    {
        //input type checks if its from post request or just normal function call
        $assignnumber = 0;
        $maxassignumber = 0;
        $accountoffier_is = 'IOSD';
        $last_ac_officer = '';


        $responseData = DB_Calls_Functions::selectRows("users", "users.account_officer,marketers.account_officer_no", [], ['joins' => [
            [
                'type' => 'LEFT',
                'table' => 'marketers',
                'condition' => 'users.account_officer=marketers.track_id'
            ],
            'orderBy' => 'users.id',
            'orderDirection' => 'DESC',
            'limit' => 1
        ]]);
        if (!Utility_Functions::input_is_invalid($responseData)) {
            $responseData = $responseData[0];
            $last_ac_officer = $responseData['account_officer'];
            $assignnumber = $responseData['account_officer_no'];
        }
        if (strlen($last_ac_officer) > 2) {
            // GET MAX ASSIGN NUMBER
            // Fetch the maximum assignment number from marketers table
            $responseData = DB_Calls_Functions::selectRows("marketers", "MAX(account_officer_no) AS max_assign_number");
            if (!Utility_Functions::input_is_invalid($responseData)) {
                $responseData = $responseData[0];
                $maxassignumber = $responseData['max_assign_number'];
            }
            // Find the next active account officer
            $Nextassignnumber = $assignnumber + 1;
            $accountoffier_details = null;
            while ($Nextassignnumber <= $maxassignumber) {
                $itsactive = 1;
                $responseData = DB_Calls_Functions::selectRows("marketers", "track_id", 
                [   
                    [
                        ['column' =>'status', 'operator' =>'=', 'value' =>$itsactive],
                        ['column' =>'account_officer_no', 'operator' =>'=', 'value' =>$Nextassignnumber]
                        ,'operator'=>'AND'
                    ]
                ]
                );
                if (!Utility_Functions::input_is_invalid($responseData)) {
                    $responseData = $responseData[0];
                    $accountoffier_details = $responseData;
                }
                if ($accountoffier_details) {
                    break;
                }
                $Nextassignnumber++;
            }

            // If no active account officer found, start from the beginning
            if (!$accountoffier_details) {
                $Nextassignnumber = 1;
                while ($Nextassignnumber <= $maxassignumber) {
                    $itsactive = 1;
                    $responseData = DB_Calls_Functions::selectRows("marketers", "track_id", 
                    [   
                        [
                            ['column' =>'status', 'operator' =>'=', 'value' =>$itsactive],
                            ['column' =>'account_officer_no', 'operator' =>'=', 'value' =>$Nextassignnumber]
                            ,'operator'=>'AND'
                        ]
                    ]);
                    if (!Utility_Functions::input_is_invalid($responseData)) {
                        $responseData = $responseData[0];
                        $accountoffier_details = $responseData;
                    }
                    if ($accountoffier_details) {
                        break;
                    }
                    $Nextassignnumber++;
                }
            }

            // Set the account officer details if found
            if ($accountoffier_details) {
                $accountoffier_is = $accountoffier_details['track_id'];
            }
        } else {
            // pick number active
            $itsactive = 1;
            $canassign = 0;

            $responseData = DB_Calls_Functions::selectRows("marketers", "track_id", 
            [   
                [
                    ['column' =>'status', 'operator' =>'=', 'value' =>$itsactive],
                    ['column' =>'account_officer_no', 'operator' =>'>', 'value' =>$canassign]
                    ,'operator'=>'AND'
                ]
            ], ['orderBy' => 'id', 'orderDirection' => 'DESC']);
            if (!Utility_Functions::input_is_invalid($responseData)) {
                $responseData = $responseData[0];
                $accountoffier_is = $responseData['track_id'];
            }
        }
        return $accountoffier_is;
    }
}

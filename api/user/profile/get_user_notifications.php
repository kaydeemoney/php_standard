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
header("Cache-Control: no-cache");
// Uncomment below if you need to allow caching on this api
// header("Cache-Control: public, max-age=$totalexpiresec"); // 86400 seconds = 24 hours
// $utility_class_call->setTimeZoneForUser('');
// header("Expires: " . gmdate('D, d M Y H:i:s', $expirationTime) . ' GMT');

$api_status_code_class_call = new Config\API_Status_Code;
$db_call_class = new Config\DB_Calls_Functions;

if (getenv('REQUEST_METHOD') == $apimethod) {
    try {
        $decodedToken = $api_status_code_class_call->ValidateAPITokenSentIN(whichtoken: 1);
        $user_pubkey = $decodedToken->usertoken;
        $getuserattached = $db_call_class->selectRows("users", "id", [[
            ['column' => 'userpubkey', 'operator' => '=', 'value' => $user_pubkey],]
        ]);
        $search = isset($_GET['search']) ? $utility_class_call->clean_user_data($_GET['search']) : '';
        $sort = isset($_GET['sort']) ? $utility_class_call->clean_user_data($_GET['sort']) : '';
        $per_page = isset($_GET['per_page']) ? $utility_class_call->clean_user_data($_GET['per_page']) : 100;
        $page_no = isset($_GET['page']) ? $utility_class_call->clean_user_data($_GET['page']) : 1;
        // no repated numbers or contineus number allowed, 
        if ($utility_class_call->input_is_invalid($getuserattached)) {
            $api_status_code_class_call->respondUnauthorized();
        }else {
            $userdata=$getuserattached[0];
            $userID=$userdata['id'];
            $base_url=Constants::BASE_URL;
            $limittext=1;
            $showvoucher=false;
            $whereclause = [];
            $topLevelOperator = "AND";
            array_push($whereclause, [
                ['column' => 'un.userid', 'operator' => '=', 'value' =>$userID],
            ]);

            if (!$utility_class_call->input_is_invalid($search)) {
                $search = "%$search%";
                array_push( $whereclause, [
                ['column' => 'un.notificationtext', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'un.notificationtitle', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'un.orderrefid', 'operator' => 'LIKE', 'value' => $search],
                ['column' => 'un.notificationcode', 'operator' => 'LIKE', 'value' => $search],
                'operator' => 'OR']);
            }
            $offset = ($page_no - 1) * $per_page;

            $total_numRow = $db_call_class->checkIfRowExistAndCount("usernotifications un", $whereclause, [], $topLevelOperator);
            // get the dataf
            $getAllData = $db_call_class->selectRows("usernotifications un", "un.notificationtext,un.notificationtype,un.orderrefid,un.notificationstatus,un.notificationcode,un.notificationtitle,un.created_at", $whereclause, 
            [ 
            'limit' =>$per_page, 'orderBy' => 'un.id', 'orderDirection' => 'DESC', 'pageno' => $page_no], $topLevelOperator);


            if($utility_class_call->input_is_invalid($getAllData)){
                $text=API_User_Response::$data_not_found;
            }else{
                $text=API_User_Response::$data_found;
                  // notificationtype  1= normal, 2= transaction 
               // Iterate through the data array
               foreach ($getAllData as &$row) {
                    $orderid=$row['orderrefid'];
                    $row['transdata']=null;
                    if(!$utility_class_call->input_is_invalid($orderid)){
                        $transbasemaintransname='';
                        $allUserTransactions = $db_call_class->selectRows(
                            "userwallettrans uw",
                            "uw.systemsendwith,uw.userid,COALESCE(uw.theusdval,'0') AS theusdval,uw.orderid,uw.vc_creationfee,uw.vc_maintainfee,uw.fund_fee,uw.paymentref,uw.transhash,uw.usernamesentto,uw.usernamesentfrm,uw.confirmtime,uw.ordertime,uw.business_card_tid,COALESCE(uw.systempaidwith,'') AS systempaidwith,COALESCE(uw.addresssentto,'') AS addresssentto,uw.iscrypto,uw.bill_cashback,COALESCE(uw.vc_transname,'') AS vc_transname,uw.bill_meter_no,uw.biz_order_status,uw.bill_main_prodtid,uw.deleted_card,uw.isexchange,uw.send_fee,uw.swapto,uw.bankaccsentto,uw.currencytag,uw.virtual_card_trans,uw.wallettrackid,COALESCE(uw.btcvalue,0) AS btcvalue,uw.bill_product_no,uw.amttopay,COALESCE(uw.cointrackid,'') AS cointrackid,uw.transtype,uw.status,COALESCE(uw.confirmation,0) AS confirmation,uw.peerstack_agent, COALESCE(pm.accountno, '') AS peerstack_accountno, COALESCE(pm.bankname, '') AS peerstack_bankname, CONCAT(COALESCE(pm.fname, ''),' ',COALESCE(pm.lname, '')) AS peerstack_agentname,cs.name as currency_name,cs.sign as currency_symbol,CONCAT('$base_url',cs.imglink) AS currency_imglink",
                            [
                                [
                                    ['column' => 'uw.userid', 'operator' => '=', 'value' =>  $userID],
                                    ['column' => 'uw.orderid', 'operator' => '=', 'value' => $orderid],
                                    'operator'=>"AND"
                                ]
                            ],
                            [
                                'joins' => [
                                    [
                                        'type' => 'LEFT',
                                        'table' => 'currencysystem cs',
                                        'condition' => 'cs.currencytag =uw.currencytag'
                                    ],
                                    [
                                        'type' => 'LEFT',
                                        'table' => 'peerstackmerchants pm',
                                        'condition' => 'pm.merchant_trackid =uw.peerstack_agent'
                                    ]
                                ],
                                'orderBy' => 'uw.id',
                                'orderDirection' => 'DESC',
                                'limit' => 1,
                                'pageno' => 1,
                            ]
                        );
        
                        foreach($allUserTransactions as &$users){
                            // transactions type we have
                            // deposit ngn, spent ngn, 
                            // fund virtual card, unload virtual card, spent virtual card, refund virtual card,(crpto,ngn)
                            // swap ngn to crypto, crypto to ngn
                            // deposit usd, deposit crypto, send crypto
                            // buy viz card
                            // top up, voucher, ticket
                            
                            // platformtype 1-Paystack 2- monify 3- 1app
                            // systemsendwith like the type of transaction spent on(withdrawal for transtype 1 3 4): 1-Internal Transfer(crypto/base currency), 2-Withdraw to Bank Account(base currency) 3-Swap/Exchange 4-Send crypto out 5- Withdraw With Peerstack, 6-Fund Card, 7- Unload/Delete card, 8- Bill Top up, 9 Charge Back, 10 Voucher Bill, 11 bill ticket, 12 withdrawl from business card sale, 13 create virtualcard, 14 Spent from virtual card
                            // systempaidwith like the type of transaction paid on
                            // transtype 1= send 2 = receive 3->swap 4->exchnage
                            // statu  0- pending, 1- successful, 2- in wallet, 3- Cancled , 4- Scam flagged 5- Awaiting Approval 6 Reversed
                            // column we use systemsendwith,userid,swapto,virtual_card_trans,iscrypto,transtype,status,confirmation,peerstack_agent,cointrackid,btcvalue,amttopay,wallettrackid,bankaccsentto,send_fee,isexchange,deleted_card,bill_main_prodtid,bill_product_no,bill_meter_no,bill_cashback,biz_order_status,vc_transname,addresssentto,theusdval,ordertime,
                            
        
                            // systemtouseid/systempaidwith 1- Paystack, 2- Monify, 3- 1app, 4- Crypto Biz 5- peertstack
                            // OLD WAY
                            //2 send with peerstack transtype =1  systemsendwith=5 ___________
                            //4 send to username transtype =1  systemsendwith=1 iscrypto=0 NGN to NGN/ USD to USD
                            //5 send to username transtype =1  systemsendwith=1  iscrypto=1 Crypto to Crypto
                            //1 send to bankacc transtype =1  systemsendwith=2____-
                            //6 swap to NGN transtype =3  systemsendwith=3  iscrypto=1 isexchange=0 crypto to NGN
                            //3 swap to NGN transtype =4  systemsendwith=3  iscrypto=1 isexchange=1  Exchnage crypto to NGN ________
        
                            // 5 deposit with Swap transtype =2  systempaidwith=4  iscrypto=0  crypto to NGN
                            // 2 deposit with peerstack transtype =2  systempaidwith=5_____
                            // 3 deposit with banktransfer transtype =2  systempaidwith=2,3
                            // 1 deposit with direct funding transtype =2  systempaidwith=1
                            // 4 deposit with crypto transtype =2  systempaidwith=4 iscrypto=1 
        
                            $swaptext='';
                            $swapTotextName='';
                            $userBaseCoinName='NGN';//base currency for the user
                            $transactionCoinsName=$userBaseCoinName;
                            $transbasemainimage='assets/icons/large_naira_icon.png';//Base currency image asset on app
                            $statustextis="Unknow"; 
                            $transtotalSendCoinDeducted=0;
                            $thusdval=0;
                            $transtopdata=$transbottomdata='';
                            $exchangeconfirmed=0;//to determine the right text to show for a processing virtual card trans, a confirmed  exchange transaction(actually its in but not paid), 
                            $transactiontype=$users['transtype']; // transtype 1= send 2 = receive 3->swap 4->exchnage
                            $transactionstatus=$users['status'];
                            $hasbaselink='';
                            $transSystemSendOrSpentWith=$users['systemsendwith'];
                            $transSystemDepositOrReceiveWith=$users['systempaidwith'];
                            $transactionIsaCryptoTrans=$users['iscrypto'];
                            $transactionIsVirtualCardTrans=$users['virtual_card_trans'];
                            $transCryptoValue=$users['btcvalue'];
                            $transCryptoSendFee=$users['send_fee'];
                            $transAmtToPay=$users['amttopay'];
                            $currencyname=$users['currency_name'];
                            if(strtolower($currencyname)=='usd'){
                                $transbasemainimage='assets/icons/large_usd_icon.png';
                            }
                            $currency_symbol= html_entity_decode($users['currency_symbol'], ENT_NOQUOTES, 'UTF-8');
                            $transWalletTrackidUsed=$users['wallettrackid'];
                            $transUserId=$users['userid'];
                            $maintransname="";
                            $transbasemaintransname=$currencyname;
                            $cardlast4="****";
                            $cardcurrency="USD";
                            $users['userbankname']="";
                            $users['useraccno']="";
                            $users['bill_image']='';
                            $users['bill_transname']='';
                            $users['bill_productname']='';
                            $users['bill_paymethod']="$transactionCoinsName wallet";
                            $users['bill_type']='';
                            $users['bill_amt']='';
                            $users['bis_card_name']='';
                            $users['bis_card_color']='';
                            $users['bis_card_desc']='';
                            $users['biz_order_status_text']="";
                            $users['bill_cashback_percent']='0';
                            if($transSystemSendOrSpentWith==3){// is swap
                                $swaptext=$users['swapto'];
                                $inputString=$swaptext;
                                $pattern = '/\bto\b/i';
                                // Use preg_split to split the string by the pattern
                                $parts = preg_split($pattern, $inputString);
                                // Check if there are at least two parts, return the second part trimmed of any leading/trailing whitespace
                                if (count($parts) >= 2) {
                                    $swapTotextName=trim($parts[1]);
                                } 
                            }
                             // checking if exchange transaction is confirmed on blockchain
                             // if user blockchain is confirmed and we have not paid and its an exchange transaction
                            if($transactionstatus==2&&$transactiontype==4 && $users['confirmation']>0){
                                $exchangeconfirmed=1;
                            }else if($transSystemSendOrSpentWith==4||$transSystemSendOrSpentWith==6||$transSystemSendOrSpentWith==13){// if its an external send out
                                $exchangeconfirmed=1;
                            }else if( $transactionIsVirtualCardTrans==1){// if its a virtaul card transac
                                $exchangeconfirmed=1;
                            }
        
                            if($transactionstatus==0){
                                $statustextis="Pending";
                            }else if($transactionstatus==1){
                                if($transactionIsaCryptoTrans==1){
                                    $statustextis="Confirmed";
                                }else{
                                    $statustextis="Successful";
                                }
                            }else if($transactionstatus==2){
                                if($transactionIsaCryptoTrans==1&&$exchangeconfirmed==0){//cryto deposit/exchnage deposit
                                    $statustextis="Incoming" ;
                                }else{
                                    $statustextis="Processing";
                                }
                            }else if($transactionstatus==3){
                                $statustextis="Canceled";
                            }else if($transactionstatus==4){
                                if($transactionIsaCryptoTrans==1){
                                    $statustextis="Scam flagged"; 
                                }else{
                                    $statustextis="Funds Locked";
                                }
                            }else if($transactionstatus==5){
                                $statustextis="Pending";//"Awaiting Approval";
                            }else if($transactionstatus==6){
                                $statustextis="Reversed";
                            }
                            if(!$utility_class_call->input_is_invalid($users['bankaccsentto'])){
                                $seprated= explode("/",$users['bankaccsentto']);
                                if(isset($seprated[0])){
                                    $users['userbankname']= $seprated[0];
                                }
                                if(isset($seprated[1])){
                                    $users['useraccno']= $seprated[1];
                                }
                            }
                            if($transactionIsaCryptoTrans==1){
                                $coinprodtrackidis=$users['cointrackid'];
                                $coinUsedData = $db_call_class->selectRows("coinproducts","priceapiname,livecoinvalue,hashlink,roundto_dp",
                                    [
                                        [
                                            ['column' => 'producttrackid', 'operator' => '=', 'value' =>$coinprodtrackidis]
                                        ]
                                    ],
                                );
                                $coindata=$coinUsedData[0];
                                $transactionCoinsName=$coindata['priceapiname'];
                                $livecoinvalue=$coindata['livecoinvalue'];
                                $hasbaselink=$coindata['hashlink'];
                                $coindecimal=$coindata['roundto_dp'];
                                // if swap to is not crypto(e.g NGN )   the btc valued swapped to is in amttopay
                                //  ($swapTotextName=="NGN" ) add other currency not crypto here
                                if(($swapTotextName=="NGN" )&& $transactiontype==3){
                                    $transCryptoValue=$transAmtToPay;
                                }
                                $walletbal=number_format((float) $transCryptoValue, $coindecimal, '.', ''); 
                                $getlivevalu=$livecoinvalue; 
                                if ($getlivevalu!=0) {
                                    $thusdval=$walletbal*$getlivevalu;
                                    $thusdval=number_format((float)$thusdval, 2, '.', '');
                                }
                                // if transtype is not exchnage chnage amount to pay
                                if($transactiontype!=4){
                                    $transAmtToPay=$thusdval;
                                }
                                $transCryptoValue= $transCryptoValue+0;
                                $transCryptoSendFee=$transCryptoSendFee+0;
                                if($transSystemSendOrSpentWith==4){
                                    $transtotalSendCoinDeducted= number_format((float)( $transCryptoValue+$transCryptoSendFee), $coindecimal, '.', '');
                                }
                                $transCryptoValue=number_format((float)$transCryptoValue); 
                            }else{
                                $transCryptoValue=number_format((float)$transCryptoValue); 
                            }
                            $transAmtToPay=number_format((float)$transAmtToPay, 2, '.', '');
        
                            if($transactionIsVirtualCardTrans==1){
                                $cardtidis= $transWalletTrackidUsed;
                                $userid=$transUserId;
                                $getVirtualCardData = $db_call_class->selectRows(
                                    "vc_customer_card uvc", "uvc.last4,uvc.vc_type_tid,vct.currency",
                                    [
                                        [
                                            ['column' => 'uvc.user_id', 'operator' => '=', 'value' => $userid],
                                            ['column' => 'uvc.trackid', 'operator' => '=', 'value' => $cardtidis],
                                        ]
                                    ],
                                    [
                                        'joins' => [
                                            [
                                                'type' => 'LEFT',
                                                'table' => 'vc_type vct',
                                                'condition' => 'uvc.vc_type_tid =vct.trackid'
                                            ]
                                        ],
                                    ]
                                );
                                $gtCardtransdata=$getVirtualCardData[0];
                                $cardlast4= $gtCardtransdata['last4'];
                                $cardcurrency=$gtCardtransdata['currency'];
                            }
        
                            $vctext="Virtual Card";
                            if($limittext==1){
                                $vctext="VC";
                            }
        
                            // withdrawal
                            // transtype 1= send 2 = receive 3->swap 4->exchnage
                            // systemsendwith like the type of transaction spent on: 1-Internal Transfer(crypto/base currency), 2-Withdraw to Bank Account(base currency) 3-Swap/Exchange 4-Send crypto out 5- Withdraw With Peerstack, 6-Fund Card, 7- Unload/Delete card, 8- Bill Top up, 9 Charge Back, 10 Voucher Bill, 11 bill ticket, 12 withdrawl from business card sale, 13 create virtualcard, 14 Spent from virtual card
                            // 
                            // systempaidwith 
        
                            if($transactiontype==1||$transactiontype==4||$transactiontype==3){
                                if($transSystemSendOrSpentWith==1){
                                    $currencyis=$currencyname;
                                    if($transactionIsaCryptoTrans==1){
                                        $currencyis='Crypto';
                                    }
                                    $maintransname="Internal Transfer Send($currencyis)";   
                                    $transbasemaintransname="Sent $currencyname";
                                }else  if($transSystemSendOrSpentWith==2){
                                    $maintransname="Withdraw to a bank account";  
                                    $transbasemaintransname="Sent $currencyname";
                                }else  if($transSystemSendOrSpentWith==3){
                                    $transactionCoinsName=$swapTotextName;
                                    $title="Swap";
                                    if($users['isexchange']==1){
                                        $title="Exchanged";
                                    }
                                    $maintransname="$title $swaptext";   
                                    $transbasemaintransname="$title $swaptext";
                                }else  if($transSystemSendOrSpentWith==4){
                                    $cmae=$transactionCoinsName;
                                    $maintransname="External $cmae pay out";   
                                    $transbasemaintransname="Sent $cmae";
                                }else  if($transSystemSendOrSpentWith==5){
                                    $maintransname="Withdraw with peerstack";   
                                    $transbasemaintransname="Sent $currencyname";
                                }else  if($transSystemSendOrSpentWith==6){
                                    $maintransname="Fund $vctext";  
                                    $transbasemaintransname="Spent $currencyname";
                                }else  if($transSystemSendOrSpentWith==7){
                                    $cmae=$userBaseCoinName;
                                    if($transactionIsaCryptoTrans==1){
                                        $cmae=$transactionCoinsName;
                                    }
                                    if($users['deleted_card']==1){
                                        $maintransname="Unload to $cmae wallet"; 
                                        $transbasemaintransname="Delete $vctext [$cardlast4]";
                                    }else  if($users['deleted_card']==2){
                                        $maintransname="Refunded from $vctext to $cmae wallet"; 
                                        $transbasemaintransname="Refund and deactivated [$cardlast4]";
                                    }else{
                                        $maintransname="Unload to $cmae wallet"; 
                                        $transbasemaintransname="Unload $vctext [$cardlast4]";
                                    }
                                }else  if($transSystemSendOrSpentWith==8){
                                    // BILLS TOP UP
                                    $prodname="";
                                    $prodtypetext="";
                                    $prodimage="";
                                    $bill_main_prodtid=$users['bill_main_prodtid'];
        
                                    $billProdData = $db_call_class->selectRows(
                                        "bill_top_up_main_products", "shortname,type,image,name, CASE WHEN type = 1 THEN 'Data' WHEN type = 2 THEN 'Airtime' ELSE ' ' END AS type_status",
                                        [
                                            [
                                                ['column' => 'product_trackid', 'operator' => '=', 'value' => $bill_main_prodtid]
                                            ]
                                        ],
                                    );
                                    if(!$utility_class_call->input_is_invalid($billProdData)){
                                        $billrow =$billProdData[0];
                                        $prodname=$billrow['shortname'];
                                        $prodrealname=$billrow['shortname'];
                                        $thetypeis=$billrow['type'];
                                        $prodimage=$billrow['image'];
                                        $prodtypetext=$billrow['type_status'];
                                    }
                                    $users['bill_type']= $prodtypetext;
                                    $maintransname="Bought $prodname $prodtypetext on ".$users['bill_product_no'];
                                    $transbasemaintransname="Spent $currencyname";
                                    $users['bill_image']=$prodimage;
                                    $users['bill_productname']=$prodrealname;
                                    $users['bill_transname']="$prodname $prodtypetext ".$users['bill_product_no'];
                                    $users['bill_amt']="$currency_symbol".$utility_class_call->remove_pointzero($transAmtToPay);
                                    $users['bill_cashback']="$currency_symbol".$users['bill_cashback'];
                                }else  if($transSystemSendOrSpentWith==9){
                                    $maintransname="Chargeback";  
                                    $transbasemaintransname="$currencyname Chargeback";
                                }else  if($transSystemSendOrSpentWith==10){
                                    // BILLS TOP UP
                                    $prodname="";
                                    $prodtypetext="";
                                    $prodimage="";
                                    $bill_main_prodtid=$users['bill_main_prodtid'];
                                    $billProdData = $db_call_class->selectRows(
                                        "bill_voucher_main_prod", "name,imglink",
                                        [
                                            [
                                                ['column' => 'voucher_tid', 'operator' => '=', 'value' => $bill_main_prodtid]
                                            ]
                                        ],
                                    );
                                    if(!$utility_class_call->input_is_invalid($billProdData)){
                                        $billrow =$billProdData[0];
                                        $prodimage=$billrow['imglink'];
                                        $prodtypetext=$billrow['name'];
                                    }
                                    $users['bill_cashback_percent']=strval(($users['bill_cashback']*100)/($transAmtToPay));
                                    $users['bill_type']= $prodtypetext." Voucher";
                                    $users['bill_productname']=$prodname;
                                    $maintransname="Bought $prodname on ".$users['bill_meter_no'];
                                    $transbasemaintransname="Spent $currencyname";
                                    $users['bill_image']=$prodimage;
                                    $users['bill_transname']="$prodname ".$users['bill_meter_no'];
                                    $users['bill_amt']="$currency_symbol".$utility_class_call->remove_pointzero($transAmtToPay);
                                    $users['bill_cashback']="$currency_symbol".$users['bill_cashback'];
                                    
                                }else  if($transSystemSendOrSpentWith==11){
                                    // BILLS TOP UP
                                    $prodname="";
                                    $prodtypetext="";
                                    $prodimage="";
                                    $bill_main_prodtid=$users['bill_main_prodtid'];
                                    // get bills prod
                                    $billProdData = $db_call_class->selectRows(
                                        "bill_ticket_main_prod", "name,imglink",
                                        [
                                            [
                                                ['column' => 'event_tid', 'operator' => '=', 'value' => $bill_main_prodtid]
                                            ]
                                        ],
                                    );
                                    if(!$utility_class_call->input_is_invalid($billProdData)){
                                        $billrow =$billProdData[0];
                                        $prodimage=$billrow['imglink'];
                                        $prodtypetext=$billrow['name'];
                                    }
                                    if($transAmtToPay==0){
                                        $users['bill_cashback_percent']=strval(0);
                                    }else{
                                        $users['bill_cashback_percent']=strval(($users['bill_cashback']*100)/($transAmtToPay));
                                    }
                                    $users['bill_type']= $prodtypetext." Ticket";
                                    $users['bill_productname']=$prodname;
                                    $maintransname="Bought $prodname Ticket";
                                    $transbasemaintransname="Spent $currencyname";
                                    $users['bill_image']=$prodimage;
                                    $users['bill_transname']="$prodname Ticket";
                                    $users['bill_amt']="$currency_symbol".$utility_class_call->remove_pointzero($transAmtToPay);
                                    $users['bill_cashback']="$currency_symbol".$users['bill_cashback'];
                                    
                                }else  if($transSystemSendOrSpentWith==12){
                                   
                                    $business_card_tid=$users['business_card_tid'];
        
                                    $businessData = $db_call_class->selectRows(
                                        "business_card_details bcd",
                                        "bcd.card_type_tid,bct.name,bct.card_type,bct.card_desc",
                                        [
                                            [
                                                ['column' => 'bcd.trackid', 'operator' => '=', 'value' => $business_card_tid],
                                                ['column' => 'bcd.user_id', 'operator' => '=', 'value' =>  $userID],
                                            ]
                                        ],
                                        [
                                            'joins' => [
                                                [
                                                    'type' => 'LEFT',
                                                    'table' => 'business_card_types bct',
                                                    'condition' => 'bct.trackid =bcd.card_type_tid'
                                                ]
                                            ],
                                        ]
                                    );
        
                                    if(!$utility_class_call->input_is_invalid($businessData)){
                                        $usercarddata=$businessData[0];
                                        $usercard_name= $usercarddata['name'];
                                        $usercard_type=$usercarddata['card_type'];
                                        $usercard_desce=$usercarddata['card_desc'];
                                        $users['bis_card_name']= $usercard_name;
                                        $users['bis_card_color']=$usercard_type;
                                        $users['bis_card_desc']= $usercard_desce;
                                        $maintransname="Ordered $usercard_name Business Card";  
                                        $transbasemaintransname="Ordered Business Card";
                                    }else{
                                        $users['bis_card_name']= "";
                                        $users['bis_card_color']="";
                                        $users['bis_card_desc']= "";
                                        $maintransname="Ordered Business Card";  
                                        $transbasemaintransname="Ordered Business Card";
                                    }
                                    if($users['biz_order_status']==1){
                                        $users['biz_order_status_text']="Delivered";
                                    }else if($users['biz_order_status']==2){
                                        $users['biz_order_status_text']="Processing";
                                    }else if($users['biz_order_status']==3){
                                        $users['biz_order_status_text']="Not Paid";
                                    }
                                }else  if($transSystemSendOrSpentWith==13){
                                    $maintransname="Create $vctext";   
                                    $transbasemaintransname="Spent $currencyname";
                                }else  if($transactionIsVirtualCardTrans==1 && $transSystemSendOrSpentWith==14){
                                    $maintransname=$users['vc_transname'];
                                    if(strlen($maintransname)>20){
                                        $transbasemaintransname=substr($users['vc_transname'],0,20)."...";
                                    }else{
                                       $transbasemaintransname=$users['vc_transname'];
                                    }
                                }
                            } else if($transactiontype==2){
                                // transtype 1= send 2 = receive 3->swap 4->exchnage
                                // deposit 
                                // systempaidwith 1 Deposit with Paystack, 2/3 deposit with Bank account transfer like providus, 9payment etc, 4 Desposit via sending to crypto address,
                                // 5 deposit via peerstack , 6 Unload virtual card deposit, 7 Refund from virtual card spending, 8 Deposit via swap, 9 Depsoit via chargeback, 10 fund card vc,11 create virtual card,12 Internal transfer, 13- Unload/Delete card,
        
                                if($transSystemDepositOrReceiveWith==1){
                                    $maintransname="Deposit via direct funding(PS)"; 
                                    $transbasemaintransname="Received $currencyname";
                                }else  if($transSystemDepositOrReceiveWith==2 || $transSystemDepositOrReceiveWith==3){
                                    $maintransname="Deposit via bank transfer";   
                                    $transbasemaintransname="Received $currencyname";
                                }else  if($transSystemDepositOrReceiveWith==4){
                                    if($transactionIsaCryptoTrans!=1){
                                        $maintransname="Deposit $currencyname";
                                    }else{
                                        $cmae=$transactionCoinsName;
                                        $maintransname="Deposit $cmae";
                                    }
                                    $transbasemaintransname="Received $currencyname";
                                }else  if($transSystemDepositOrReceiveWith==5){
                                    $maintransname="Deposit with peerstack";   
                                    $transbasemaintransname="Received $currencyname";
                                }else  if($transSystemDepositOrReceiveWith==6){
                                    $cmae=$currencyname;
                                    if($transactionIsaCryptoTrans==1){
                                        $cmae=$transactionCoinsName;
                                    }
                                    $maintransname="Deposit from $vctext to $cmae";   
                                    $transbasemaintransname="Received $cmae";
                                }else  if($transSystemDepositOrReceiveWith==7){
                                    $maintransname="Refund to $vctext [$cardlast4]";  
                                        if(strlen($maintransname)>20){
                                            $transbasemaintransname=substr($users['vc_transname'],0,20)."...";
                                        }else{
                                            $transbasemaintransname=$users['vc_transname'];
                                        }
                                }else  if($transSystemDepositOrReceiveWith==8){
                                    $maintransname="Deposit via swap $swaptext"; 
                                    $transbasemaintransname="Received $currencyname";
                                }else  if($transSystemDepositOrReceiveWith==9){
                                    $maintransname="Cashback bonus"; 
                                    $transbasemaintransname="Received $currencyname";
                                }else  if($transSystemDepositOrReceiveWith==10){
                                        $maintransname="Fund $vctext [$cardlast4]";  
                                        $transbasemaintransname="Fund $vctext [$cardlast4]";
                                } else if($transSystemDepositOrReceiveWith==11){
                                        $maintransname="Create $vctext [$cardlast4]"; 
                                        $transbasemaintransname="Create $vctext [$cardlast4]";
                                } else  if($transSystemDepositOrReceiveWith=12){
                                    $title=$currencyname;
                                    if($transactionIsaCryptoTrans==1){
                                        $title=$transactionCoinsName;
                                    }
                                    $maintransname="Internal transfer receive($title)";  
                                    $transbasemaintransname="Received $title";
                                } else  if($transSystemSendOrSpentWith==13 ){
                                    $cmae=$currencyname;
                                    if($transactionIsaCryptoTrans==1){
                                        $cmae=$transactionCoinsName;
                                    }
                                    if($users['deleted_card']==1){
                                        $maintransname="Unload from virtual card"; 
                                        $transbasemaintransname="Delete $vctext";
                                    }else  if($users['deleted_card']==2){
                                        $maintransname="Refunded from $vctext to $cmae wallet"; 
                                        $transbasemaintransname="Refund and Deactivated  ";
                                    }else{
                                        $maintransname="Unload from virtual card"; 
                                        $transbasemaintransname="Unload $vctext";
                                    }
                                }
                            }
                             // if swap to is  crypto(e.g USDT )   the btc valued swapped to is in amttopay
                            //  ($swapTotextName=="NGN" ) add other currency not crypto here
                            if(($swapTotextName=="NGN")&&$transactiontype!=3){
                                $transAmtToPay= $users['btcvalue'];
                            }
                            $transmainamt_value=$utility_class_call->formatLargeNumber($transAmtToPay);
                            // for swap
                         
                            $transmainamt_valueusd=$users['theusdval'];
                            if($showvoucher==false){
                                $users['bill_voucher_code']="";
                            }
        
                            $transmainamt="";
                            $transcryptomainamt="";
                            // exchange or deposit
                            if($transactiontype==2||$transactiontype==4){
                                if($transactionIsVirtualCardTrans==1){
                                    if($transSystemSendOrSpentWith==6||$transSystemSendOrSpentWith==13){
                                        $transcryptomainamt="+ $$transCryptoValue $transactionCoinsName";
                                        if($currencyname=="NGN"){
                                              $transmainamt="+ $transmainamt_valueusd USD";
                                        }else{
                                             $transmainamt="+ $transmainamt_value $currencyname";
                                        }
                                    }else{
                                        $transmainamt="+ $transmainamt_valueusd USD";
                                    }
                                }else{
                                    
                                    $transcryptomainamt="+ $$transCryptoValue $transactionCoinsName";
                                   
                                    $transmainamt="+ $transmainamt_value $currencyname";
                                    
                                    
                                }
                            }else{
                                if($transactionIsVirtualCardTrans==1){
                                    if($transSystemSendOrSpentWith==6||$transSystemSendOrSpentWith==13){
                                        $transcryptomainamt="- $$transCryptoValue $transactionCoinsName";
                                    }else{
                                        $transmainamt="- $transmainamt_valueusd USD";
                                    }
                                }else{
                                    $transcryptomainamt="- $$transCryptoValue $transactionCoinsName";
                                    $transmainamt="- $transmainamt_value $currencyname";
                                }
                            }
           
                            if (strlen($transbasemaintransname) > 16) {
                                $transbasemaintransname = substr($transbasemaintransname, 0, 16) . '...';
                            } 
                            if($transactionIsaCryptoTrans==1||($transactionIsVirtualCardTrans==1 &&($transSystemSendOrSpentWith==6||$transSystemSendOrSpentWith==13))){
                                $transtopdata=$transcryptomainamt;
                            }else{
                                $transtopdata=$transmainamt;
                            }
                            if($transactionIsaCryptoTrans==1){
                                $transbottomdata=$transmainamt;
                                if ($transactionIsVirtualCardTrans==1 &&($transSystemSendOrSpentWith==6|| $transSystemSendOrSpentWith==13))  {
                                    $transbottomdata= $transcryptomainamt;
                                }
                            }
                         
                            $users['overview_trans_amt']=$transmainamt;    
                            $users['overview_trans_crypto_amt']=$transcryptomainamt;  
                            $users['maintransname']=$maintransname;
                            $users['overview_transstatus']=$statustextis;    
                            $users['swaptoname']=$swaptext;
                            $users['hashtop']=$hasbaselink;
                            $users['coinname']=$transactionCoinsName;
                            $users['totalsenddeducted']=$transtotalSendCoinDeducted+0;
                            $users['overview_transstatus_no']=$transactionstatus;   
                            $users['overview_transname']=$transbasemaintransname;
                            $users['overview_topdata']=$transtopdata;  
                            $users['overview_bottomdata']=$transbottomdata;   
                            $users['overview_cardcurrency']=$cardcurrency;
                            $users['overview_transorder_time']=$users['ordertime'];   
                            $users['overview_transconfirm_time']=$users['confirmtime'];   
                            $users['overview_transimg_asset']=$transbasemainimage;  
                            $users['overview_trans_img']=$users['currency_imglink'];
                            // unset($users['systemsendwith']);
                            unset($users['virtual_card_trans']);
                            unset($users['bankaccsentto']);
                            unset($users['userid']);
                            // unset($users['systempaidwith']);
                            $row['transdata']=$users;
                        }

                        if(strlen(trim($transbasemaintransname))>2){
                            $row['notificationtitle']= $transbasemaintransname;
                        }
                    }
                }
            }
            
            $total_pages = ceil($total_numRow / $per_page);
            $maindata['records']=$getAllData;
            $maindata['pageno']=$page_no;
            $maindata['perpage']=$per_page;
            $maindata['totalpage']=$total_pages;
            $api_status_code_class_call->respondOK($maindata,$text);
        }
    } catch (\Exception $e) {
        $api_status_code_class_call->respondInternalError($utility_class_call->get_details_from_exception($e));
    }
} else {
    $api_status_code_class_call->respondMethodNotAlowed();
}

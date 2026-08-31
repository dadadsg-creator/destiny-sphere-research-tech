<?PHP
ob_start();
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/InvoiceNumberGenerator.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/classes/Core/Trace.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Admin/controls/PremiumAccaunts/classes/Core/TextFileTraceListener.class.php';

Assert::SetDieOnError(false);

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=Premiumsupport><font size=3><b>HELP</b></font></a></p>';


if ( !isset ( $GLOBALS["common"] ) ) {

    $common = ClassFactory::getInstance ( "DS_Common" );

    if ( !$common->setDbId(0) ) {
        echo local( 'DS_DB_FAILED' )." : ".$common->errormsg;
        exit;
    }
}
$GLOBALS['admin_name'] = $UserManager->GetUserName();           

class EditProfiData {

    var $errormsg;
    var $find_username;
    var $find_surname;
    var $find_email;
    var $show_address;
    var $limit;
    var $mode;
    var $find_id_payment;
    var $find_id_user;
    
    var $since;
    var $prolonged;
    
    private $action = 'index';
    
    private $userID;
        
    function EditProfiData() 
    {
        $this->limit = 10;

        if(!$this->getPostData($_POST))
        {
            echo $this->errormsg;
            return;
        }
        
        switch($this->action)
        {
            case 'index':
                if(!$this->showProfiUsers())
                {
                    echo $this->errormsg;
                }
                break;
        
            default:
                break;
        }
    }

    function getPostData ( $data ) {

        global $common;

        if(sizeof($data) == 0)
        {
            $this->action = 'index';
            return true;    
        }
        
        if(isset($data['find']))
        {
            if(isset($_POST['mode']))
            {
                unset($_POST['mode']);
            }
            
            if(isset($data['show_address']))
            {
                $this->show_address = 1;
            }

            $this->find_username = $data['find_username'];
            $this->find_email = $data['find_email'];
            $this->find_surname = $data['find_surname'];
            $this->find_id_payment = $data['find_id_payment'];
            $this->find_id_user = $data['find_id_user'];
            
            $this->action = 'index';

            return true;
        } 
        
        if(isset($data['edit']))
        {
            if ( isset ( $data['show_address'] ) ) $this->show_address = 1;
            if ( isset ( $data['mode'] ) ) $this->mode = 1;
            if ( isset ( $data['find_username'] ) ) $this->find_username = $data['find_username'];
            if ( isset ( $data['find_surname'] ) ) $this->find_surname = $data['find_surname'];
            if ( isset ( $data['find_id_payment'] ) ) $this->find_id_payment = $data['find_id_payment'];
            if ( isset ( $data['find_id_user'] ) ) $this->find_id_user = $data['find_id_user'];
            if ( isset ( $data['limit'] ) ) $this->limit = $data['limit'];
            
            $this->userID = (int)$data['id_user'];
            
            if($data['id_user'] <= 0)
            {
                $this->errormsg = 'SYSTEM ERROR EditProfiData::wrong $data[\'id_user\']';
                return false;
            }

            $sql = sprintf ( "select username
                    from ds_user
                    where id_user = %d",
                    $data['id_user'] );

            $res = $common->getResultArray ( "username_id_user", $sql, "", "", "" );
            if ( $res === false ) {
                $this->errormsg = "SYSTEM ERROR EditProfiData::getPostData1";
                $this->errormsg .= " !!! ".$sql;
                return false;
            }
                    
            if(isset($data['active'])) 
            {
                if(sizeof($res[0]['username']) === 0)
                {
                    $this->errormsg = 'SYSTEM ERROR EditProfiData::$res[0][\'username\']';
                    echo $this->errormsg;
                }

                if(!$common->setDbId(0)) 
                {
                    echo local( 'DS_DB_FAILED' )." : ".$common->errormsg;
                    exit;
                }
                                
                $sql = sprintf ( "update ds_user  
                        set accountactive=true,
                        activate_mail=1  
                        where id_user = %d",
                        $data['id_user'] );
                
                $res = $common->executeQuery( $sql );
                if ( $res === false ) {
                    $this->errormsg = "SYSTEM ERROR EditProfiData::getPostData100";
                    $this->errormsg .= " !!! ".$sql;
                    return false;
                } 
                else
                     actionLog("Activate premium account. User : ".$data['id_user']);

                $active = "true";
            } else {
                $active = "false";
            }
            
            if(!$common->setDbId(0)) 
            {
                echo local('DS_DB_FAILED')." : ".$common->errormsg;
                exit;
            }
            
            $sql = "
                    UPDATE
                        ds_userprofidata
                    SET
                        active = $active
                    WHERE
                        id_user = ".$data['id_user']."
                    ";
            $res = $common->executeQuery($sql);
            if($res === false)
            {
                $this->errormsg = "SYSTEM ERROR EditProfiData::UpdateActive";
                $this->errormsg .= " !!! ".$sql;
                return false;
            } 
            
            
            if (!empty($data['newpass'])) {  
                $sql = sprintf ( "update ds_userprofidata
                        set password = '%s'
                        where id_user = %d",
                        md5($data['newpass']), $data['id_user'] );
                
                $res = $common->executeQuery ( $sql );
                if ( $res === false ) {
                    $this->errormsg = "SYSTEM ERROR EditProfiData::getPostData4.1";
                    $this->errormsg .= " !!! ".$sql;
                    return false;
                } 
                else 
                actionLog("Change Premium password for user_id : ".$data['id_user']." DbId : 0");
            }

            if ( $data['new_premium_email'] != $data['old_premium_email'] ) {

                $sql = "update ds_userprofidata
                            set email = '".$data['new_premium_email']."'
                            where id_user = ".$data['id_user']."";

                $res = $common->executeQuery ( $sql );
                if ( $res === false ) {
                    $this->errormsg = "SYSTEM ERROR EditProfiData::getPostData4";
                    $this->errormsg .= " !!! ".$sql;
                    return false;
                } else actionLog("Change PREMIUM Email for user_id : ".$data['id_user']." DbId 0");
            }                               

            if ( $data['trial_profi'] != "" ) {
                
                if ( $data['trial_profi'] == 'trial' ) {
                    
                    $sql = sprintf ( "update ds_userprofidata
                            set trial = true,
                            profi = false
                            where id_user = %d",
                            $data['id_user'] );
                    
                    $res = $common->executeQuery ( $sql );
                    if ( $res === false ) {
                        $this->errormsg = "SYSTEM ERROR EditProfiData::getPostData4";
                        $this->errormsg .= " !!! ".$sql;
                        return false;
                    } else actionLog("Change TRIAL mode for user_id : ".$data['id_user']." DbId : 0");

                } 
                else if ( $data['trial_profi'] == 'profi') 
                {                               
                    $sql = sprintf ( "update ds_userprofidata
                            set trial = false,
                            profi = true
                            where id_user = %d",
                            $data['id_user'] );

                    $res = $common->executeQuery($sql);
                    if($res === false) 
                    {
                        echo "<br><br><br>".pg_last_error();
                        $this->errormsg = "SYSTEM ERROR EditProfiData::getPostData5";
                        $this->errormsg .= " !!! ".$sql;
                        return false;
                    } 
                    else 
                    actionLog("Change PROFI mode for user_id : ".$data['id_user']." DbId : 0");
                }               
            }                   
            
            if(!$this->showProfiUsers()) 
            {
                return false;
            }
        }
    }
    
    function showPagePanel($params,$count)
    {               
        $page_numbers = round(($count/$this->limit),0);
        $res_str = '';
        for($i=1;$i<=$page_numbers;$i++)
        {
            
            $res_str .= "<a class=sitemapa href='index.php?offset=".
                        (($i-1)*$this->limit)."".$params."'>".$i."</a> &nbsp ";         
        }
        return $res_str;
    }

    function showProfiUsers () {

        global $common;

        echo "<form action='getReport.php' method='POST' target='_blank'>";
        echo "<table cellpadding=2 cellspacing=0><tr><td bgcolor='444E6C' class=maintxt>";
        echo "<strong>".local( 'DS_USER_DATE_START' )." : </strong>&nbsp;<input type=text name=sDate size=20 value='".date('Y-m-d 00:00:00',mktime(0, 0, 0, date('m')  , date('d')-1, date('Y')))."'>&nbsp;&nbsp;";
        echo "<strong>".local( 'DS_USER_DATE_END' )." : </strong>&nbsp;<input type=text name=eDate size=20 value='".date('Y-m-d H:i:s')."'>&nbsp;&nbsp;</td></tr><tr><td bgcolor='#444E6C'>";
        echo "<input type=submit class=Submit value='".local( 'DS_ACTION_CREATE_REPORT' )."'><hr size=1>";
        echo "</td></tr><tr><td bgcolor='444E6C' class=maintxt>";
        echo "<a class=sitemapa href='../ds_order_report/' target='_blank'>".local( 'DS_EDITPROFY_CREATE_REPORT_PAYMENTS' )."</a><hr size=1>";
        echo "</td></tr></table>";
        echo "</form>";

        
        if ( isset ( $_GET['offset'] ) ) $offset = $_GET['offset']; else $offset = 0;
        

        if ( isset ( $_GET['find_username'] ) ) $this->find_username = $_GET['find_username'];
        if ( isset ( $_GET['find_email'] ) ) $this->find_email = $_GET['find_email'];
        if ( isset ( $_GET['find_surname'] ) ) $this->find_surname = $_GET['find_surname'];
        if ( isset ( $_GET['find_id_payment'] ) ) $this->find_id_payment = $_GET['find_id_payment'];
        if ( isset ( $_GET['find_id_user'] ) ) $this->find_id_user = $_GET['find_id_user'];
        if ( isset ( $_GET['address'] ) ) $this->show_address = 1;
        if ( $_REQUEST['compliancesearch'] == 'FULL' ) $this->compliancesearch = 1;
        
        
        $str = '';
        $gen = new InvoiceNumberGenerator();


        if ( $this->find_email != '' ) {if ($this->compliancesearch == 1) {$str .= " and upd.email = '$this->find_email'";} else {$str .= " and upd.email ilike '%$this->find_email%'";}}
        if ( $this->find_username != '' ) {if ($this->compliancesearch == 1) {$str .= " and u.username = '$this->find_username'";} else {$str .= " and u.username ilike '%$this->find_username%'";}}
        if ( $this->find_surname != '' ) $str = " and upd.surname ilike '%$this->find_surname%'";
        if ( $this->find_id_user != '' ) $str = " and upd.id_user = $this->find_id_user";
        if ( $this->find_id_payment != '' ) 
        {
            $str = ' and exists ( select id_payment from ds_payment
                     where id_user = upd.id_user and id_payment ='.
                     $gen->getPaymentIdByCode($this->find_id_payment) .')';
        }
        
        if ( isset ( $_GET['mode'] ) ) {

            $this->mode = $_GET['mode'];

        } else if ( isset ( $_POST['mode'] ) ) {
        
            $this->mode = $_POST['mode'];

        }


        if ( isset ( $_GET['sort'] ) ) {
            
            if ( $_GET['sort'] == 'id' ) $sort = 'upd.id_user';
            if ( $_GET['sort'] == 'nick' ) $sort = 'upper(u.username)';
            if ( $_GET['sort'] == 'surname' ) $sort = 'upper(upd.surname)';
            if ( $_GET['sort'] == 'email' ) $sort = 'upper(upd.email)';
            if ( $_GET['sort'] == 'canceldate' ) $sort = 'canceldate';
            if ( $_GET['sort'] == 'createdate' ) $sort = 'upd.creationdate';
            if ( $_GET['sort'] == 'startdate' ) $sort = 'upd.startdate';
            if ( $_GET['sort'] == 'enddate' ) $sort = 'upd.enddate';
            
        } else {
            
            $sort = 'upper(u.username)';
        }

        if ( $this->mode != "" ) {
                
            if ( $this->mode == 1 ) {

                $sql = sprintf ( "select upd.*,                     
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and active = false
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and active = false";
            
            } else if ( $this->mode == 2 ) {

                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and exists
                        (select id_payment
                        from ds_payment
                        where id_user = u.id_user
                        and verified = false)
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and exists
                        (select id_payment
                        from ds_payment
                        where id_user = u.id_user
                        and verified = false)";
            
            } else if ( $this->mode == 3 ) {
            


                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and date_trunc ( 'day', creationdate ) = date_trunc ( 'day', now() )
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and date_trunc ( 'day', creationdate ) = date_trunc ( 'day', now() )";
            
            } else if ( $this->mode == 4 ) {
            
                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.trial = true
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.trial = true";

            } else if ( $this->mode == 5 ) {

                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.cancelled = true
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.cancelled = true";

            } else if ( $this->mode == 9 ) {

                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.cancelled = true
                        and upd.profi = true
                        and upd.active = true
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.cancelled = true
                        and upd.profi = true
                        and upd.active = true";
            
            } else if ( $this->mode == 6 ) {
            
                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.profi = true
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.profi = true";

            } else if ( $this->mode == 7 ) { // valid profi users
            
                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.profi = true
                        and upd.active = true
                        and upd.enddate > now()
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.profi = true
                        and upd.active = true
                        and upd.enddate > now()";

            } else if ( $this->mode == 8 ) { // valid trials users
            
                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.trial = true
                        and upd.active = true
                        and upd.enddate > now()
                        order by %s
                        limit %d offset %d",
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        and upd.trial = true
                        and upd.active = true
                        and upd.enddate > now()";

            } else {

                $sql = sprintf ( "select upd.*,
                        coalesce ( now(), startdate ) as startdate2,
                        u.username
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user
                        %s
                        order by %s
                        limit %d offset %d",
                        $str,
                        $sort,
                        $this->limit,
                        $offset );
                        
                $sql2 = "select count(*)
                        from ds_userprofidata upd,
                        ds_user u
                        where upd.id_user = u.id_user";

            }

        } 
        else {
            
            $sql = sprintf ( "select upd.*,
                    coalesce ( now(), startdate ) as startdate2,
                    u.username
                    from ds_userprofidata upd,
                    ds_user u
                    where upd.id_user = u.id_user
                    %s
                    order by %s
                    limit %d offset %d",
                    $str,
                    $sort,
                    $this->limit,
                    $offset );
                    
            $sql2 = "select count(*)
                    from ds_userprofidata upd,
                    ds_user u
                    where 
                        upd.id_user = u.id_user ".$str;

        }
        
        
        if(isset($_REQUEST['search']))
        {
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            if ( $res === false ) {

                $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers1";
                $this->errormsg .= " !!! ".$sql."|".pg_last_error();
                return false;
                }
                
            $res20 = $common->getResultArray ( "", $sql2, "", "", "" );
            if ( $res20 === false ) {

                $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers2";
                $this->errormsg .= " !!! ".$sql."|".pg_last_error();
                return false;
                }
        }
        
        if(isset($_GET['show_as']) &&
            strcmp($_GET['show_as'], 'csv') == 0)
            {
                $this->generateCSVResult($res);
            }
            else 
            {
                $this->showUsers($offset, $res, $res20[0]['count']);
            }
        
    }
    
    function generateCSVResult($res)
    {
        ob_clean();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users.csv"');
        print ("id_user;username;email;profi;trial;creationdate;startdate;enddate;active;firstname;surname;gender;birthdate \n");
        foreach ($res as $v)
            print $v['id_user'].';'.
                    $v['username'].';'.
                    $v['email'].';'.
                    $v['profi'].';'.
                    $v['trial'].';'.
                    $v['creationdate'].';'.
                    $v['startdate'].';'.
                    $v['enddate'].';'.
                    $v['active'].';'.
                    $v['firstname'].';'.
                    $v['surname'].';'.
                    $v['gender'].';'.
                    $v['birthdate'].
                    " \n";
        die();
    }
    
    function showUsers($offset, $res, $count)
    {
        global $common;
//".local( 'DS_USER_USERNAME' )."       
        echo "<table>
                    <tr>
                    <td>
                    <TABLE border='0' cellpadding='2' cellspacing='0'>
                    <tr>
                        <form action='index.php' method='post'>
                            <input type='hidden' name='search' value='1'>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>".local( 'DS_USER_SURNAME' )." : </strong>
                        </td>
                        <td class=maintxt bgcolor='444E6C'>
                            <input type='text' name='find_surname' size=20 value='".$this->find_surname."'>
                            <input type='submit' class=Submit name='find' value = '".local( 'DS_ACTION_SEARCH' )."'>
                        </td>
                    </tr>
                    <tr>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>Username : </strong>
                        </td>
                        <td class=maintxt bgcolor='444E6C'>
                            <input type='text' name='find_username' size=20 value='".$this->find_username."'>
                            <input type='submit' class=Submit name='find' value = '".local( 'DS_ACTION_SEARCH' )."'>
                        </td>
                    </tr>
                    <tr>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>".local( 'DS_USER_EMAIL' )." : </strong>
                        </td>
                        <td class=maintxt bgcolor='444E6C'>
                            <input type='text' name='find_email' size=20 value='".$this->find_email."'>
                            <input type='submit' class=Submit name='find' value = '".local( 'DS_ACTION_SEARCH' )."'>
                        </td>
                    </tr>
                    <tr>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>".local( 'DS_USER_PAYMENT_ID' )." : </strong>
                        </td>
                        <td class=maintxt bgcolor='444E6C'>
                            <input type='text' name='find_id_payment' size=20 value='".$this->find_id_payment."'>
                            <input type='submit' class=Submit name='find' value = '".local( 'DS_ACTION_SEARCH' )."'>
                        </td>
                    </tr>
                    <tr>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>".local( 'DS_USER_UID' )." : </strong>
                        </td>
                        <td class=maintxt bgcolor='444E6C'>
                            <input type='text' name='find_id_user' size=20 value='".$this->find_id_user."'>
                            <input type='submit' class=Submit name='find' value = '".local( 'DS_ACTION_SEARCH' )."'>
                        </td>
                    </tr>
                    <tr>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>".local( 'DS_ACTION_SHOW_ADDRESS' )." : </strong>
                            <input type=checkbox class=check name=show_address ".( $this->show_address == 1 ? "checked" : "" ).">
                        </td>
                    </tr>
                    <tr>
                        <td class=maintxt bgcolor='444E6C'>
                            <strong>".local( 'DS_COMPLIANCE_SEARCH' )." : </strong>
                            <input type=checkbox ".$compiliance_search_check." class=check name=compliancesearch value=FULL ".( $this->compliancesearch == 1 ? "checked" : "" ).">
                        </td>
                    </tr>
                
                </table>
            </td>
            <td width=50>&nbsp;</td><td valign='top'>";
        
        empty($this->show_address) ?
            $showAdressLink = "":
            $showAdressLink = "&address=1";             
            
        echo "<a class=sitemapa href='?mode=1&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_ALL_INACTIVE' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=1&search=1&show_as=csv".$showAdressLink."'>(csv)</a><br>";
        
        echo "<a class=sitemapa href='?mode=2&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_ALL_UNVERIFIED' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=2&search=1&show_as=csv".$showAdressLink."'>(csv)</a><br>";
        
        echo "<a class=sitemapa href='?mode=3&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_REGISTERED_TODAY' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=3&search=1&show_as=csv".$showAdressLink."'>(csv)</a><br>";
        
        echo "<a class=sitemapa href='?mode=4&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_TRIAL' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=4&search=1&show_as=csv".$showAdressLink."'>(csv)</a><br>";
        
        echo "<a class=sitemapa href='?mode=5&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_CANCELED' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=5&search=1&show_as=csv".$showAdressLink."'>(csv) </a><br>";
                
        echo "<a class=sitemapa href='?mode=9&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_CANCELED_PROFI' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=9&search=1&show_as=csv".$showAdressLink."'>(csv)</a><br>";
                
        echo "<a class=sitemapa href='?mode=6&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_PROFI' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=6&search=1&show_as=csv".$showAdressLink."'>(csv)</a><br>";
        
        echo "<a class=sitemapa href='?mode=7&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_VALID_PROFI' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=7&search=1&show_as=csv".$showAdressLink."'> (csv) </a><br>";
        
        echo "<a class=sitemapa href='?mode=8&search=1".$showAdressLink."'><b>".local( 'DS_EDITPROFY_SHOW_VALID_TRIALS' )."<b></a>&nbsp;";
        echo "<a class=sitemapa href='?mode=8&search=1&show_as=csv".$showAdressLink."'> (csv) </a>
        </td>";
    
        echo "<td width=50>&nbsp;</td><td valign='top' class=maintxt><b>";
        
        $sql = sprintf ( "select count(*) as profi
                from ds_userprofidata
                where profi = true" );
        
        $res2 = $common->getResultArray ( "profi_amount", $sql, "", "", "" );
        if ( !$res2 ) {
            $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers2";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }
        
        echo local( 'DS_USER_PROFY' )." : ".$res2[0]['profi']."<br>";

        $sql = sprintf ( "select count(*) as trial
                from ds_userprofidata
                where trial = true" );
        
        $res2 = $common->getResultArray ( "trial_amount", $sql, "", "", "" );
        if ( !$res2 ) {
            $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers3";
            $this->errormsg .= " !!! ".$sql;
            return false;

        }
        
        echo local( 'DS_USER_TRIAL' )." : ".$res2[0]['trial']."<br>";

        $sql = sprintf ( "select count(*) as cancelled
                from ds_userprofidata
                where cancelled = true and profi = true and active = true" );
        
        $res2 = $common->getResultArray ( "cancelled_amount", $sql, "", "", "" );
        if ( !$res2 ) {
            $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers4";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }
        
        echo local( 'DS_USER_CANCELED' )." : ".$res2[0]['cancelled']."<br>";
        
        $sql = sprintf ( "select count(upd.id_user) as valid_profi
                from ds_userprofidata upd,
                ds_user u
                where upd.id_user = u.id_user
                and upd.profi = true
                and upd.active = true
                and upd.enddate > now()" );

        $res2 = $common->getResultArray ( "valid_profi_amount", $sql, "", "", "" );
        if ( !$res2 ) {
            $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers5";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        echo local( 'DS_USER_VALID_PROFI' )." : ".$res2[0]['valid_profi']."<br>";
                
        $sql = sprintf ( "select count(upd.id_user) as valid_trials
                from ds_userprofidata upd,
                ds_user u
                where upd.id_user = u.id_user
                and upd.trial = true
                and upd.active = true
                and upd.enddate > now()" );
        
        $res2 = $common->getResultArray ( "valid_trials_amount", $sql, "", "", "" );
        if ( !$res2 ) {
            $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers6";
            $this->errormsg .= " !!! ".$sql;
            return false;
        }

        echo local( 'DS_USER_VALID_TRIALS' )." : ".$res2[0]['valid_trials']."<br>";

        echo "</b></td>";

        echo "</tr></table></form>";

        echo "<br>";
        
        $linkAddOn = '';
        if ( $this->show_address != '' )    $linkAddOn .= "&address=1"; 
        if ( $this->mode != "" )            $linkAddOn .= "&mode=$this->mode";
        if ( $this->find_username != '' )   $linkAddOn .= "&find_username=$this->find_username";
        if ( $this->find_surname != '' )    $linkAddOn .= "&find_surname=$this->find_surname";
        if ( $this->find_id_payment != '' ) $linkAddOn .= "&find_id_payment=$this->find_id_payment";
        if ( $this->find_id_user != '' )    $linkAddOn .= "&find_id_user=$this->find_id_user";
        
        
            echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' bgcolor='#222233'><tr><td>
                <TABLE width='100%' border='0' cellpadding='3' cellspacing='1' align='center'>";
            echo "<tr>
            <td class=th_2 nowrap align='center'><a href='?sort=id&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_UID' )."</b></a></td>
            <td class=th_2 nowrap align='center'><a href='?sort=nick&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_USERNAME' )."</b></a></td>
            <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_FIRSTNAME' )."</b></td>
            <td class=th_2 nowrap align='center'><a href='?sort=surname&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_SURNAME' )."</b></a></td>
            <td class=th_2 nowrap align='center'><a href='?sort=email&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_EMAIL' )."</b></a></td>";

            if ( $this->show_address == 1 ) {

                echo "
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_GENDER' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_BIRTHDATE' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_STREET' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_POSTCODE' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_CITY' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_COUNTRY' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_STATE' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_PHONE1' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_PHONE2' )."</b></td>
                <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_FAX' )."</b></td>
                </td>";
            }

            echo "
            <td class=th_2 nowrap align='center'><a href='?sort=createdate&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_DATE_CREATION' )."</b></a></td>
            <td class=th_2 nowrap align='center'><a href='?sort=startdate&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_DATE_START' )."/".local( 'DS_USER_DATE_END' )."</b></a></td>
            <!-- td class=th_2 nowrap align='center'><b>".local( 'DS_USER_DATE_DURATION_DAYS' )."</b></td>
            <td class=th_2 nowrap align='center'><a href='?sort=enddate&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_DATE_END' )."</b></a></td -->
            <td class=th_2 nowrap align='center'><a href='?sort=canceldate&search=1".$linkAddOn."&offset=".$offset."' class=maintxt_small><b>".local( 'DS_USER_DATE_CANCEL' )."</b></a></td>";

            echo "
            <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_CANCELED' )."</b></td>
            <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_ACTIVE' )."</b></td>
            <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_TRIAL_OR_PROFI' )."</b></td>
            <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_UNFERIFIED_PAYMENTS' )."?</b></td>
            <td class=th_2 nowrap align='center'><b>".local( 'DS_USER_NEW_PREMIUM_PASSWORD' )."</b></td>
            <td class=th_2 nowrap align='center'>&nbsp;</td>
            <td class=th_2 nowrap align='center'>&nbsp;</td>
            <td class=th_2 nowrap align='center'>&nbsp;</td>
            </tr>
            
            <script language=\"javascript\">
                function openWin(page)
                {
                    newWin = window.open(page,null,\"height=500,width=1000,status=no,toolbar=no,menubar=no,location=no\");
                    newWin.focus();
                }
            </script>
            ";

            if ( $this->mode != '' ) echo "<input type=hidden name=mode value='$this->mode'>";

            if(sizeof($_GET) !=0 || sizeof($_POST) !=0)
            {
                $params = '&search=1';  
                if($this->find_username != '') $params .= "&find_username=".
                                                        $this->find_username;
                if($this->find_surname != '') $params .= "&find_surname=".
                                                        $this->find_surname;
                if($this->find_id_payment != '') $params .= "&find_id_payment=".
                                                        $this->find_id_payment;
                if($this->find_id_user != '') $params .= "&find_id_user=".
                                                        $this->find_id_user;
                if($this->find_email != '') $params .= "&find_email=".
                                                        $this->find_email;
                                                        
                if($this->show_address != '') $params .= "&address=1";
                if($this->mode != '') $params .= "&mode=".$this->mode;
                if(isset($_GET['sort'])) $params .=  "&sort=".$_GET['sort'];    
                
                if(($offset - $this->limit) < 0) 
                    $prev = 0; 
                    else 
                    $prev = $offset - $this->limit;

                $next = $offset + $this->limit;
                
                
                for ( $i=0; $i<sizeof ( $res ); $i++ ) {

                echo "<tr><form action='index.php' method='post'>
                <input type='hidden' name='search' value='1'>
                <input type=hidden name=id_user value=".$res[$i]['id_user'].">";
                if ( $this->show_address != '' ) echo "<input type=hidden name=show_address value='1'>";
                if ( $this->find_username != '' ) echo "<input type=hidden name=find_username value='$this->find_username'>";
                if ( $this->find_surname != '' ) echo "<input type=hidden name=find_surname value='$this->find_surname'>";
                if ( $this->find_id_payment != '' ) echo "<input type=hidden name=find_id_payment value='$this->find_id_payment'>";
                if ( $this->find_id_user != '' ) echo "<input type=hidden name=find_id_user value='$this->find_id_user'>";
                if ( $this->mode != '' ) echo "<input type=hidden name=mode value='$this->mode'>";
                echo $compiliance_search_hidden;

                echo "<td class=td bgcolor='#444E6C' ><span class=maintxt_small>&nbsp;".$res[$i]['id_user']."</span></td>".
                "<td class=td bgcolor='#444E6C'><span class=maintxt_small>&nbsp;".$res[$i]['username']."</span></td>".
                "<td class=td bgcolor='#444E6C'><span class=maintxt_small>&nbsp;".$res[$i]['firstname']."</span></td>".
                "<td class=td bgcolor='#444E6C'><span class=maintxt_small>&nbsp;".$res[$i]['surname']."</span></td>
                <td class=td bgcolor='#444E6C'>
                    <input type=text name=new_premium_email class=input_small size=15 maxlength=50 value='".$res[$i]['email']."'>
                    <input type=hidden name=old_premium_email value='".$res[$i]['email']."'>
                </td>";

                if ( $this->show_address == 1 ) {

                    echo "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['gender']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['birthdate']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['street']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['postcode']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['city']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['country']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['state']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['phone1']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['phone2']."</td>".
                        "<td  class=td bgcolor='#444E6C'>&nbsp;".$res[$i]['fax']."</td>";
                }

                $sql = sprintf ( "select id_payment 
                        from ds_payment
                        where id_user = %d
                        and verified != true",
                        $res[$i]['id_user'] );

                $res2 = $common->getResultArray ( "unverified_payments", $sql, "", "", "" );
                if ( $res2 === false ) {
                    $this->errormsg = "SYSTEM ERROR EditProfiData::showProfiUsers7";
                    $this->errormsg .= " !!! ".$sql;
                    return false;
                }

                $res[$i]['startdate2'] = substr($value, 0, strpos($res[$i]['startdate2'], "."));

                echo "<td class=td bgcolor='#444E6C'><span class=maintxt_small>&nbsp;".$res[$i]['creationdate']."</span></td>".
                "<td class=td bgcolor='#444E6C'><span class=maintxt_small>".$res[$i]['startdate']."<br>".$res[$i]['enddate']."</span>".
                //"<input type=text name=startdate class=input_small value='".$res[$i]['startdate2']."'>".
                "</td>".
                //"<td class=td bgcolor='#444E6C' align='center'><input type=text name=duration class=input_small size=15 maxlength=6></td>".
                //"<td class=td bgcolor='#444E6C'><input type=text name=enddate class=input_small value='".$res[$i]['enddate']."'></td>".
                "<td class=td bgcolor='#444E6C'><span class=maintxt_small>&nbsp;".$res[$i]['canceldate']."</span></td>".
                "<td class=td bgcolor='#444E6C' align=center><span class=maintxt_small>&nbsp;".$res[$i]['cancelled']."</span></td>".
                "<td class=td bgcolor='#444E6C' align=center><input type='checkbox' class=check name='active'";

                if ( $res[$i]['active'] == 't' ) echo " checked";

                echo "></td>";

                echo "<td class=td bgcolor='#444E6C'> &nbsp; <input type='radio' class=check name='trial_profi' value='trial'";

                if ( $res[$i]['trial'] == 't' ) echo " checked";

                echo ">/<input type='radio' class=check name='trial_profi' value='profi'";

                if ( $res[$i]['profi'] == 't' ) echo " checked";    

                echo "></td>";  

                echo "<td class=td bgcolor='#444E6C' align='center'>";

                if ( sizeof ( $res2 ) > 0 ) echo "<span class='maintxt_small'>YES</span>"; else echo "<span class='maintxt_small'>NO</span>";

                echo "</td>";
                echo "<td class=td bgcolor='#444E6C' align='center'><input type=text name=newpass class=input_small size=15 maxlength=50></td>";

                echo "<td class=td bgcolor='#444E6C' nowrap valign=middle>
                        <input type=submit class=Submit name=edit value=Edit>
                        </form>
                    </td>
                    <td class=td bgcolor='#444E6C' nowrap>
                        <a target='_blank' class=maintxt_small href='showInvoice.php?id_user=".$res[$i]['id_user']."' alt='".local('DS_SHOW_INVOICE')."'>
                            <img src='/Admin/img/knopka_trade.gif' border='0' hspace=0 vspace=0 alt='".local('DS_SHOW_INVOICE')."'>
                        </a>
                        <a target='_blank' class=maintxt_small href='premiumChangeLog.php?id_user=".$res[$i]['id_user']."' alt='show payments'>
                            <img src='/Admin/img/knopka_prop.gif' border='0' hspace=0 vspace=0 alt='show payments'>
                        </a>
                        <a target='_blank' class=maintxt_small href='showsmspays.php?id_user=".$res[$i]['id_user']."' alt='show sms invoice'>
                            <img src='/Admin/img/knopka_sms.gif' border='0' hspace=0 vspace=0 alt='show sms invoice'>
                        </a>
                    </td>
                    <td class=td bgcolor='#444E6C' nowrap valign=middle>
                        <input type=\"button\" value=\"ADD\" class=Submit valign=absmiddle 
                            onclick=\"openWin('addPremium.php?".session_name()."=".session_id()."&id_user=".$res[$i]['id_user'].$params."&offset=$offset"."')\"
                        >
                    </td>";
                
                echo "</tr>";
            }
        }
        
        echo "</table></td></tr></table>";
        
        if((sizeof($_GET) !=0) || (sizeof($_POST) !=0))
        {       
            echo "
                <p align='center'>".
                    $this->showPagePanel($params, $count)."</p>
                <p align='center'><a class=sitemapa href='?offset=".$prev.
                    $params."'><b>"
                    .local( 'DS_ACTION_PREV' )."</b></a> &nbsp; | &nbsp; 
                <a class=sitemapa href='?offset=".$next.
                    $params."'><b>"
                    .local( 'DS_ACTION_NEXT' )."</b></a></p>";              
        }
    }
   
}

$epd = new EditProfiData ();
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
ob_end_flush();
?>
<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";

define('DEFAULT_MIN_INTERSECTIONS',3);

if (isset ($_GET["ban"]))
{
    $str = "/Admin/controls/Players/EditUser/Ban_Unban/index.php?Action=ban&username=".$_GET["ban"];
    headerLocation($str);
    exit();
} elseif (isset($_GET["unban"]))  
{
    $str = "/Admin/controls/Players/EditUser/Ban_Unban/index.php?Action=unban&username=".$_GET["unban"];
    headerLocation($str);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

$world_only = checkDir();

$common = ClassFactory::getInstance ( "DS_Common" );

echo "<SCRIPT language='JavaScript'>
                            
                function switchCheckboxes()
                {                   
                    if(document.forms[0].elements['state'].value == 'false')
                    {
                        document.forms[0].elements['state'].value = 'true';
                    }
                    else
                    {
                        document.forms[0].elements['state'].value = 'false';
                    }
                    
                    for(i=0; i<document.forms[0].elements.length; i++)
                    {
                        tmpstr = document.forms[0].elements[i].name;                        
                        if(tmpstr.substring(0,8) == 'showuser')
                        {                                                               
                                 eval('document.forms[0].elements[i].checked = '+
                                 document.forms[0].elements['state'].value);                        
                        }
                    }                   
                }
            </SCRIPT>";
?>  

        <form method='get' action='<?=@$SERVER["PHP_SELF"]?>'>        
      <table width="100%"><tr><td><table border="0" cellspacing="0" cellpadding="0"><tr><td valign=top>
        <table width=350 border="0" cellspacing="0" cellpadding="0" bgcolor=222233><tr><td><table width=100% border="0" cellspacing="1" cellpadding="4"><tr>
        <td class=th_2>
<p> <? echo local( 'DS_ACTION_SELECT_DB' ); ?></td></tr><tr><td class=maintxt bgcolor=#444E6C>
    <select name='DbServerId'>
<?
$common->setDbId(0);
$worldNames = array();
$sql = "select dbname, worldname from ds_worldcollection";
$res = $common->getResultArray("", $sql);
foreach ($res as $row)
    $worldNames[$row['dbname']] = $row['worldname'];

foreach ($GLOBALS['dbWorldAccess'] as $k=>$v)
{
    if($world_only != 'all')
    {
        if(strcmp($v['server'],$world_only) === 0)
        {
            echo '<option value="'.$k.'" selected>'.$worldNames[$v['server']].'</option>';
            define('serverId',$k);
        }
    }
    else 
    {
        $selected =(($k == $_GET['DbServerId'])?'SELECTED':'');
        echo '<option value="'.$k.'" '.$selected.'>'.$worldNames[$v['server']].'</option>';
    }
}
?>
    </select>
    <input type="checkbox" name="speedsearch" value="true" checked class=check>
    <? echo local( 'DS_ACTION_FAST_SEARCH' ); ?> 
    (<input type="checkbox" name="lastvisdisp" value="true" class=check>
    <? echo local( 'DS_ACTION_SHOW_LAST_VISIT' ); ?>)
    </td></tr>
    </table></td></tr></table></td>
    
    <td width=10></td><td valign=top>
    <table border="0" cellspacing="0" cellpadding="0" bgcolor=222233><tr><td><table border="0" cellspacing="1" cellpadding="4"><tr>
    <td class=th_2>
        <? echo local( 'DS_USER_USERNAME' ); ?></td>
    <td class=th_2> 
        <? echo local( 'DS_USER_EMAIL' ); ?></td></tr>
    <tr>
        <td class=maintxt bgcolor=#444E6C>
            <input name = 'username' type ='text' size = '37' value='<?= @$_GET["username"] ?>'></td>
    <td class=maintxt bgcolor=#444E6C>
        <input name='email' type="text" size="37" value='<?= @$_GET["email"] ?>'></td></tr>
    <tr><td colspan=2 class=maintxt_small bgcolor=#444E6C><? echo local( 'DS_ACTION_SEARCH_HELP' ); ?></td></tr>
    </table></td></tr></table></td></tr><tr><td colspan=3 height=10></td></tr>
    
   <tr><td valign=top> 
    <table width=350 border="0" cellspacing="0" cellpadding="0" bgcolor=222233><tr><td><table width=100% border="0" cellspacing="1" cellpadding="4"><tr>
        <td class=th_2 align=center>
            <? echo local( 'DS_CHECK_MULTY_FROM_LOGIN_DATE' ); ?></td>
        <td class=th_2 align=center>
            <? echo local( 'DS_CHECK_MULTY_TO_LOGIN_DATE' ); ?></td></tr>
   <tr>
        <td class=maintxt bgcolor=#444E6C align=center>
            <input name = 'fromdate' type ='text' size = '23' value='<?= @$_GET["fromdate"] ?>'></td>
        <td class=maintxt bgcolor=#444E6C align=center>
        <input name = 'todate' type ='text' size = '23' value='<?= @$_GET["todate"] ?>'></td></tr>
     </table></td></tr></table></td>
        
    <td width=10></td><td valign=top>
    <table border="0" cellspacing="0" cellpadding="0" bgcolor=222233>
    <tr>
    <td>
    <table border="0" cellspacing="1" cellpadding="4">
    <tr>
        <td class=th_2>
            <? echo local( 'DS_USER_IP' ); ?></td>
        <td class=th_2>
            <? echo local( 'DS_USER_ALLIANCE_TAG' ); ?></td>
        <td class=th_2>
            <? echo local( 'ADMIN_PASSWORD' ); ?>
        </td>
    </tr>
    <tr>
        <td class=maintxt bgcolor=#444E6C align=center>
            <input name = 'ip' type ='text' size = '25' value='<?= @$_GET["ip"] ?>'></td>
        <td class=maintxt bgcolor=#444E6C align=center>
            <input name = 'alliance' type ='text' size = '25' value='<?= @$_GET["alliance"] ?>'></td>
        <td class=maintxt bgcolor=#444E6C align=center>
            <input name = 'clear_password' type ='text' size = '50' value='<?= @$_GET["clear_password"] ?>'>
        </td>
    </tr>
    <tr>
        <td colspan=2 class=maintxt bgcolor=#444E6C align=left>
            <input type="checkbox" name="show_ips" value="show_ips" class=check
            <?if(isset($_GET['show_ips'])) echo 'checked';?>>
                <? echo local( 'DS_CHECK_MULTY_SHOW_LAST' ); ?>
                &nbsp;
            <input name = 'num_ips' type ='text' maxlength='2' size = '2' value=
                '<?= (empty($_GET["num_ips"])) ? 10 : $_GET["num_ips"] ?>'> 
                <? echo local( 'DS_USER_IP' );?>                
        </td>
        <TD class=maintxt bgcolor=#444E6C align=left>
        <input type="checkbox" name="order_group" class=check
            <?if(isset($_GET['order_group'])) echo 'checked';?>>
            <? echo local( 'GROUP_BY_IP_AND_ORDER_BY_TIME' ); ?>
        </TD>
    </tr>
    <tr>
        <td colspan=3 class=maintxt bgcolor=#444E6C>
            <input type="checkbox" name="vertical" value="vertical" class=check
                <?if(isset($_GET['vertical'])) echo 'checked';?>>
            <? echo local( 'DS_CHECK_MULTY_VERTICAL_COMPARE_ONLY' ); ?>
        </td>
    </tr>
    <tr>        
        <td class=maintxt bgcolor=#444E6C align="left">
            <input type="checkbox" name="cookie_search" class=check
                <?if(isset($_GET['cookie_search'])) echo 'checked';?>>
                <? echo local( 'COOKIE_SEARCH' ); ?>
        </td> 
        <td class=maintxt bgcolor=#444E6C align="center">
            <input type="checkbox" name="show_first" class=check
            <?if(isset($_GET['show_first'])) echo 'checked';?>>
            <? echo local( 'SHOW_FIRST' ); ?>
        </td>
        <td class=maintxt bgcolor=#444E6C align="left">                     
            <? echo local( 'SHOW_MIN_INTERSECTIONS' ); ?>
            <INPUT type="text" maxlength="2" size="2" name="min_intersection" 
            value="<?if(isset($_GET['min_intersection']))
                     {
                        echo $_GET['min_intersection'];
                     } 
                     else 
                     {
                        echo constant('DEFAULT_MIN_INTERSECTIONS');
                     }
                   ?>">
        </td>
        
    </tr>
    <tr>    
        
    </tr>
    </table>
    </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    <br>
    <TABLE border="0" cellpadding="0" cellspacing="0" width="100%">        
    <TR>
        <TD align="left">
            <input type="submit" class=Submit name="search" 
            value="<? echo " >>>>> ".local( 'DS_ACTION_SEARCH' )." <<<<< "; ?>">
            <hr size=1 color=222233>
        </TD>
    </TR>
    </TABLE>
    <br>    
<?



if ( isset ( $_GET["search"] ) ) {

    $log_str = "Searching for users: ";
    
    if(defined('serverId'))
    {
        $log_str .= " in db ".constant('serverId');
        $common->setDbId(constant('serverId'));
        $current_db = constant('serverId');
    }
    else 
    {
        $log_str .= " in db ".$_REQUEST['DbServerId'];
        $common->setDbId($_REQUEST['DbServerId']);
        $current_db = $_REQUEST['DbServerId'];
    }
        

    $common->executeQuery ( "begin" );
    $common->executeQuery ( "set STATEMENT_TIMEOUT to 600000" );
    
  
    $user_sql = ""; 
    $refUsers = array();
    
    if (!empty ($_GET["username"]))
    {
        $common->setDbId(0);
        $sql = "SELECT id_user      FROM            ds_user WHERE username = '{$_GET["username"]}'";
    $resUser = $common->getResultArray("", $sql, "", "", "");
      //SU_DebugTools::LogAll("userid=".var_export($resUser, true),'mults.log',0);
      if ($resUser)
      {
        $user_searchID = $resUser[0]['id_user'];
        $oldDbId = $_SESSION["dbId"];
        $_SESSION["dbId"] = 0;
        $sql= "SELECT id_user FROM ds_user_registration_recomend 
        WHERE id_user_recomend = {$user_searchID}";

          $refU = $common->getResultArray("", $sql, "", "", "");
       
          
          assert(empty($common->errormsg));     
        $_SESSION["dbId"] = $current_db;                  
          if ($refU)
          {
            foreach ($refU as $r)
            {
              array_push($refUsers, $r['id_user']);
            }
          }  
      }
        $log_str .= " with username ".$_GET["username"];
        $user_sql = " and username ilike '" . @$_GET["username"] . "' ";        
        $common->setDbId($current_db);
    }

    $fromdate_sql = "";
    if (!empty ($_GET["fromdate"]))
    {
        $log_str .= " with last visit from ".$_GET["fromdate"];
        $fromdate_sql = " and lastvisit >= to_timestamp('".@$_GET["fromdate"]."','YYYY-MM-DD HH24:MI:SS')";
        $fromdate_sql2 = " and lastvisit >= to_timestamp('".@$_GET["fromdate"]."','YYYY-MM-DD HH24:MI:SS')";
    }

    $todate_sql = "";
    if (!empty ($_GET["todate"]))
    {
        $log_str .= " with last visit to ".$_GET["todate"];
        $todate_sql = " and lastvisit <= to_timestamp('".@$_GET["todate"]."','YYYY-MM-DD HH24:MI:SS') ";
        $todate_sql2 = " and lastvisit <= to_timestamp('".@$_GET["todate"]."','YYYY-MM-DD HH24:MI:SS') ";
    }

    $email_sql = "";
    if (!empty ($_GET["email"]))
    {
        $log_str .= " with mail ".$_GET["email"];
        $email_sql = " and email ilike '%" . @$_GET["email"] . "%' ";
    }

    foreach ($_GET as $key => $value) 
    {
        if (!(strpos ($key, "showuser") === FALSE))
        {
            $showuserfound = true;
            $ui_ids .= $value . ",";
        }
    }

    $showuser_sql = "";
    if ($showuserfound == true)
    {
        $ui_ids = substr($ui_ids, 0, strlen ($ui_ids) - 1);
        $showuser_sql = " and ui.id_userinstance in (" . $ui_ids . ") ";
    }

    $check_sql = "";
    if ((!empty ($_GET["username"])) || (!empty ($_GET["email"])))
    {
        if(isset($_GET["cookie_search"]))
        {
            $check_sql = " INNER JOIN
                  (select distinct 
                                    ip,
                                    ip_proxy,
                                    id_cookie 
                                from 
                                    ds_loginhistory, 
                                    ds_user 
                                where 
                                    ds_loginhistory.ip != '1.1.1.1'
                                    and ds_loginhistory.id_user = ds_user.id_user
                                    and ds_loginhistory.id_cookie is not null
                                    ". $user_sql . 
                                    $email_sql . 
                                    $fromdate_sql . 
                                    $todate_sql . 
                    ") as lh2 
                    ON(lh.id_cookie = lh2.id_cookie) ";
        }
        else 
        {
            $check_sql = "INNER JOIN
                  (select distinct ip,ip_proxy from ds_loginhistory, ds_user 
                    where 
                        ds_loginhistory.ip != '1.1.1.1' and
                        ds_loginhistory.id_user = ds_user.id_user 
                    ". $user_sql . $email_sql . $fromdate_sql . $todate_sql . ") lh2 
                    ON(lh.ip = lh2.ip) ";           
        }
    }
    

    $password_sql = "";
    if (!empty ($_GET["password"]))
    {       
        $log_str .= " with password ".$_GET["password"];
        $password_sql = " AND password = '" . @$_GET["password"]."'";
    }
    elseif (!empty($_GET['clear_password']))
    {
        $log_str .= " with password ".$_GET["clear_password"];
        $password_sql = " AND password = md5('" . @$_GET["clear_password"]."')";
    }

    $alliance_sql = "";
    if (!empty ($_GET["alliance"]))
    {
        $log_str .= " with alliancetag ".$_GET["alliance"];
        $alliance_sql = " and alliancetag = '" . @$_GET["alliance"] . "' ";
    }

    $mark_ip = array();

    if (!empty ($_GET["ip"]))
    {
        $ips = explode(',',$_GET["ip"]);
        $ip_str = ''; 
        foreach ($ips as $k=>$v)
        {           
            $mark_ip[trim($v)] = '';
            $ip_str .= "'".trim($v)."',";
        }       
        $ip_str = substr($ip_str,0,-1);
        $ip_sql = "and lh.ip in (".$ip_str.") ";
        $log_str .= " with ip ".$ip_str;
    }
    
    $cookie_sql = "";
    if(!empty($_GET['id_cookie']))
    {
        $cookie_sql = " AND lh.id_cookie=".$_GET['id_cookie'];
    }

    $order_sql = "order by u.username";
    if (!empty ($_GET["order"]))
    {
        $order_sql = " order by " . $_GET["order"];
    }
    
    if (!empty($_GET["speedsearch"])) {
        $jointype = "";

        if (!empty($_GET["lastvisdisp"])) {

            $lastvisit = "(select lh3.lastvisit from ds_loginhistory lh3 where lh3.ip != '1.1.1.1' and lh3.id_user = u.id_user order by lastvisit desc limit 1)";

        } else {

            $lastvisit = "null";

        }
    } else  {

        $jointype = "LEFT";
        $lastvisit = "(select lh3.lastvisit from ds_loginhistory lh3 where lh3.ip != '1.1.1.1' and lh3.id_user = u.id_user order by lastvisit desc limit 1)";
    }
    
    if(isset($_GET['order_group']))
    {
        $order_group = " group by lh.ip, lh.ip_proxy, to_char (lh.lastvisit, 'YYYY-MM-DD HH24:MI:SS'), lh.id_loginhistory
                        order by to_char (lh.lastvisit, 'YYYY-MM-DD HH24:MI:SS') desc";
    }
    else 
    {
        $order_group = " order by to_char (lh.lastvisit, 'YYYY-MM-DD HH24:MI:SS') desc";
    }
    
    
    $common->executeQuery ( "set statement_timeout to 0" );

    $userscore = " (
                    SELECT
                          SUM( c.combscore ) as score
                    FROM
                        ds_comb c
                    WHERE
                         c.id_userinstance = ui.id_userinstance
                    GROUP BY
                          c.id_userinstance 
                    )";
    
    $ispremium = " (
                    SELECT
                          count(upd.*)
                    FROM
                        ds_userprofidata upd
                    WHERE
                        upd.id_user = u.id_user     
                        and upd.active = 't'
                        and upd.enddate > now()
                        and upd.profi = 't'     
                    )";

    $sql = sprintf ( "
                    SELECT distinct
                        u.id_user,
                        u.username,
                        ui.id_userinstance,
                        email,
                        accountactive,
                        to_char (registrationdate, 'YYYY-MM-DD HH24:MI:SS') as registrationdate,
                        password,
                        fr.combname,
                        ui.id_world,
                        " . $userscore . " as userscore,
                        " . $lastvisit . " as lastvisit2,
                        " . $ispremium . " as  premium,
                        fr.alliancetag,
                        fr.coords, 
                        worldname 
                    FROM
                            ds_user u
                        join ds_loginhistory lh ON (lh.id_user = u.id_user)
                        " . $jointype . " join ds_userinstance ui ON (u.id_user = ui.id_user)
                        " . $jointype . " join ds_finalranking fr ON (fr.id_userinstance = ui.id_userinstance)
                        " . $jointype . " join ds_world w ON (w.id_world = fr.id_world)
                        %s
                         where 
                        lh.ip != '1.1.1.1'
                        %s                      
                        %s
                        %s
                        %s
                        %s
                        %s
                        %s
                        %s",
                        $check_sql,
                        @$ip_sql,
                        $cookie_sql,
                        $password_sql,
                        $alliance_sql,
                        @$fromdate_sql2,
                        @$todate_sql2,
                        $showuser_sql,                      
                        $order_sql
                    );
                                        
    $res = $common->getResultArray("",$sql,"","","");
    if($common->errormsg) 
    {       
        die ( "error2 ->" . $sql ."|".$common->errormsg );      
    }

    $ids = array();
    foreach ($res as $row) $ids[] = $row['id_user'];
    $payments = json_decode(file_get_contents("https://billing.destinysphere.ru/gate/payers.php?&v=".VERSION_NAME."&ids=".implode(',',$ids)));
    for ($i=0; $i<count($res); $i++) {
        if (isset($payments->$res[$i]['id_user'])) $res[$i]['totalpaid'] = $payments->$res[$i]['id_user'];
        else $res[$i]['totalpaid'] = 0;
    }
    
    $common->executeQuery ( "commit" );
    actionLog($log_str);

    if (!is_array($res) || (sizeof($res) == 0)) 
    {
        echo "</td>
                </tr>";     
        print "<tr><td>nothing found!</td></tr></table>";       
        exit;
    } 
    else 
    {
        echo "<INPUT type='hidden' value='false' name='state'>              
                <INPUT type='button' onclick='switchCheckboxes();' value='".local('DS_CHECK_MULTY_SELECT_UNSELECT')."' class='Submit'>
                </td>
                </tr>";
        if (!empty($_GET["show_ips"]) && !empty($_GET["vertical"]))
        {
            echo "<table  border='0' cellspacing='0' cellpadding='0' bgcolor=222233><tr>";
            $line = 0;
            foreach ($res as $k=>$v ) 
            {
                $sql = sprintf ( "select distinct 
                                        ip, 
                                        ip_proxy, 
                                        to_char (lastvisit, 'YYYY-MM-DD HH24:MI:SS') as times
                            from 
                                ds_loginhistory lh
                            where 
                                lh.ip != '1.1.1.1' and
                                lh.id_user = %s         
                                %s
                                %s                                  
                                %s
                            limit %s",
                            $v['id_user'],
                            $todate_sql,
                            $fromdate_sql,
                            $order_group,
                            $_GET["num_ips"]);
                        

                $res2 = $common->getQueryArray ( $sql );
                if ( $common->errormsg ) 
                {
                    die ( "error2 ->" . $sql ."|".$common->errormsg);
                }
                
                
                if((int)$v['premium'] === 1) $color = '#3EF755';
                else $color = $v['username'];
                $user_colored = "<font color='$color'>".$v['username']."</font>";

        
                $output .= "<td valign='top' >
                            <table border='0' width='100%' border='0' cellspacing='1' cellpadding='4'>
                            <tr>
                            <td class=th_2 align=center bgcolor=#444E6C nowrap>
                                <input type='checkbox' name='showuser". $line . "' value='".$v['id_userinstance']."' class=check> ".
                                $user_colored.
                            "</td></tr>";

                foreach ($res2 as $k2=>$v2)
                {
                    $output .= "<tr><td class=maintxt_small align=center bgcolor=#444E6C nowrap>";
                    if(isset($v2['ip_proxy']) && $v2['ip_proxy'] != '') 
                    {
                        if(array_key_exists($v2['ip_proxy'],$mark_ip))                      
                        {
                            $output .= "<FONT color='Yellow'>".$v2['ip_proxy'].":</FONT>"; 
                        }
                        else 
                        {
                            $output .= $v2['ip_proxy'].":";
                        }
                    }
                    if(array_key_exists($v2['ip'],$mark_ip))
                    {
                        $output .= "<FONT color='Yellow'>".$v2['ip']."</FONT>"; 
                    }
                    else 
                    {
                        $output .= $v2['ip']; 
                    }
                    $output .= '<br>('.$v2['times'].')</td></tr>';
                }
                $output .= '</table></td>';
                $line++;
            }

            $output .= sprintf ( '</tr></table></td></tr>');
        }
        else
        {

            $get_str = "";
            foreach ($_GET as $key => $value) {
                if ($key != "order")
                $get_str .= $key . "=" . $value . "&";
            }

            echo ('<tr><td><table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor=222233><tr><td><table width="100%" border="0" cellspacing="1" cellpadding="2">
                                <tr>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap>#</td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=u.username">'.local( 'DS_NICK_NAME' ).'</a></td>                               
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=email">'.local( 'DS_USER_EMAIL' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=registrationdate">'.local( 'DS_USER_REGDATE' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap>'.local( 'DS_COUNT_ENTRIES' ).'</td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=password">'.local( 'DS_USER_PASSWORD' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=worldname">'.local( 'DS_USER_WORLD' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=combname">'.local( 'DS_USER_COMBNAME' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=alliancetag">'.local( 'DS_USER_ALLIANCE' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=coords">'.local( 'DS_USER_COORD' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=lastvisit2">'.local( 'DS_USER_LASTVISIT' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=ui.userscore">Заплатил рублей</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=ui.userscore">'.local( 'DS_USER_SCORE' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap><a class=maintxt_small href="index.php?' . $get_str . '&order=accountactive">'.local( 'DS_USER_BANNED' ).'</a></td>
                                  <td class=th_2 align=center bgcolor=#444E6C nowrap>'.local('ADMIN_ACTIONS').'</td>
                                </tr>');

            $oldpass = $res[0]['password'];
            $oldnick = $res[0]['username'];

            $line = 0;

            foreach ( $res as $k=>$v ) {

                $backcolor = ((@$oldpass == $v['password'] && @$oldnick != $v['username'])?" bgcolor=#222233 ":"");
                $oldpass = $v['password'];
                $oldnick = $v['username'];
                
                $user_colored = $v['username'];

                if((int)$v['premium'] === 1) $color = '#3EF755';
                else $color = $v['username'];
                $user_colored = "<font color='$color'>".$v['username']."</font>";
        
        $user_colored .= "({$v['id_user']})";
        
        if ($user_searchID)
        {
                if (in_array($v['id_user'], $refUsers))
                {
                  $user_colored = "<b style='color:yellow'>***</b><span style='color:yellow'>".$user_colored."</span><b style='color:yellow'>***</b>";
                }
        }               

                $res = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));
            
                @$output .= sprintf ( '<tr>
                                <td class=maintxt_small align=center bgcolor=#444E6C><input type="checkbox" name="showuser'. $line . '" value="' . $v['id_userinstance'] . '" class=check></td>         
                                <td class=maintxt_small bgcolor=#444E6C>&nbsp;<a href="index.php?username=%s&search=true&DbServerId=%s&speedsearch=' . $_GET["speedsearch"] . '">%s</a></td>            
                                <td class=maintxt_small bgcolor=#444E6C>&nbsp;<a href="index.php?email=%s&search=true&DbServerId=%s&speedsearch=' . $_GET["speedsearch"] . '">%s</a></td>           
                                <td class=maintxt_small bgcolor=#444E6C nowrap>%s</td>          
                                <td class=maintxt_small bgcolor=#444E6C align=center width=100>%s</td>  
                                <td class=maintxt_small %s align=center bgcolor=#444E6C><a href="index.php?username=%s&password=%s&search=true&DbServerId=%s&speedsearch=' . $_GET["speedsearch"] . '">%s</a></td>                          
                                <td class=maintxt_small bgcolor=#444E6C align=center>%s</a></td>                           
                                <td class=maintxt_small bgcolor=#444E6C>&nbsp;%s</a></td>                       
                                <td class=maintxt_small align=center bgcolor=#444E6C>&nbsp;<a href="index.php?username=%s&alliance=%s&search=true&DbServerId=%s&speedsearch=' . $_GET["speedsearch"] . '">%s</a></td>                           
                                <td class=maintxt_small bgcolor=#444E6C>&nbsp;%s</a></td>                          
                                <td class=maintxt_small bgcolor=#444E6C>&nbsp;%s</a></td>                          
                                <td class=maintxt_small align=center bgcolor=#444E6C>&nbsp;%s</a></td>          
                                <td class=maintxt_small align=center bgcolor=#444E6C>&nbsp;%s</a></td>          
                                <td class=maintxt_small bgcolor=#444E6C align=center>&nbsp;%s</a></td>          
                                <td class=maintxt_small nowrap bgcolor=#444E6C>
                                    <a href="showTrades.php?dbid=%s&world=%s&user=%s">
                                        <img src="/Admin/img/knopka_trade.gif" vspace="1" hspace="1" border="0" alt="'.local('DS_SHOW_TRADE').'">
                                    </a>
                                    <a href="showBattles.php?dbid=%s&world=%s&user=%s">
                                        <img src="/Admin/img/knopka_battle.gif" vspace="1" hspace="1" border="0" alt="'.local('DS_SHOW_BATTLES').'">
                                    </a>
                                    <a href="%s?ban=%s">
                                        <img src="/Admin/img/knopka_lock.gif" vspace="1" hspace="1" border="0" alt="'.local( 'DS_ACTION_BAN' ).'">
                                    </a>
                                    <a href="%s?unban=%s">
                                        <img src="/Admin/img/knopka_unlock.gif" vspace="1" hspace="1" border="0" alt="'.local( 'DS_ACTION_UNBAN' ).'">
                                    </a>
                                </td>
                            </tr>',
                $v['username'],
                $_GET['DbServerId'],
                $user_colored,              
                $v['email'],
                $_GET['DbServerId'],
                $v['email'],
                $v['registrationdate'],
                getCountEntries($common,$v['id_user'],$check_sql,$current_db),
                $backcolor,
                $v['username'],
                $v['password'],
                $_GET['DbServerId'],
                substr ($v['password'],0,10),
                $v['worldname'],
                $v['combname'],
                $v['username'],             
                $v['alliancetag'],
                $_GET['DbServerId'],
                $v['alliancetag'],
                $v['coords'],
                $v['lastvisit2'],
                $v['totalpaid'],
                $v['userscore'],
                ($v['accountactive'] == 't' ? "No" : "Yes"),                
                $_REQUEST['DbServerId'],
                $v['id_world'],
                $v['username'],
                $_REQUEST['DbServerId'],
                $v['id_world'],
                $v['username'],             
                $_SERVER["PHP_SELF"],
                $v['username'],
                $_SERVER["PHP_SELF"],
                $v['username']);

                if (@$_GET["show_ips"]) 
                {
                    $sql = sprintf ( "select distinct 
                                    ip, 
                                    ip_proxy, 
                                    to_char (lastvisit, 'YYYY-MM-DD HH24:MI:SS') as times,
                                    lh.id_loginhistory
                            from 
                                ds_loginhistory lh                  
                            where 
                            lh.ip != '1.1.1.1' and
                            lh.id_user = %s     
                            %s
                            %s                                      
                            %s
                            limit %s",
                            $v['id_user'],
                            $fromdate_sql,
                            $todate_sql,
                            $order_group,
                            $_GET["num_ips"]);

                    $res2 = $common->getQueryArray  ( $sql );
                    if ( $common->errormsg  ) {
                        die ( "error2 ->" . $sql ."|".$common->errormsg);
                    }

                    @$output .= '';
                    foreach ($res2 as $k2=>$v2 ){
                        $output .= "<tr>
                                    <td class=maintxt_small align=center>
                                        &nbsp
                                    </td>
                                    <td class=maintxt_small align=center>".
                                    $user_colored   
                                    ."</td>
                                    <td class=maintxt_small align=center>";
                        if(isset($v2['ip_proxy']) && $v2['ip_proxy'] != '') 
                        {
                            if(array_key_exists($v2['ip_proxy'],$mark_ip))
                            {
                                $output .= "<FONT color='Yellow'>".$v2['ip_proxy'].":</FONT>"; 
                            }
                            else 
                            {
                                $output .= $v2['ip_proxy'].":";
                            }
                        }
                        if(array_key_exists($v2['ip'],$mark_ip))
                        {
                            $output .= '<a href="?show_cookies_action=t&id_login='.$v2['id_loginhistory'].'&DbServerId='.$_REQUEST['DbServerId'].'" target="_blank"><FONT color="Yellow">'.$v2['ip'].'</FONT></a>'; 
                        }
                        else 
                        {
                            $output .= '<a href="?show_cookies_action=t&id_login='.$v2['id_loginhistory'].'&DbServerId='.$_REQUEST['DbServerId'].'" target="_blank">'.$v2['ip']."</a>"; 
                        }
                        
                        $output .= '</td>
                                    <td colspan=11 class=maintxt_small>' .
                                    $v2['times']. '
                                    </td></tr>';
                    }
                    $output .= '';

                }
            }
            $output .= sprintf ( '</table></td></tr></table></td></tr>');
        }

        echo $output;
    }
} elseif (isset ( $_GET["show_cookies_action"] ))
{
    $id_login = (int)$_GET["id_login"]; 
    if ($id_login == 0) {
        echo  "<br>WRONG DATA!<br>"; 
        return ;
        
    }
    if(defined('serverId'))
    {
        $log_str .= " in db ".constant('serverId');
        $common->setDbId(constant('serverId'));
        $current_db = constant('serverId');
    }
    else 
    {
        $log_str .= " in db ".$_REQUEST['DbServerId'];
        $common->setDbId($_REQUEST['DbServerId']);
        $current_db = $_REQUEST['DbServerId'];
    }
    
    $sql = "select 
                u.username, 
                count(u.username) as count
            from 
                ds_loginhistory lh
            left join ds_user u ON (u.id_user = lh.id_user)
            where 
                id_cookie = 
                        (
                            select 
                                id_cookie 
                            from 
                                ds_loginhistory 
                            where 
                                id_loginhistory = ".$id_login."
                        )
            GROUP BY u.username
            ";

    $res = $common->getResultArray("",$sql,"","","");
    if($common->errormsg) 
    {       
        die ( "error2 ->" . $sql ."|".$common->errormsg );      
    }
    
    $output = "<font color='#3EF755'>Игроки, заходившие в игру с темиже кукими, что владелец IP<br><br></font>";

    $output .= '
        <table  border="0" cellspacing="0" cellpadding="0" bgcolor=222233>
        <tr>
        <td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor=222233><tr><td><table width="100%" border="0" cellspacing="1" cellpadding="2">
            <tr>
                 <td class=th_2 align=center bgcolor=#444E6C nowrap>    
                    User Name
                 </td>
                 <td class=th_2 align=center bgcolor=#444E6C nowrap>    
                    Count Of Login
                 </td>
            </tr>
            ';

    foreach ($res as $key => $data)
    {
        $output .= '
            <tr>
                <td class=maintxt_small bgcolor=#444E6C>
                    '.$data['username'].'
                </td>
                <td class=maintxt_small bgcolor=#444E6C align="right">
                    '.$data['count'].'
                </td>
            </tr>';
    }
        $output .= '
            </table>
        </td>
        </tr>
        </table>
        </table>
    ';
    echo $output;
}

function showTimePanel()
{
    for($i=1; $i<=31; $i++)
    {
        if(isset($_POST['sd'])&&((int)$_POST['sd'] === $i))
        {
            $days_scince.= "<OPTION value=".$i." SELECTED>".$i."</OPTION>";
        }
        else 
        {
            $days_scince.= "<OPTION value=".$i.">".$i."</OPTION>";
        }
            
    }
    for($i=1;$i<=12;$i++)
    {
        if(isset($_POST['sm'])&&((int)$_POST['sm'] === $i))
        {
            $months_since.= "<OPTION value=".$i." SELECTED>".$i."</OPTION>";
        }
        else 
        {
            $months_since.= "<OPTION value=".$i.">".$i."</OPTION>";
        }
    }
    for ($i=2003;$i<=date('Y');$i++ )
    {
        if(isset($_POST['sy'])&&((int)$_POST['sy'] === $i))
        {
            $years_since.= "<OPTION value=".$i." SELECTED>".$i."</OPTION>";
        }
        else 
        {
            $years_since.= "<OPTION value=".$i.">".$i."</OPTION>";
        }
    }
    
    echo "<SELECT name='sy'>$years_since</SELECT>&nbsp          
          <SELECT name='sm'>$months_since</SELECT>&nbsp
          <SELECT name='sd'>$days_since</SELECT>&nbsp"; 
    
    return true;
}

?>
    </table>    

<?

function checkDir()
{
    if(preg_match('/\/[_a-zA-Z0-9]+\/index\.php/',$_SERVER['PHP_SELF'],$matches) === 0)
    {
        echo 'wrong dir part1 '.$_SERVER['PHP_SELF'];
        exit;
    }
    if(strlen($matches[0]) < 11)
    {
        echo 'wrong dir part2';
        exit;
    }

    $dir_name = substr($matches[0],1,-10);
    @list($dir, $db_name) = explode('_',$dir_name);
    if(strcmp($dir,'SearchMultyAccaunts') != 0)
    {
        echo 'wrong dir part3';
        exit;
    }
    if(isset($db_name))
    {
        return $db_name;
    }
    
    return 'all';
}

function getCountEntries($common,$id_user, $check_sql,$dbid)
{
    if(!$check_sql)
        return '';
        
    if(isset($_GET['min_intersection']))
    {
        $min_intersections = $_GET['min_intersection'];
    }
    else
    {
        $min_intersections = constant('DEFAULT_MIN_INTERSECTIONS');
    }
        
    if(isset($_GET["cookie_search"]))
    {       
        $id_name = 'id_cookie';     

        $show_first = "";
        if(isset($_GET['show_first']))
        {
            $show_first = ",
                            (SELECT
                                    u.username
                                FROM
                                    ds_loginhistory lh2,
                                    ds_user u
                                WHERE
                                    lh2.ip != '1.1.1.1'
                                    AND lh2.id_cookie = lc.id_cookie
                                    AND lh2.id_user = u.id_user
                            ORDER BY 
                                    lh2.lastvisit
                                LIMIT 1
                            ) as first ";
        }
            
        $count_entries_sql = " SELECT
                                    lc.id_cookie as id,
                                    count(lh.id_loginhistory)
                                     ".$show_first." 
                                FROM
                                    ds_loginhistory as lh
                                    NATURAL JOIN ds_logincookie as lc
                                     ".$check_sql." 
                                WHERE
                                    lh.ip != '1.1.1.1' and
                                    lh.id_user = ".$id_user."
                                    AND lh.id_cookie is not null
                            GROUP BY lc.id_cookie";
    }
    else 
    {       
        $id_name = 'ip';    
        $count_entries_sql = "SELECT
                                    lh.ip as id,
                                    count(lh.id_loginhistory)
                                FROM
                                    ds_loginhistory as lh
                                     ".$check_sql." 
                                WHERE
                                    lh.ip != '1.1.1.1' and
                                    lh.id_user = ".$id_user."
                                GROUP BY lh.ip";
    }
    
    $res = $common->getResultArray("",$count_entries_sql,"","","");
    if($common->errormsg) 
    {
        //die ( "error3 ->" . $sql ."|".$common->errormsg );
        die ( "error3 ->" ."|".$common->errormsg );
    }
    
    if(sizeof($res) == 0)
        return '';
    
    $res_arr = array();
    foreach ($res as $v)
    {
        if ((int)$v['count'] < $min_intersections) 
            continue;
            
        $tmp_str = "<a href='".$_SERVER['PHP_SELF'].
                        "?search=1&speedsearch=true&DbServerId=".$dbid.'&'.
                        $id_name."=".$v['id'].                      
                        "'>".
                        $v['count'];
        array_key_exists('first',$v) ? $tmp_str .= ' ('.$v['first'].')':$tmp_str .= '';                     
        $tmp_str .= "</a>";
        $res_arr[] = $tmp_str;
    }
        
    return join(", ",$res_arr);
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
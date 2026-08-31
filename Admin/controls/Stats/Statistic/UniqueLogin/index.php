<?php
ini_set('max_execution_time', 120000);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=UnigueLogin><b>HELP</b></a></p>';

if(count($_REQUEST['dateFrom']) > 0)
{
    $start = $_REQUEST['dateFrom'];
} else {
    $week = strtotime("-1 week");

    $start[0] =  date('Y', $week);
    $start[1] = date('m', $week);
    $start[2] = date('d', $week);
    $start[3] = date('H', $week);
}

if(count($_REQUEST['dateTo']) > 0)
{
    $end = $_REQUEST['dateTo'];
} else {
    $end[0] = date('Y');
    $end[1] = date('m');
    $end[2] = date('d');
    $end[3] = date('H') + 1;
}

echo <<<EOT
    <form action="" method="POST">
    <table border=0>
        <tr>
            <td>
                <font color=#F8F8FF>Start date:</font> 
                <table border=0>
                    <tr class=th_2>
                        <td>Year</td><td>month</td><td>day</td><td>hour</td>
                    </tr>
                    <tr>
                        <td><input type='text' size=4 name='dateFrom[0]' value="{$start[0]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateFrom[1]' value="{$start[1]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateFrom[2]' value="{$start[2]}">&nbsp; &nbsp;</td>
                        <td><input type='text' size=2 name='dateFrom[3]' value="{$start[3]}"></td>
                    </tr>
                </table>
            </td>
            <td width=250px>
                <font color=#F8F8FF>End date:</font>
                <table border=0>
                    <tr class=th_2>
                        <td>Year</td><td>month</td><td>day</td><td>hour</td>
                    </tr>
                    <tr>
                        <td><input type='text' size=4 name='dateTo[0]' value="{$end[0]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateTo[1]' value="{$end[1]}">&nbsp;-&nbsp;</td>
                        <td><input type='text' size=2 name='dateTo[2]' value="{$end[2]}">&nbsp; &nbsp;</td>
                        <td><input type='text' size=2 name='dateTo[3]' value="{$end[3]}"></td>
                    </tr>
                </table>
            </td>
        </tr>   
        
        </table>
        
    <br><input type='submit' class='Submit' name='show_unique_login' value='&nbsp;&nbsp;&nbsp;&nbsp;Show Stat&nbsp;&nbsp;&nbsp;&nbsp;'>
    </form>
EOT;




if ( isset($_REQUEST["show_unique_login"] ) )
{
    assert(is_array($_REQUEST['dateFrom']));
    $dateFrom = $_REQUEST['dateFrom'][0].'-';
    $dateFrom .= $_REQUEST['dateFrom'][1].'-';
    $dateFrom .= $_REQUEST['dateFrom'][2].' ';
    $dateFrom .= $_REQUEST['dateFrom'][3].':00:00';
    
    assert(is_array($_REQUEST['dateTo']));
    $dateTo = $_REQUEST['dateTo'][0].'-';
    $dateTo .= $_REQUEST['dateTo'][1].'-';
    $dateTo .= $_REQUEST['dateTo'][2].' ';
    $dateTo .= $_REQUEST['dateTo'][3].':00:00';

    $common = ClassFactory::getInstance ( "DS_Common" );
    $common->setDbId(0);

    //Выбираем общую инфу о игроке
    $sql = "
        SET STATEMENT_TIMEOUT to 120000;
        SELECT count(*) as count
                FROM (
                        SELECT
                             id_user
                            FROM
                                ds_loginhistory
                            WHERE 
                                lastvisit > '".$dateFrom."'
                                 AND 
                                lastvisit < '".$dateTo."'                           
                            GROUP BY id_user) as c";

    $countUniqueLogin = $common->getResultArray ("", $sql, "", "", "" );
    assert(empty($common->errormsg));

    assert(count($countUniqueLogin) == 1);

    assert(count($countUniqueLogin) == 1);
    echo "<font color=#F8F8FF>Count Of Login: <b>".$countUniqueLogin[0]['count']."</b></font><br>";
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
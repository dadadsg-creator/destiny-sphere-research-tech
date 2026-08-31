<? 
// Show user trade from trade history
$world_only = checkDir();

if(!isset($_GET['dbid']) || !isset($_GET['user']) || !isset($_GET['world']))
{
    header('Location: index.php'); 
    exit; 
}

$user = pg_escape_string(trim($_GET['user']));
$world = (int)$_GET['world'];


if(isset($_GET['only_same_ip']) && $_GET['only_same_ip'] == 'on')
{
    $onlySameIP = true;
} else {
    $onlySameIP = false;
}

$log_str = "Viewing trades: ";

if($world === "") 
{
    echo "World must be specified!!"; 
    exit;
}
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

$world = (int)$_GET['world'];

$common = ClassFactory::getInstance("DS_Common");
    
if($world_only != 'all')
{
    foreach ($GLOBALS['dbWorldAccess'] as $k=>$v)
    {
        if(strcmp($v['server'],$world_only) === 0)
        {
            if (!$common->setDbId($k)) 
            {
                echo "DB-connection failed to ".$k; exit;
            }
            $log_str .= " in db ".$k;
        }
    }
} else {
    if (!$common->setDbId($_GET['dbid'])) 
    {
        echo "DB-connection failed to ".$_GET['dbid']; exit;
    }
    
    $log_str .= " in db ".$_GET['dbid'];
}
$trades = $common->getResultArray("trades_".$user."_$world", "
SELECT
    (
        SELECT
            u_from.username
        FROM
            ds_user AS u_from
        WHERE
            u_from.id_user = hist.from_user_id
    ) AS seller,

    (
        SELECT
            u_to.username
        FROM
            ds_user AS u_to
        WHERE
            u_to.id_user = hist.to_user_id
    ) AS customer,

                
    (
        SELECT
            lh1.ip
        FROM
            ds_user use1,
            ds_loginhistory lh1
        WHERE
            use1.id_user = hist.from_user_id
            AND use1.id_user = lh1.id_user
            AND lh1.lastvisit < hist.start_date
        ORDER BY lh1.lastvisit DESC
        LIMIT 1
    ) as seller_ip,
    
    (
        SELECT
            lh1.ip
        FROM
            ds_user use2,
            ds_loginhistory lh1
        WHERE
            use2.id_user = hist.to_user_id
            AND use2.id_user = lh1.id_user
            AND lh1.lastvisit < hist.start_date
        ORDER BY lh1.lastvisit DESC
        LIMIT 1
    ) as customer_ip,
                
    CASE
        -- 1 recource
        WHEN c_from.id_tradecategory = 1 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_attributename an,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = an.id_attributename
                AND an.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANG_ID")."
        )
        
        -- 2 units
        WHEN c_from.id_tradecategory = 2 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_unit uni,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = uni.id_unit
                AND uni.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANG_ID")."
        )

        -- 3 arts
        WHEN c_from.id_tradecategory = 3 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_artefact art,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = art.id_artefact
                AND art.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANG_ID")."
        )
        
        -- 4 money
        WHEN c_from.id_tradecategory = 4 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_money mon,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = mon.id_money
                AND mon.id_locolizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANG_ID")."
        )
        
        ELSE
            c_from.categoryname
        END AS goods_from,                           
            
    hist.from_ammount,
        
    CASE
        -- 1 recource
        WHEN c_from.id_tradecategory = 1 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_attributename an,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = an.id_attributename
                AND an.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )
        
        -- 2 units
        WHEN c_from.id_tradecategory = 2 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_unit uni,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = uni.id_unit
                AND uni.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )
        
        -- 3 arts
        WHEN c_from.id_tradecategory = 3 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_artefact art,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = art.id_artefact
                AND art.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )
        
        -- 4 money
        WHEN c_from.id_tradecategory = 4 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_money mon,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_from_article = mon.id_money
                AND mon.id_locolizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
            )
        
    ELSE
        c_from.categoryname
    END AS goods_from,
            
    hist.from_ammount,

    CASE
        -- 1 recource
        WHEN c_to.id_tradecategory = 1 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_attributename an,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_to_article = an.id_attributename
                AND an.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )
            
        -- 2 units
        WHEN c_to.id_tradecategory = 2 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_unit uni,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_to_article = uni.id_unit
                AND uni.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )

        -- 3 arts
        WHEN c_to.id_tradecategory = 3 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_artefact art,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_to_article = art.id_artefact
                AND art.id_localizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )

        -- 4 money
        WHEN c_to.id_tradecategory = 4 THEN
        (
            SELECT
                ltl.text
            FROM
                ds_money mon,
                ds_localizedtextlanguage ltl
            WHERE
                hist.id_to_article = mon.id_money
                AND mon.id_locolizedname = ltl.id_localizedtext
                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
        )
        
    ELSE
        c_from.categoryname
    END AS goods_to,

    hist.from_ammount,
    
    hist.to_ammount,
    
    (hist.to_ammount/hist.from_ammount) as course,
    
    hist.end_date

FROM
    ds_trade_history AS hist
    INNER JOIN ds_user AS u ON (u.id_user = hist.from_user_id OR u.id_user = hist.to_user_id)
    INNER JOIN ds_trade_category AS c_from ON (c_from.id_tradecategory = hist.id_from_category)
    INNER JOIN ds_trade_category AS c_to ON (c_to.id_tradecategory = hist.id_to_category)
    LEFT JOIN ds_trade_scope AS ts ON (ts.id_tradescope = hist.id_trade_scope)
WHERE
    u.username = '$user'
    AND
    hist.world_id = $world
ORDER BY
    hist.end_date DESC
    ",
    "",
    "",
    "");
    

$log_str .= " for user ".$user;
$log_str .= " in world with id ".$world;
actionLog($log_str);

if($trades === false)
{
    echo "Unable to get trades for user $user ".pg_last_error(); exit; 
} 

if(!$trades)
{
    echo "No trades found for user $user"; 
    exit; 
}

?>

<center><span class=select_txt><b><?=$user?></b></span></center>
<center>
<form action="<?=@$SERVER["PHP_SELF"]?>" method="GET">
    <input type="hidden" name="user" value="<?=$user?>">
    <input type="hidden" name="dbid" value="<?=$_GET['dbid']?>">
    <input type="hidden" name="world" value="<?=$world?>">
    <input type="checkbox" name="only_same_ip" <?if($onlySameIP){?>checked<?}?>><span class=maintxt_med>Only red trades</span>
    <input type="submit" value="show">
</form>
</center>
<br>
<table width=100% cellpadding="0" cellspacing="0" bgcolor=#222233><tr><td><table width="100%" border="0" cellspacing="1" cellpadding="2">
<tr><td class=th_2 align=center>#</td><td class=th_2 align=center>Who</td><td class=th_2 align=center>goods</td><td class=th_2 align=center>trade by</td><td class=th_2 align=center>date</td></tr>

<?php
foreach ($trades as $k=>$v ) 
{
    
    if($onlySameIP && strcmp($v['customer_ip'],$v['seller_ip']) != 0)
    {
        continue;
    }
    
    $date = preg_replace('/(.+)\..+/','$1',$v['end_date']);

    if($v['seller'] === $user) 
    {
        echo "<tr>
            <td bgcolor=#444E6C class=maintxt_med>
                <span class=g_yellou>".local('DS_SELLED_TO')."</span>
            </td>
            <td bgcolor=#444E6C class=maintxt_med><a href='".
            $_SERVER['PHP_SELF'].
            "?dbid=".$_GET['dbid'].
            "&world=".$_GET['world'].
            "&user=".$v['customer'].
                    "' >";
                    
        if(strcmp($v['customer_ip'],$v['seller_ip']) == 0)
        {
            echo "<font color='red'>".$v['customer']."</font>";
        } else {
            echo $v['customer'];
        }
                    
        echo "</a></td>
            <td bgcolor=#444E6C class=maintxt_med>";
        
    } elseif($v['customer'] === $user) {
        
        echo "<tr>
            <td bgcolor=#444E6C class=maintxt_med>
                <span class=g_green>".local('DS_BUYED_FROM')."</span>
            </td>
            <td bgcolor=#444E6C class=maintxt_med><a href='".
            $_SERVER['PHP_SELF'].
            "?dbid=".$_GET['dbid'].
            "&world=".$_GET['world'].
            "&user=".$v['seller'].
                    "' >";
        if(strcmp($v['customer_ip'],$v['seller_ip']) == 0)
        {
            echo "<font color='red'>".$v['seller']."</font>";
        } else {
            echo $v['seller'];
        }   
        
        echo "</a></td>
            <td bgcolor=#444E6C  class=maintxt_med>";
        
    } else {    
        
        echo "<tr>
                <td colspan=2 bgcolor=#444E6C class=maintxt_med>
                    ERROR 
                </td>
                <td>
                    $user
                </td>
                <td bgcolor=#444E6C class=maintxt_med>"; 
    }
    
    echo number_format($v['from_ammount']). " " . $v['goods_from']; 
?>
   </td>
   <td bgcolor=#444E6C class=maintxt_med><?= number_format($v['to_ammount'])." " . $v['goods_to'] ?></td>
   <td bgcolor=#444E6C class=maintxt_med><?=$date?></td></tr>
<?php
} // END OF FOREACH
?>
</table></td></tr></table>

<?php

function checkDir()
{
    if(preg_match('/\/[_a-zA-Z0-9]+\/showTrades\.php/',$_SERVER['PHP_SELF'],$matches) === 0)
    {
        echo 'wrong dir';
        exit;
    }
    
    if(strlen($matches[0]) < 16)
    {
        echo 'wrong dir';
        exit;
    }

    $dir_name = substr($matches[0],1,-15);
    @list($dir, $db_name) = explode('_',$dir_name);
    if(strcmp($dir,'SearchMultyAccaunts') != 0)
    {
        echo 'wrong dir ';
        exit;
    }

    if(isset($db_name))
    {
        return $db_name;
    }
    
    return 'all';
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php"; ?>

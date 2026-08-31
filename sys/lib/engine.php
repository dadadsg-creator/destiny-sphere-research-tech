<?
/*
function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
    } 
$start_time =  getmicrotime();
*/

require_once $_SERVER['DOCUMENT_ROOT']."/sys/define.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/templates.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/MYSQLManager.php";

if ($VIS_ADMIN) {
    require_once $_SERVER['DOCUMENT_ROOT']."/sys/PageManagerBase2.php";
} else {
    require_once $_SERVER['DOCUMENT_ROOT']."/sys/PageManagerBase.php";
};

require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/PageManager5.php";

$p = $_GET['p'];

if (strpos($p, " ")) {$p = substr($p, 0, strpos($p, " "));}
if (strpos($p, "\"")) {$p = substr($p, 0, strpos($p, "\\\""));}
if (strpos($p, "'")) {$p = substr($p, 0, strpos($p, "\\'"));}
if (strpos($p, ";")) {$p = substr($p, 0, strpos($p, ";"));}


getMysqlConnection();
$query = "select Auth from Pages where ID='".$p."'";
$res = mysql_query($query);
if (mysql_num_rows($res)) {
    $auth =  mysql_result($res, 0, 'Auth');
    if ($auth == 'Y') {
        $query = "select COUNT(*) from SiteUsers where Email='".$_SESSION['uemail']."' and Password='".$_SESSION['password']."'";
        $res = mysql_query($query);
        if (@mysql_result($res, 0, 'COUNT(*)') == 0) {
            headerLocation('?p=register&action=loginform&redirect='.$p);
            die;
        }
    }
}


$P = new PageManager(array("PageID"=>$p));
if (!isset($P) || $P->Page['lang_id'] != $default_lang) {
//  $p = $default_page;
    $P = new PageManager(array("PageID"=>$default_page));
    if (!isset($P)) {
        echo "Fatal error. Can't load index page !";
        exit;
    }
}

ob_start();

//=============================
//*********************
if ($VIS_ADMIN) {
?>
<script>
var ds = new Array();
var i = 0;
var vis = 'hidden';
</script>
<?
};
//*********************
//=============================

echo $P->ApplyTemplate(1,"main");
ob_end_flush();

//=============================
//*********************
if ($VIS_ADMIN) {
?>
<div id="lay1" style="position:absolute;top:0;left:0;visibility:block;filter:alpha(opacity=30)" onmouseover="document.all.lay1.style.filter='alpha(opacity=100)'"  onmouseout="document.all.lay1.style.filter='alpha(opacity=30)'">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr bgcolor="#CCCCCC">
        <td><input type="checkbox" class=check<?= ($visible == 'visible') ? 'checked' : '';?> onClick="f(this)"><font face="Verdana" size="-1">Отметить текстовые блоки</font></td>
        <td align="right"><font face="Verdana" color="#FF0000"><strong><a href="/Admin/?act=normal">Админская зона</a></strong></font></td>
    </tr>
</table>
</div>
<script>
    function f(chb) {
        if (chb.checked == true) {
            vis = 'visible';
        } else {
            vis = 'hidden';
        };
        document.cookie = 'visible='+vis;
        for (j=0;j<i;j++) {
            eval ("document.all."+ds[j]+".style.visibility='"+vis+"';");
        };
    };
<?
if ($visible == 'visible') {
?>
        for (j=0;j<i;j++) {
            eval ("document.all."+ds[j]+".style.visibility='visible';");
        };
<?      
};
?>
    function seldiv_show(id) {
        eval('div = document.all.seldiv_'+id);
        if (div.style.visibility=='hidden') {
            div.style.visibility='visible';
        } else {
            div.style.visibility='hidden';
        }
    }
</script>
<?
};
//*********************
//=============================

//echo "<center><small>Скрипт выполнен за ".round (getmicrotime()-$start_time,3)." сек.</small></center>";
?>

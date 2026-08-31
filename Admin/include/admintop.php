<?
//echo "sadfasdfasyidtfkjasdgkajsdhgkasjdhg";
//die('TUT111');
$VIS_ADMIN = 0;

if ($UserManager->ErrMsg) $ErrMsg = $UserManager->ErrMsg;

/*if (!$UserManager->CheckMenuUrl($PHP_SELF)) {
$RedirectUrl = $UserManager->GetFirstMainMenuUrl();
if ($RedirectUrl) headerLocation("$RedirectUrl");
}*/

//$MainMenu = $UserManager->GetMainMenu();


?>

<html>
<head>
    <title><? echo local('ADMIN_ADMIN_CONSOLE_DS'); ?></title>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=<? echo local('ADMIN_CHARSET'); ?>">
</head>
<LINK href="/Admin/styles/ie.css" type=text/css rel=stylesheet>
<link href='/Admin/styles/bs_calendar.css' rel='stylesheet' type='text/css' />
<?

?>
<script src="/Admin/js/menu.js"></script>
<script language='Javascript' src='/Admin/js/bscal.js'></script>

<body margin="0" leftmargin="0" topmargin="0" color=white bgcolor="FFFFFF" marginwidth=0 marginheight=0>

<table cellpadding="0" cellspacing="0" border="0" background="/Admin/img/header-bg2.gif" width="100%" height="100%" valign="top">
    <tr>
        <td valign="top">
            <img src="/Admin/img/emp.gif" width="770" height="0" alt="" border="0"><br>
            <table cellpadding="0" cellspacing="0" border="0" width="100%" height="100%" valign="top">
                <tr>
                    <td width="100%" align="right" colspan="4"><img src="/Admin/img/emp.gif" width="1" height="10" alt="" border="0">
                        <table cellpadding="0" cellspacing="0" border="0" valign="top">
                            <tr>
                                <td valign="top">
                                    <table cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td><img src="/Admin/img/logo_small.gif" width="262" height="39" alt="" border="0"></td>
                                 <td nowrap><span class="MText"><b><?=$_SERVER['SERVER_NAME']?></b></span><br></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="100%" align="right">

                                </td>
                            </tr>
                            <tr>
                                <td><img src="/Admin/img/emp.gif" width="2" height="1" alt="" border="0" hspace="200"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td valign="top"><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="85">
                                </td>
                                <td align="right">
                                    <table cellpadding="0" cellspacing="0" border="0" bgcolor="#444E6C">
                                        <tr>
                                            <td><img src="/Admin/img/both-top-indic-tbl.gif" width="9" height="24" alt="" border="0"></td>
                                            <td ><img src="/Admin/img/both-top-indic-man.gif" width="16" height="24" alt="" border="0" hspace="3"></td>
                                            <td >
                                                <img src="/Admin/img/emp.gif" width="2" height="1" alt="" border="0"><br>
                                                <span class="SmallAText">&nbsp;<?=$UserManager->GetUserName()?>&nbsp;</span>
                                            </td>
                                            <td ><img src="/Admin/img/emp.gif" width="3" height="1" alt="" border="0" hspace="0"></td>
                                            <td valign="bottom"><a href="/Admin/login.php?action=logout"><img src="/Admin/img/both-top-indic-lo-button.gif" width="58" height="20" alt="" border="0"></a></td>
                                            <td ><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="10"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
        </tr>
        <tr>
        <td bgcolor="#444E6C" width="100%" colspan="4"><img src="/Admin/img/emp.gif" width="1" height="4" alt="" border="0"></td>
        </tr>

        <tr>
        <td bgcolor="#444E6C" width="100%" colspan="4">


<table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>                                        
                    <?
                    foreach ($adminMenu->menu1Level as $k=>$v){
                        ?>
                        <td nowrap background="/Admin/img/publisher-top-menu-na-bg.gif" align="center" id="d<?=$k?>"><a href="<?=addSID($v['Url'])?>" class="MMenuLink" onmouseover="showTNav(<?=$k?>)" onmouseout="hideTNav()">&nbsp;&nbsp;<?=$v['Title']?>&nbsp;&nbsp;</a></td>
                        <td width="2"><img src="/Admin/img/publisher-top-menu-spc.gif" width="1" height="25" alt="" border="0"></td>
                        <?
                    }
                    ?>

                </tr>
            </table>

            <table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td bgcolor="#9CA4B7" width="100%">
        <?      
        $menu2LevelActive = $adminMenu->curentLevel2Active();
        foreach ($adminMenu->menu1Level as $k=>$v) {
            ?>
            <div id="navMenu<?=$k?>" style="position:parent; left:0px; top:0px; display:none" onMouseOver="resetnTimer()" onMouseOut="hideTNav()">
            &nbsp;&nbsp;&nbsp;&nbsp;
                        <?
                        foreach ($adminMenu->menu2Level as $k2=>$v2) {
                            if($v2['Parent_ID'] != $k) continue;
                            ?> <img src="/Admin/img/menu-arrow.gif" width="5" height="5" alt="" border="0"> <?
                            $Title = str_replace(" ","&nbsp;",$v2['Title']);
                            $Url = str_replace(" ","&nbsp;",$v2['Url']);

                            if ($menu2LevelActive == $v2['ID']) {
                                echo "<span class=\"SMenuLink_Active\">".$Title."</span>";
                            } else {
                                echo "<a href=\"".addSID($UrlBase.$Url)."\" class=\"SMenuLink\">".$Title."</a>";
                            }
                        }
                        ?>
            </div>
            <?
        }
        ?>
        </td>
        <td bgcolor="#6C7B8B" width="100%"><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0"  vspace="12"></td>
    </tr>
    <tr>
        <td bgcolor="#6C7B8B"><img src="/Admin/img/emp.gif" width="770" height="1" alt="" border="0"></td>
        <td bgcolor="#6C7B8B" width="100%"><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0"></td>
    </tr>
</table>
<? //echo "!!"; echo $adminMenu->curentLevel1Active(); echo "!!";?>
<script>
InitMenu('/Admin/img/publisher-top-menu-a-bg.gif','/Admin/img/publisher-top-menu-na-bg.gif');
showDefaultMenu(<?=$adminMenu->curentLevel1Active()?>);
</script>


        </td>
        </tr>
                <tr>
                    <TD  vAlign=top width="170" bgcolor="#444E6C">
                        <br>
                    </td>
                    <td width="10" bgcolor="#444E6C"><img src="/Admin/img/emp.gif" width="10" height="1" border="0"></td>
                    <td width="100%" height="100%" valign="top" bgcolor="#444E6C"><h1><?=$ActiveMenu?></h1>

                        <?if ($ErrMsg) echo "<font color=red>".$ErrMsg."</font><br><br>";?>
<? 
if($adminMenu->have3Lavel($adminMenu->curentLevel1Active(), $adminMenu->curentLevel2Active()))
{
    $menu3 = $adminMenu->menu[$adminMenu->curentLevel1Active()]['Childe'][$adminMenu->curentLevel2Active()]['Childe'];
    if(count($menu3)>0){
        foreach($menu3 as $k=>$v)
        {
            if($v['ID'] ==$adminMenu->curentLevel3Active() )
            {
                $menuOut[] = '<span class=select_txt ><b>'.$v['Title'].'</b></span>';
            } else {
                $menuOut[] = '<a href="'.addSID($v['Url']).'" class="maintxt">'.$v['Title'].'</a>';
            }
        }

        echo implode (' <img src="/Admin/img/menu-arrow.gif" border=0> ', $menuOut);
        echo "<br><br>";
    }

}
?>
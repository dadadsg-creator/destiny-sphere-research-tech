<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <META HTTP-EQUIV="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="post-check=0, pre-check=0">
    <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
    <META NAME="Last-Modified" CONTENT="{$lang.LAST_MODIFIED} GMT">
    <title>{$lang.TITLE}</title>
    <link rel="stylesheet" href="cabinetstyle.css" type="text/css">
    <!--../dsstyle_il.css-->
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>
<body>
<script language="javascript">
<!--
{literal}
function openURL (url, target) {
    window.open(url, target);
}
{/literal}
//-->
</script>
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0 class=bg_top>
    <tr>
        <td height=100% align=center valign=top>
            <table width=875 border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td valign=top class=bg_left><img src="img/cabinet/bg/bg_left.jpg" border=0></td>
                    <td height=100% valign=top class=bg01>
                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                            <!--begin top line-->
                            <tr>
                                <td height=10 background="img/cabinet/bg/bg_head_02.gif">{include file='counters.php'}</td>
                            </tr>
                            <!--end top line-->
                            
                            <!--begin header-->
                            <tr>
                                <td>
                                    <table width=100% height=86 border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td width=500>
                                                <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="500" height="86" id="header_1" align="middle">
                                                    <param name="allowScriptAccess" value="sameDomain">
                                                    <param name="movie" value="../img/flash/header.swf">
                                                    <param name="quality" value="best">
                                                    <param name="menu" value="false">
                                                    <param name="settings" value="false">
                                                    <param name="wmode" value="opaque">
                                                    <param name="bgcolor" value="#ffffff">
                                                    <embed src="../img/flash/header.swf" quality="best" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
                                                </object></td>
                                            <td width=265><img src="../img/background/header_bg.jpg" border=0></td>
                                            <td width=100% background="../img/background/header_bg2.gif"></td>  
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end header-->
                            
                            <!--begin menu top-->
                            <tr>
                                <td align=center class=bg_top_menu>
                                    <table height=34 border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div class=menu onclick="openURL('http://destinysphere.ru');" valign=middle><span style="position: relative; top: 9px;">œŒ–“¿À</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div style="width:110px; height:100%; cursor: hand;" onclick="openURL('http://forum.destinysphere.ru/forum/index.php');" valign=middle><span style="position: relative; top: 9px;">‘Œ–”Ã</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div style="width:110px; height:100%; cursor: hand;" onclick="openURL('http://news.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">√¿«≈“¿</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div style="width:110px; height:100%; cursor: hand;" onclick="openURL('http://sms.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">DS-Mobile</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                            <td width="110" height="22" align=center valign="middle" class="menu_txt">
                                                <div style="width:110px; height:100%; cursor: hand;" onclick="openURL('https://billing.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">¡»ÀÀ»Õ√</span></div></td>
                                            <td align=center valign="top">
                                                <img height=34 src="img/cabinet/graph/del_but01.gif" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end menu top-->
                            
                            <!--begin center-->
                            <tr>
                                <td height=100% valign=top>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td width=200 valign=top>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=3>
                                                    {if !empty($ERROR_TEXT)}
                                                    <tr>
                                                        <td valign=top>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                    <td width=100% align=center bgcolor="#ffffff" style="padding:5px;">
                                                                        <font color='#DD0D1A'><b>{$lang.ERROR}</b>&nbsp;{$lang.$ERROR_TEXT}</font>
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    {/if}
                                                    <tr>
                                                        <td valign=top>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                    <td width=100% bgcolor="#ffffff">
                                                                        {if $RAMBLER_LOGIN}
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=4>
                                                                                <form method='post' action='index_login.php'>
                                                                                <input type="hidden" name="rambler_id_login" value="1">
                                                                                <tr>
                                                                                    <td height=100% class=header_txt align="center">
                                                                                        {$lang.ENTER}</span>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=1 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=100% align="center">
                                                                                        <b>{$USER_NAME}</b>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=100% align="center">
                                                                                        <input type="submit" value="{$lang.ENTER}" class=submit01>
                                                                                    </td>
                                                                                </tr>
                                                                                </form>
                                                                                <tr>
                                                                                    <td height=1 class=delimetr01></td>
                                                                                </tr>
                                                                                <form action="index_login.php" method="POST">
                                                                                <tr>
                                                                                    <td align="center">
                                                                                        <input type="checkbox" name="disable" class=check>{$lang.DISABLE_REG}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=1 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center">
                                                                                        <input type="hidden" name="cancel_rambler_login" value="1">
                                                                                        <input type="submit" value="{$lang.CANCEL}" class=submit01><br>
                                                                                    </td>
                                                                                </tr>
                                                                                </form>
                                                                            </table>
                                                                            
                                                                        {elseif $RAMBLER_REGISTRATION}
                                                                            
                                                                            <form action="index_registration.php" method="POST">
                                                                            <input type="hidden" name="user" value="{$USERNAME}">
                                                                            <input type="hidden" name="pass" value="{$PASSWORD}">
                                                                            <input type="hidden" name="conf" value="{$PASSWORD}">
                                                                            <input type="hidden" name="lang" value="3">
                                                                            <input type="hidden" name="checkbox" value="on">
                                                                            <input type="hidden" name="email" value="{$EMAIL}">
                                                                            <input type="hidden" name="register" value="1">
                                                                            <input type="hidden" name="rambler" value="1">
                                                    
                                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=4>
                                                                                <tr>
                                                                                    <td align="center" class=header_txt>
                                                                                        {$lang.RAMBLER_ID}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center">
                                                                                        <b>{$EMAIL}</b>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=1 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center" class=header_txt>
                                                                                        {$lang.REGISTRATION}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=100%_large align="center">
                                                                                        <b>{$lang.USERNAME}</b>&nbsp;
                                                                                        <input type="text" class="input_login_wnd" name="user" value="{$USERNAME}">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center" class=header_txt>
                                                                                        <input type="submit" value="{$lang.REGISTRATE}" class="submit02">
                                                                                    </td>
                                                                                </tr>
                                                                            </form>
                                                                                <tr>
                                                                                    <td height=1 class=delimetr01></td>
                                                                                </tr>
                                                                            <form action="index_login.php" method="POST">
                                                                                <tr>
                                                                                    <td align="center">
                                                                                        <input type="checkbox" name="disable" class=check>{$lang.DISABLE_REG}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height=1 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center">
                                                                                        <input type="hidden" name="cancel_rambler_registration" value="1">
                                                                                        <input type="submit" value="{$lang.CANCEL}" class=submit01><br>
                                                                                    </td>
                                                                                </tr>
                                                                            </form>
                                                                            </table>
                                                
                                                                        {else}
                                                                            
                                                                            <table width='100%' cellpadding='1' cellspacing='2' border='0'>
                                                                                <form method='post' action='index_login.php'>
                                                                                <input type='hidden' name='enter' value='Login'>
                                                                                <tr>
                                                                                    <td align="right">
                                                                                        <b>{$lang.USERNAME}</b>
                                                                                    </td>
                                                                                    <td align="left">
                                                                                        <input type='text' name='login' size='15' title="{$lang.TEXT4}">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="right">
                                                                                        <b>{$lang.PASSWORD}</b>
                                                                                    </td>
                                                                                    <td align="left">
                                                                                        <input type='password' name='pass' size='15' title="{$lang.TEXT4}">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td></td>
                                                                                    <td align="left">
                                                                                        <input type='submit' class="submit01" value='&nbsp;&nbsp;{$lang.LOGIN}&nbsp;&nbsp;' title="{$lang.TEXT4}">
                                                                                    </td>
                                                                                </tr>
                                                                                </form>
                                                                                <tr>
                                                                                    <td height=1 colspan=2 class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan=2 align=right>
                                                                                        <a href='{$lang.SENDPASSWORD_HREF}' target="_blank"><b>{$lang.SENDPASSWORD}</b></a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                            
                                                                        {/if}
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign=top>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                    <td width=100% align=center bgcolor="#ffffff">
                                                                        <p>{$lang.TEXT2} <b><SCRIPT language=JavaScript src="/sys_dyn_components/UserOnline.php"></SCRIPT></b> {$lang.TEXT3}</p>
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height=100% valign=top>
                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                    <td width=100% height=100% align=center valign=top bgcolor="#ffffff">
                                                                        <a href='/ds/index_registration.php'><img src="../img/buttons/but_register02.gif" alt="{$lang.REGISTRATE}" border=0></a><br /><br />
                                                                        <p>{$lang.TEXT10}</p>
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td valign=top>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=5>
                                                    <tr>
                                                        <td>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                    <td width=100% class=header_txt>{$lang.REG_TITLE}</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height=1 class=delimetr01></td>
                                                    </tr>
                                                    <tr>
                                                        <td height=100% align=center valign=top><img src="img/cabinet/graph/trio.jpg" border=0></td>
                                                    </tr>
                                                    <tr>
                                                        <td height=1 class=delimetr01></td>
                                                    </tr>
                                                    <tr>
                                                        <td height=100% valign=top>
                                                            {$lang.REG_TEXT}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height=1 class=delimetr01></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                                    <td width=100% class=header_txt>{$lang.SCREENSHOT_TITLE}</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align=center valign=top>
                                                            <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/t.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="img/cabinet/border/03/l.gif"></td>
                                                                    <td width=100% align=center bgcolor="#ffffff">
                                                                        <table width=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td align=center>
                                                                                    <img src="img/cabinet/screenshots/01_small.jpg" border=0></td>
                                                                                <td align=center>
                                                                                    <img src="img/cabinet/screenshots/02_small.jpg" border=0></td>
                                                                                <td align=center>
                                                                                    <img src="img/cabinet/screenshots/03_small.jpg" border=0></td>
                                                                                <td align=center>
                                                                                    <img src="img/cabinet/screenshots/04_small.jpg" border=0></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="img/cabinet/border/03/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                                                    <td background="img/cabinet/border/03/b.gif"></td>
                                                                    <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width=200 valign=top class=bg02>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td valign=top>
                                                            <table width=100% height=100% border=0 cellpadding=0 cellspacing=5>
                                                                <tr>
                                                                    <td style="padding-left: 3px;">
                                                                        <img src="img/cabinet/graph/a_02.gif" border=0></td>
                                                                    <td width=100% align=left class=header_txt>
                                                                        {$lang.NEWS_TITLE}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height=1 colspan=2 class=delimetr01></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height=100% valign=top style="padding-left: 5px; padding-right:5px;">
                                                            <table width=100% border=0 cellpadding=0 cellspacing=1>
                                                                <tr>
                                                                    <td align=left>
                                                                        <span style="color:#555555; font-size:8px;">[{$news.week.pubDate}]</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top>
                                                                        <span class=header_txt_02><img src="img/cabinet/graph/a_03.gif" width=8 height=8 border=0 style="margin-right: 2px;">{$news.week.title}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top>
                                                                        {$news.week.description}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=right>
                                                                        <a href="{$news.week.link}" target=_blank>‰‡Î¸¯Â</a>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height=1 class=delimetr01></td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=left>
                                                                        <span style="color:#555555; font-size:8px;">[{$news.news.pubDate}]</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top>
                                                                        <span class=header_txt_02><img src="img/cabinet/graph/a_03.gif" width=8 height=8 border=0 style="margin-right: 2px;">{$news.news.title}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top>
                                                                        {$news.news.description}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=right>
                                                                        <a href="{$news.news.link}" target=_blank>‰‡Î¸¯Â</a>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height=1 class=delimetr01></td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=left>
                                                                        <span style="color:#555555; font-size:8px;">[{$news.sphere.pubDate}]</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top>
                                                                        <span class=header_txt_02><img src="img/cabinet/graph/a_03.gif" width=8 height=8 border=0 style="margin-right: 2px;">{$news.sphere.title}</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top>
                                                                        {$news.sphere.description}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=right>
                                                                        <a href="{$news.sphere.link}" target=_blank>‰‡Î¸¯Â</a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end center-->
                            
                            <!--begin spaces-->
                            <tr>
                                <td height=3></td>
                            </tr>
                            <!--end spaces-->
                            
                            <!--begin line-->
                            <tr>
                                <td height=3 class=f_d01></td>
                            </tr>
                            <!--end line-->
                            
                            <!--begin footer-->
                            <tr>
                                <td>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td width=100% class=f_bg01>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('http://destinysphere.ru/?p=press');" class=b_menu><span style="position: relative; top: 20px;">œ–≈——¿</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('http://destinysphere.ru/?p=support_contact');" class=b_menu><span style="position: relative; top: 20px;"> ŒÕ“¿ “€</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                        <td align=center>
                                                            <div valign=middle onclick="openURL('http://destinysphere.ru/?p=about');" class=b_menu><span style="position: relative; top: 20px;">Œ Õ¿—</span></div></td>
                                                        <td width=2><img src="img/cabinet/btn/b_d01.gif" border=0></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td height=100% valign=top>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td height=22 valign=top><img height=22 src="img/cabinet/bg/bg_footer_t_l.gif" border=0></td>
                                                    </tr>
                                                    <tr>
                                                        <td align=right valign=middle class=copyrights background="img/cabinet/bg/bg_footer_b_l.gif">&nbsp;{$lang.COPYRIGHT}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <!--end footer-->
                        </table>
                    </td>
                    <td valign=top class=bg_right><img src="img/cabinet/bg/bg_right.jpg" border=0></td>
                </tr>
                <tr>
                    <td><img src="img/cabinet/bg/bg_b_l.gif" border=0></td>
                    <td background="img/cabinet/bg/bg_b_b.gif"></td>
                    <td><img src="img/cabinet/bg/bg_b_r.gif" border=0></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
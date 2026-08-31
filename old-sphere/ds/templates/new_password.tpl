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
                                <td width=860>
                                    <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="860" height="86" id="header_1" align="middle">
                                        <param name="allowScriptAccess" value="sameDomain">
                                        <param name="movie" value="../img/flash/header_01.swf?tip=2&urlpath=/&statpath=../sys_dyn_components/UserOnline.php%3Fmode%3Dp">
                                        <param name="quality" value="best">
                                        <param name="menu" value="false">
                                        <param name="settings" value="false">
                                        <param name="wmode" value="opaque">
                                        <param name="bgcolor" value="#ffffff">
                                        <embed src="../img/flash/header_01.swf?tip=2&urlpath=/&statpath=../sys_dyn_components/UserOnline.php%3Fmode%3Dp" quality="best" wmode="opaque" bgcolor="#ffffff" width="860" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
                                    </object></td>
                                </td>
                            </tr>
                            <!--end header-->
                            
                            
                            <!--begin center-->
                            <tr>
                                <td height=100% valign=top>
                                    <table width=100% height=50% border=0 cellpadding=0 cellspacing=5>
                                        <tr>
                                            <td align="center">
                                                <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td align="right"><img src="img/cabinet/graph/a_01.gif" border=0></td>
                                                        <td width=55% class=header_txt align="left">{$lang.CAPTION}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height=1 class=delimetr01></td>
                                        </tr>
                                        {if empty($MSG) || (!empty($MSG) && $MSG != 'NEW_PASSWORD_SEND' && $MSG != 'NEW_PASSWORD_ACTIVATED')}
                                        
                                        {if !empty($MSG)}
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
                                                            <font color='#DD0D1A'><b>{$lang.ERROR}</b>&nbsp;{$lang.$MSG}</font>
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
                                        <tr><td align="center">Для восстановления пароля введите ваш игровой логин ИЛИ е-мейл и нажмите кнопку "Восстановить".</td></tr>
                                        <tr>
                                            <td>
                                                <form action="remaind_pass.php" method="POST">
                                                <input type='hidden' name='lang' value='{$showlang}'>
                                                <table width=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr height="40">                                            
                                                        <td align="right" width="45%">
                                                            {$lang.LOGIN}:&nbsp;
                                                        </td>
                                                        <td align="left" width="55%">
                                                            <input type="text" name="login" value="">
                                                        </td>
                                                    </tr>
                                                    <tr height="40">
                                                        <td align="right">
                                                            {$lang.EMAIL}:&nbsp;
                                                        </td>
                                                        <td align="left">
                                                            <input type="text" name="e_mail" value="">
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height=1 class=delimetr01></td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <input type="hidden" name="action" value="change_login">
                                                <input type="submit" value="{$lang.SUBMIT}" class=submit01>
                                                </form>
                                            </td>
                                        </tr>
                                        {else}
                                        <tr>
                                            <td align="center">
                                                <font color='#DD0D1A'>&nbsp;{$lang.$MSG}</font>
                                            </td>
                                        </tr>
                                        {/if}
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
                                                        <!--<td align=center>
                                                            <div valign=middle onclick="openURL('http://ds.destinysphere.net/?p=about');" class=b_menu><span style="position: relative; top: 20px;">Impressum</span></div></td>-->
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
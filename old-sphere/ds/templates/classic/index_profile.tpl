<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<!-- <meta http-equiv="Refresh" content="1; URL=http://www.destinysphere.net"> -->
<meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
<meta http-equiv="Content-Style-Type" content="text/css">

<title>{$lang.TITLE}</title>
<link rel="stylesheet" href="../dsstyle.css" type="text/css">
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
<script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>
<body>
{literal}
<script language="Javascript" type="text/javascript">
<!--
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
//-->
</script>

<script language="Javascript" type="text/javascript">
<!--
function rollover(name, source)  {

  document.images[name].src = source;

}
//-->
</script>
{/literal}
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td>
            <table width=100% border=0 cellpadding=0 cellspacing=0>
                <!-- header begin -->
                <tr height=86>
                    <td valign=top>
                        <table width=100% height=86 border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=500>
                                    <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="500" height="86" id="header_1" align="middle">
                                        <param name="allowScriptAccess" value="sameDomain">
                                        <param name="movie" value="../img/flash/header.swf">
                                        <param name="quality" value="high">
                                        <param name="menu" value="false">
                                        <param name="settings" value="false">
                                        <param name="wmode" value="opaque">
                                        <param name="bgcolor" value="#ffffff">
                                        <embed src="../img/flash/header.swf" quality="high" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
                                    </object></td>
                                <td width=265><img src="../img/background/header_bg.jpg" border=0></td>
                                <td width=100% background="../img/background/header_bg2.gif"></td>  
                            </tr>
                        </table>    
                    <td>    
                </tr>
                <!--begin-->
                <tr height=3>
                    <td background="../img/background/buttons_bg.gif" align=center class=wnd_gen_txt></td>
                </tr>
                <!--end-->
                <tr height=5>
                    <td background="../img/background/bg.gif" align=center class=wnd_gen_txt></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td width=100% height=100% valign=middle align=center>
            <table border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td width=5 height=5><img src="../img/border/01/t_l.gif" border=0 width=5 height=5></td>
                    <td height=5 background="../img/border/01/t.gif"></td>
                    <td width=10 height=5><img src="../img/border/01/t_r.gif" border=0 width=10 height=5></td>
                </tr>
                <tr>
                    <td width=5 background="../img/border/01/l.gif"></td>
                    <td class=wnd_outer>
                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td colspan=3 align=center>
                                    <img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large' style="font-size:14px;""><b>{$lang.HEADER}</b></span></td>
                            </tr>
                            <tr>
                                <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                            </tr>
                            <tr>
                                <td width=4 background="../img/border/02/l.gif"></td>
                                <td width=100% class=wnd_inner>
                                    <FORM action="./index_profile.php" method="POST">
                                    <INPUT type="hidden" name="modified" value="true">
                                    <table width="100%" height="100%" border="0" cellpadding="2" cellspacing="1">
                                        <tr> 
                                            <td colspan=2 height='25' align="center" class='wnd_gen_txt_large' valign="middle">
                                            <font color="Red">{$ERROR_MESSAGE}</font>
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$lang.LOGIN_NAME} 
                                            </td>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$LOGIN_NAME_CURRRENT}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td  align="left" valign="top" class='wnd_gen_txt_large'>
                                                {$lang.USER_MAIL} 
                                            </td>
                                            <td  align="left" valign="top" class='wnd_gen_txt_large'>
                                                {$USER_MAIL_CURRRENT}
                                                <BR>
                                                <FONT size="-5"> {$lang.USER_MAIL_TEXT}</FONT>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$lang.REFLINK_TEXT} 
                                            </td>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$REFLINK}
                                            </td>
                                        </tr>                                       
                                        <tr>
                                            <td align="left" class='wnd_gen_txt_large'>
                                                {$lang.TIME_DELTA} 
                                            </TD>
                                            <td align="right" class='wnd_gen_txt_large'>
                                                 <SELECT name="time_delta" class=select_gen style="width:200px;">   
                                                        {foreach from=$timeZones key=myId item=i}   
                                                                {if $currentTimeZone == $i}
                                                                    <option value='{$i}' selected>
                                                                {else}
                                                                    <option value='{$i}'>
                                                                {/if}
                                                                <span class='wnd_gen_txt'>
                                                                {$myId}
                                                                </span>
                                                        </option>
                                                        {/foreach}
                                                 </SELECT>
                                            </TD>
                                        </tr>
                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$lang.USERS_LANGUAGE} 
                                            </td>
                                            <td  align="right" class='wnd_gen_txt_large'>
                                                 <SELECT name="lang" class=select_gen style="width:200px;">
                                                        {foreach from=$userLanguages key=k item=i}  
                                                                {if $currentUserLanguage == $i.1}
                                                                    <option value='{$k}' selected>
                                                                {else}
                                                                    <option value='{$k}'>
                                                                {/if}
                                                                <span class='wnd_gen_txt'>
                                                                    {$i.0}
                                                                </span>
                                                        </option>
                                                        {/foreach}
                                                </SELECT>
                                            </td>
                                        </tr>
                                        <tr>
                                        {$SCALING_1}
                                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                    {$lang.SCALING}
                                            </td>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                    <INPUT type="checkbox" name="scaling" {$SCALING_ON}>
                                            </td>
                                        </tr>
                                        {$SCALING_2}
                                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$lang.OLD_PASSWORD} 
                                            </td>
                                            <td  align="right" class='wnd_gen_txt_large'>
                                                <INPUT type="password" name="old_password" size="20" maxlength="50">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$lang.NEW_PASSWORD} 
                                            </td>
                                            <td  align="right" class='wnd_gen_txt_large'>
                                                <INPUT type="password" name="new_password" size="20" maxlength="50">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td  align="left" class='wnd_gen_txt_large'>
                                                {$lang.CONFIRM_PASSWORD} 
                                            </td>
                                            <td  align="right" class='wnd_gen_txt_large'>
                                                <INPUT type="password" name="confirm_password" size="20" maxlength="50">
                                            </td>
                                        </tr>
                                            <td  colspan="2" align="center" class='wnd_gen_txt_large'>
                                                <INPUT type="submit"  class="input_button"  value="{$lang.BUTTON_TEXT}">
                                            </td>
                                        </tr>
                                        </tr>
                                            <td  colspan="2" align="center">
                                                <font size="-4"><A href="{$FORUM_PROFILE_LINK}" class="wnd_gen_txt_large">{$lang.FORUM_PROFILE_TEXT}</A></font>
                                            </td>
                                        </tr>
                                    </table>
                                    </FORM>
                                </td>
                                <td width=4 background="../img/border/02/r.gif"></td>
                            </tr>
                            <tr>
                                <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                            </tr>
                        </table>                        
                    </td>
                    <td width=10 background="../img/border/01/r.gif"></td>
                </tr>
                <tr>
                    <td width=5 height=10><img src="../img/border/01/b_l.gif" border=0 width=5 height=10></td>
                    <td height=10 background="../img/border/01/b.gif"></td>
                    <td width=10 height=10><img src="../img/border/01/b_r.gif" border=0 width=10 height=10></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
{include file='counters.php'}
</body>
</html>             

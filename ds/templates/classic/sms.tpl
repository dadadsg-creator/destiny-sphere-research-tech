<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <META HTTP-EQUIV="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="post-check=0, pre-check=0">
    <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
    <META NAME="Last-Modified" CONTENT="gmdate("D, d M Y H:i:s") GMT">
    <title>{$lang.TITLE}</title>
    <link rel="stylesheet" href="cabinetstyle2.css" type="text/css">
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
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td height=100% align=center valign=top>
            <table width=834 border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td>
                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td background="img/cab/c_l.gif">
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td height=100% valign=top><img src="img/cab/c_l_t.gif" border=0></td>
                                        </tr>
                                        <tr>
                                            <td valign=bottom><img src="img/cab/c_l_b.jpg" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width=100%>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                            
                                    <!-- header begin -->
                                    {php}
                                    require_once  $_SERVER['DOCUMENT_ROOT']."/ds/templates/header.tpl";
                                    {/php}
                                    <!-- header end -->
                            
                                        <tr>
                                            <td height=100% valign=top bgcolor=#3e456a>
                                                <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                
                                                    {if !empty($MSG)}
                                                    <tr>
                                                        <td colspan=2>
                                                            <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/05/t_l.gif" border=0></td>
                                                                    <td height=13 align=left background="../img/v4/borders/05/t.gif"><img src="../img/v4/borders/05/t_l_l.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/05/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top background="../img/v4/borders/05/l.gif"><img src="../img/v4/borders/05/l_t.jpg" border=0></td>
                                                                    <td width=100% align=center bgcolor="#ffffff">
                                                                        <table border=0 cellpadding=0 cellspacing=0>
                                                                            <tr width=100%>
                                                                                <td style="padding:1px;" align=right><img src="img/cabinet/icons/alert.gif" border=0></td>
                                                                                <td style="padding:1px;" align=left><span class=sys_message_error>{$lang.$MSG}</span></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td valign=bottom background="../img/v4/borders/05/r.gif"><img src="../img/v4/borders/05/r_b.jpg" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/05/b_l.gif" border=0></td>
                                                                    <td height=13 align=right background="../img/v4/borders/05/b.gif"><img src="../img/v4/borders/05/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/05/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    {/if}
                                                    <tr>
                                                        <!--BEGIN LEFT FRAME-->
                                                        <td valign=top style="padding-left:2px; padding-right:2px;">
                                                            <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                <!--1-->
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/05/t_l.gif" border=0></td>
                                                                    <td height=13 align=left background="../img/v4/borders/05/t.gif"><img src="../img/v4/borders/05/t_l_l.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/05/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td valign=top background="../img/v4/borders/05/l.gif"><img src="../img/v4/borders/05/l_t.jpg" border=0></td>
                                                                    <td width=100% class=bg03 style="padding:2px;">
                                                                        <table width=100% cellpadding=0 cellspacing=1 border=0>
                                                                        <tr>
                                                                                <td>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr align="center">
                                                                                        <form name="choose_country" action="sms.php" method="POST">
                                                                                            <td width=100% align="center" class=header_txt>
                                                                                            {$lang.COUNTRY}
                                                                                            <input type="hidden" name="action" value="CHOOSE_COUNTRY">
                                                                                            <select name='country' onChange="document.forms['choose_country'].submit();">
                                                                                            <option disabled>{$lang.CHOOSE_COUNTRY}</option>
                                                                                            {foreach from=$countries key=k item=country}
                                                                                            {if $country.id_country == $selectedCountry}
                                                                                                <option value='{$country.id_country}' selected>{$country.country}</option>
                                                                                            {else}
                                                                                                <option value='{$country.id_country}'>{$country.country}</option>
                                                                                            {/if}
                                                                                            {/foreach}
                                                                                            </select>
                                                                                            </td>
                                                                                            </form>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01_5></td>
                                                                            </tr>   
                                                                        {foreach from=$servises key=k item=servise}
                                                                            <tr>
                                                                                <td>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt>
                                                                                                <span style="padding-left:5px; position: relative; top: -3px;">
                                                                                                {if $ACTION == 'CHOOSE_COUNTRY'}
                                                                                                <a href="javascript:alert('{$lang.CHOOSE_COUNTRY}');">{$servise}</a>
                                                                                                {else}
                                                                                                <a href="sms.php?action={$servise}&country={$selectedCountry}">{$lang.$servise}</a>
                                                                                                {/if}
                                                                                                </span>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01_5></td>
                                                                            </tr>
                                                                        {/foreach}
                                                                        </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                            
                                                                    <td valign=bottom background="../img/v4/borders/05/r.gif"><img src="../img/v4/borders/05/r_b.jpg" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/05/b_l.gif" border=0></td>
                                                                    <td height=13 align=right background="../img/v4/borders/05/b.gif"><img src="../img/v4/borders/05/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/05/b_r.gif" border=0></td>
                                                                </tr>

                                                            </table>
                                                        </td>
                                                        <!--END LEFT FRAME-->
                                                        
                                                        <!--BEGIN RIGHT FRAME-->
                                                        <td width=575 valign=top height="100%">
                                                            <table width=575 height="100%" cellpadding=0 cellspacing=0 border=0>
                                                                <tr><td height="4"></tr>
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/t_l.gif" border=0></td>
                                                                    <td height=11 align=right background="../img/v4/borders/04/t.gif"><img src="../img/v4/borders/04/t_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/t_r.gif" border=0></td>
                                                                </tr>
                                                                
                                                                <tr>
                                                                    <td background="../img/v4/borders/04/l.gif"></td>
                                                                    <td width=100% height="100%" class=bg04 style="padding:2px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                        {if $ACTION == 'CHOOSE_COUNTRY'}
                                                                        <tr align="center">
                                                                            <td>
                                                                                <table>
                                                                                    <tr>
                                                                                        <td align="center" colspan="{$countries|@count}" height="100%">
                                                                                            <h6>{$lang.CHOOSE_COUNTRY}</h6>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr align="center" valign="middle">
                                                                                        {foreach from=$countries key=k item=country}
                                                                                        <td align="center"><a href="sms.php?action=CHOOSE_COUNTRY&country={$country.id_country}" title="{$country.country}"><img border="0" src="./images/flags/{$country.id_country}.gif"></a></td>
                                                                                        {/foreach}
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
        
                                                                        {else}
                                                                            <tr>
                                                                                <td width=100%>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="./img/cabinet/graph/a_01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt>
                                                                                                {$lang.$ACTION}
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width=100% align=left style="padding:1px;">
                                                                            {if $ACTION == 'PREMIUM'}
                                                                                {if $number == '5373'}
                                                                                    <br>{$lang.DESC_PREMIUM_UKR}<br><br>
                                                                                {else}
                                                                                    <br>{$lang.DESC_PREMIUM}<br><br>
                                                                                {/if}
                                                                                </td>
                                                                            </tr>
                                                                            {if $QUESTION == 'YES'}
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    {$lang.GET_FREE_PICTURE}<br><br>
                                                                                </td>
                                                                            </tr>
                                                                            <form action="sms.php" method="POST">
                                                                                <input type="hidden" name="action" value="{$ACTION}">
                                                                                <input type="hidden" name="country" value="{$selectedCountry}">
                                                                            <tr>
                                                                                <td>
                                                                                    <table width="100%">
                                                                                    <tr>
                                                                                        <td align="right">
                                                                                            <input type="submit" name="WITHOUT_PICTURE" class="submit01" value="{$lang.NO}">
                                                                                        </td>
                                                                                        <td width="10%">
                                                                                        </td>
                                                                                        <td align="left">
                                                                                            <input type="submit" name="WITH_PICTURE" class="submit01" value="{$lang.YES}">
                                                                                        </td>
                                                                                    <tr>
                                                                                    </table>
                                                                                    </td>
                                                                                </td>
                                                                            </tr>
                                                                            </form>
                                                                            {/if} 
                                                                                {if !empty($smsText)}
                                                                                <tr>
                                                                                    <td class=delimetr01></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td><br></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <table border="1" align="center">
                                                                                        <tr>
                                                                                            <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="center" colspan="2">
                                                                                                {$lang.SEND_MESSAGE}
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.ON_NUMBER}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$number}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.WITH_TEXT}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$smsText}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.COST}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$COAST} {if $SHOW_SMS_PORTAL == 'yes'}*{/if}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                         </table>
                                                                                             </td>
                                                                                        </tr>       
                                                                                         </table>
                                                                                     </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td><br></td>
                                                                                </tr>
                                                                                {if $SHOW_SMS_PORTAL == 'yes'}
                                                                                <tr>
                                                                                    <td>
                                                                                        * {$lang.SMS_PORTAL}
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                <tr>
                                                                                    <td><br></td>
                                                                                </tr>
                                                                                {/if}
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                {if $number == '5373'}
                                                                                    {$lang.PREMIUM_FOOTER_UKR}<br>
                                                                                {else}
                                                                                    {$lang.PREMIUM_FOOTER}<br>
                                                                                {/if}
                                                                                
                                                                                

                                                                        {elseif $ACTION == 'ARMY'}
                                                                                <br>{$lang.DESC_ARMY}<br><br>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                <form action="sms.php" method="POST">
                                                                                <input type="hidden" name="action" value="{$ACTION}">
                                                                                <input type="hidden" name="viewSms" value="Yes">
                                                                                <input type="hidden" name="country" value="{$selectedCountry}">
                                                                                    
                                                                                    <br>{$lang.CHOOSE_GAME_WORLD}
                                                                                    <select name='world'>
                                                                                    {foreach from=$worlds key=k item=world}
                                                                                        {if $selected_world == $world.world_identifier}
                                                                                        <option value='{$world.world_identifier}' selected>{$world.worldname}</option>
                                                                                        {else}
                                                                                        <option value='{$world.world_identifier}'>{$world.worldname}</option>
                                                                                        {/if}
                                                                                    {/foreach}
                                                                                    </select>
                                                                                </td>
                                                                             </tr> 
                                                                             <tr>
                                                                                <td align="center">
                                                                                <br><input type="submit" class="submit04" value="{$lang.SHOW_SMS_TEXT}"><br><br>
                                                                                 </form>
                                                                                </td>
                                                                            </tr>
                                                                            {if !empty($smsText)}
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><br></td>
                                                                            </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <table border="1" align="center">
                                                                                        <tr>
                                                                                            <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="center" colspan="2">
                                                                                                {$lang.SEND_MESSAGE}
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.ON_NUMBER}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$number}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.WITH_TEXT}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$smsText}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.COST}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$COAST} {if $SHOW_SMS_PORTAL == 'yes'}**{/if}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                         </table>
                                                                                             </td>
                                                                                        </tr>       
                                                                                         </table>
                                                                                     </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <br><b>* {$lang.ENTER_PASSWOD}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {if $SHOW_SMS_PORTAL == 'yes'}
                                                                                <tr>
                                                                                    <td>
                                                                                        <b>** {$lang.SMS_PORTAL}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                {/if}
                                                                                
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <br>{$lang.ARMY_FOOTER}<br><br>
                                                                                
                                                                        {elseif $ACTION == 'AINF'}
                                                                                <br>{$lang.DESC_AINF}
                                                                                <br><br>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                <form action="sms.php" method="POST">
                                                                                <input type="hidden" name="action" value="{$ACTION}">
                                                                                <input type="hidden" name="viewSms" value="Yes">
                                                                                <input type="hidden" name="country" value="{$selectedCountry}">
                                                                                    <br>{$lang.CHOOSE_GAME_WORLD}
                                                                                    <select name='world'>
                                                                                    {foreach from=$worlds key=k item=world}
                                                                                        {if $selected_world == $world.world_identifier}
                                                                                        <option value='{$world.world_identifier}' selected>{$world.worldname}</option>
                                                                                        {else}
                                                                                        <option value='{$world.world_identifier}'>{$world.worldname}</option>
                                                                                        {/if}
                                                                                    {/foreach}
                                                                                    </select>
                                                                                </td>
                                                                            </tr> 
                                                                            <tr>
                                                                                <td align="center">     
                                                                                 <br><input type="submit" class="submit04" value="{$lang.SHOW_SMS_TEXT}"><br><br>
                                                                                 </form>
                                                                                </td>
                                                                            </tr>
                                                                            {if !empty($smsText)}
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><br></td>
                                                                            </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <table border="1" align="center">
                                                                                        <tr>
                                                                                            <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="center" colspan="2">
                                                                                                {$lang.SEND_MESSAGE}
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.ON_NUMBER}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$number}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.WITH_TEXT}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$smsText}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.COST}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$COAST} {if $SHOW_SMS_PORTAL == 'yes'}**{/if}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                         </table>
                                                                                             </td>
                                                                                        </tr>       
                                                                                         </table>
                                                                                     </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <br><b>* {$lang.ENTER_PASSWOD}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {if $SHOW_SMS_PORTAL == 'yes'}
                                                                                <tr>
                                                                                    <td>
                                                                                        <b>** {$lang.SMS_PORTAL}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                {/if}
                                                                                
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <br>{$lang.AINF_FOOTER}<br><br>
                                                                                
                                                                    {elseif $ACTION == 'ARCH'}
                                                                                <br>{$lang.DESC_ARCH}<br><br>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                <form action="sms.php" name="worlds" method="POST">
                                                                                <input type="hidden" name="action" value="{$ACTION}">
                                                                                <input type="hidden" name="viewSms" value="Yes">
                                                                                <input type="hidden" name="country" value="{$selectedCountry}">
                                                                                    <tr>
                                                                                        <td>
                                                                                        <table height=100% border=0 cellpadding=5 cellspacing=1>
                                                                                            <tr>
                                                                                            <td> 
                                                                                                {$lang.CHOOSE_GAME_WORLD}
                                                                                            </td> 
                                                                                            <td> 
                                                                                                <select name='world'>
                                                                                                {foreach from=$worlds key=k item=world}
                                                                                                {if $selected_world == $world.world_identifier}
                                                                                                <option value='{$world.world_identifier}' selected>{$world.worldname}</option>
                                                                                                {else}
                                                                                                <option value='{$world.world_identifier}'>{$world.worldname}</option>
                                                                                                {/if}
                                                                                                {/foreach}
                                                                                                </select>
                                                                                            </td>
                                                                                         </tr>
                                                                                         <tr>
                                                                                            <td> 
                                                                                                {$lang.SEND_TIME} 
                                                                                            </td> 
                                                                                            <td> 
                                                                                                <select name='time'>
                                                                                                {foreach from=$time key=k item=t}
                                                                                                    {if $t == $selectedTime}
                                                                                                        <option value='{$t}' selected>{$t}</option>
                                                                                                    {else}
                                                                                                        <option value='{$t}'>{$t}</option>  
                                                                                                    {/if}
                                                                                                {/foreach}
                                                                                                </select>
                                                                                            </td>
                                                                                         </tr>
                                                                                         <tr>
                                                                                            <td>
                                                                                                {$lang.GPOUP_SIZE}
                                                                                            </td> 
                                                                                            <td> 
                                                                                                <input type="text" name="groupSize" size="5" maxlength="10" value="{$selectedGroupSize}">
                                                                                            </td>
                                                                                         </tr>
                                                                                         <tr>
                                                                                            <td>
                                                                                                Сота :
                                                                                            </td> 
                                                                                            <td>  
                                                                                                <select name='comb'>
                                                                                                {foreach from=$comb key=k item=c}
                                                                                                    {if $k == $selectedComb}
                                                                                                        <option value='{$k}' selected>{$c}</option>
                                                                                                    {else}
                                                                                                        <option value='{$k}'>{$c}</option>  
                                                                                                    {/if}
                                                                                                {/foreach}
                                                                                                </select>
                                                                                            </td>
                                                                                         </tr>
                                                                                    </table>
                                                                                 </td> 
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <br><input type="submit" class="submit04" value="{$lang.SHOW_SMS_TEXT}"><br><br>
                                                                                 </td> 
                                                                                 </form>    
                                                                            </tr>
                                                                            {if !empty($smsText)}
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><br></td>
                                                                            </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <table border="1" align="center">
                                                                                        <tr>
                                                                                            <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="center" colspan="2">
                                                                                                {$lang.SEND_MESSAGE}
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.ON_NUMBER}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$number}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.WITH_TEXT}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$smsText}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.COST}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$COAST} {if $SHOW_SMS_PORTAL == 'yes'}**{/if}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                         </table>
                                                                                             </td>
                                                                                        </tr>       
                                                                                         </table>
                                                                                     </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <br><b>* {$lang.ENTER_PASSWOD}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {if $SHOW_SMS_PORTAL == 'yes'}
                                                                                <tr>
                                                                                    <td>
                                                                                        <b>** {$lang.SMS_PORTAL}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                {/if}
                                                                                
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <br>{$lang.ARCH_FOOTER}<br><br>
                                                                                
                                                        {elseif $ACTION == 'ART'}
                                                                                <br>{$lang.DESC_ART}<br><br>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><br>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                <form action="sms.php" name="worlds" method="POST">
                                                                                <input type="hidden" name="action" value="{$ACTION}">
                                                                                <input type="hidden" name="viewSms" value="Yes">
                                                                                <input type="hidden" name="country" value="{$selectedCountry}">
                                                                                    {$lang.CHOOSE_GAME_WORLD}
                                                                                    {literal}
                                                                                    <select name="world" onChange="MkHouseValues(this.selectedIndex)">
                                                                                    {/literal}
                                                                                    {foreach from=$userWorlds key=k item=world}
                                                                                    {if $selected_world == $world.world_identifier}
                                                                                        <option value='{$world.world_identifier}' selected>{$world.worldName}</option>
                                                                                    {else}
                                                                                        <option value='{$world.world_identifier}'>{$world.worldName}</option>
                                                                                    {/if}
                                                                                    {/foreach}
                                                                                    </select>
                                                                                    {$lang.COMB}
                                                                                    <select name="comb">
                                                                                    <option value="{$SELECTED_COMB}">{$SELECTED_COMB}</option>
                                                                                    </select>
                                                                                  {literal}
                                                                                <script type="text/javascript">
                                                                                <!--{/literal}
                                                                                // далее у нас следует массив из улиц, содержащих дома, разделённые запятой
                                                                                {assign var=comma value=''}
                                                                                var aHouseValues = new Array(
                                                                                {foreach from=$combs key=k item=comb}
                                                                                {$comma}"{$comb}"
                                                                                {assign var=comma value=','}
                                                                                {/foreach});
                                                                                
                                                                                {literal}
                                                                                
                                                                                var flag = 0;
                                                                                var k = 0;
                                                                                
                                                                                // ф-ция, возвращающая массив домов по заданной улице
                                                                                function getHouseValuesByStreet(index){
                                                                                    var sHouseValues = aHouseValues[index];
                                                                                    return sHouseValues.split(","); // преобразуем строку в массив домов
                                                                                }
                                                                                
                                                                                // главная ф-ция, выводящая динамически список домов
                                                                                function MkHouseValues(index){
                                                                                    document.worlds.comb.selectedIndexs = 0;
                                                                                    var aCurrHouseValues = getHouseValuesByStreet(index);
                                                                                    var nCurrHouseValuesCnt = aCurrHouseValues.length;
                                                                                    var oHouseList = document.forms["worlds"].elements["comb"];
                                                                                    var oHouseListOptionsCnt = oHouseList.options.length;
                                                                                    oHouseList.length = 0; // удаляем все элементы из списка домов
                                                                                    for (i = 0; i < nCurrHouseValuesCnt; i++){
                                                                                        // далее мы добавляем необходимые дома в список
                                                                                        if (document.createElement){
                                                                                            var newHouseListOption = document.createElement("OPTION");
                                                                                            newHouseListOption.text = aCurrHouseValues[i];
                                                                                            newHouseListOption.value = aCurrHouseValues[i];
                                                                                            // тут мы используем для добавления элемента либо метод IE, либо DOM, которые, alas, не совпадают по параметрам…
                                                                                            (oHouseList.options.add) ? oHouseList.options.add(newHouseListOption) : oHouseList.add(newHouseListOption, null);
                                                                                            
                                                                                            if (aCurrHouseValues[i] == "{/literal}{$SELECTED_COMB}{literal}")
                                                                                            {
                                                                                                 k = i;
                                                                                            }
                                                                                            
                                                                                        }else{
                                                                                            // для NN3.x-4.x
                                                                                            oHouseList.options[i] = new Option(aCurrHouseValues[i], aCurrHouseValues[i], false, false);
                                                                                        }
                                                                                    }
                                                                                    flag = 1;
                                                                                }
                                                                                
                                                                                    if (flag == 0)
                                                                                    {
                                                                                        // инициируем изменение элементов в списке домов, в зависимости от текущей улицы
                                                                                        MkHouseValues(document.forms["worlds"].elements["world"].selectedIndex);
                                                                                        flag = 1;
                                                                                    }
                                                                                    
                                                                                    if("{/literal}{$SELECTED_COMB}{literal}" != 0)
                                                                                    {
                                                                                        document.worlds.comb.selectedIndex = k;
                                                                                    }
                                                                                    
                                                                                    //-->
                                                                                    </script>
                                                                                    {/literal}
                                                                                </td>
                                                                             </tr> 
                                                                             </tr> 
                                                                             <tr>
                                                                                <td align="center">
                                                                                     <br><input type="submit" class="submit04" value="{$lang.SHOW_SMS_TEXT}"><br><br>
                                                                                     </form>
                                                                                    </td> 
                                                                                </tr> 
                                                                                </td>
                                                                            </tr>
                                                                            {if !empty($smsText)}
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><br></td>
                                                                            </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <table border="1" align="center">
                                                                                        <tr>
                                                                                            <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="center" colspan="2">
                                                                                                {$lang.SEND_MESSAGE}
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.ON_NUMBER}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$number}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.WITH_TEXT}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$smsText}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.COST}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$COAST} {if $SHOW_SMS_PORTAL == 'yes'}**{/if}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                         </table>
                                                                                             </td>
                                                                                        </tr>       
                                                                                         </table>
                                                                                     </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <br><b>* {$lang.ENTER_PASSWOD}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {if $SHOW_SMS_PORTAL == 'yes'}
                                                                                <tr>
                                                                                    <td>
                                                                                        <b>** {$lang.SMS_PORTAL}</b><br><br>
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                {/if}
                                                                                            
                                                    {elseif $ACTION == 'CREDIT' || $ACTION == 'CREDIT500' || $ACTION == 'CREDIT2500' || $ACTION == 'CREDIT1' || $ACTION == 'CREDIT2' || $ACTION == 'CREDIT3'}
                                                                                <br>{$lang.DESC_CREDIT} <b>{$SUM} {$lang.$CREDITS}</b><br><br>
                                                                                </td>
                                                                            </tr>
                                                                            {if !empty($smsText)}
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                                <tr>
                                                                                    <td><br>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td>
                                                                                        <table border="1" align="center">
                                                                                        <tr>
                                                                                            <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="center" colspan="2">
                                                                                                {$lang.SEND_MESSAGE}
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.ON_NUMBER}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$number}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.WITH_TEXT}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$smsText}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                {$lang.COST}
                                                                                                </td>
                                                                                                <td>
                                                                                                <b>{$COAST} {if $SHOW_SMS_PORTAL == 'yes'}*{/if}</b>
                                                                                                </td>
                                                                                            </tr>
                                                                                         </table>
                                                                                          </td>
                                                                                        </tr>       
                                                                                    </table>
                                                                                <tr>
                                                                                    <td>
                                                                                        <br>
                                                                                     </td>
                                                                                </tr>
                                                                                {if $SHOW_SMS_PORTAL == 'yes'}
                                                                                <tr>
                                                                                    <td>
                                                                                        * {$lang.SMS_PORTAL}
                                                                                    </td>
                                                                                </tr>
                                                                                {/if}
                                                                                <tr>
                                                                                    <td>
                                                                                        <br>
                                                                                     </td>
                                                                                </tr>
                                                                                {/if}
                                                                                <tr>
                                                                                    <td><br><br><br>
                                                                                    </td>
                                                                                </tr>
                            
                                                
                                                {/if}
                                                        {/if}                       
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="../img/v4/borders/04/r.gif"></td>
                                                                    <td width=4">&nbsp;&nbsp;</td>
                                                                </tr>
                                                                
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/b_l.gif" border=0></td>
                                                                    <td height=12 align=right background="../img/v4/borders/04/b.gif"><img src="../img/v4/borders/04/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/b_r.gif" border=0></td>
                                                                </tr>
                                                            </table><br><br>
                                                        </td>
                                                        <!--END RIGHT FRAME-->
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height=23 background="img/cab/c_b.gif">
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    <tr>
                                                        <td align=left><img src="img/cab/c_b_l.jpg" border=0></td>
                                                        <td align=right><img src="img/cab/c_b_r.jpg" border=0></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td background="img/cab/c_r.gif">
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td height=100% valign=top><img src="img/cab/c_r_t.gif" border=0></td>
                                        </tr>
                                        <tr>
                                            <td valign=bottom><img src="img/cab/c_r_b.jpg" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align=center><b>{$lang.COPYRIGHT}</b></td>
                </tr>
            </table>
        </td>
    </tr>   
</table>
{include file='counters.php'}
</body>
</html>
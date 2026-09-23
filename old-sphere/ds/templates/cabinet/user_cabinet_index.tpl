<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <title>{$lang.TITLE}</title>
    <link rel="stylesheet" href="cabinetstyle2.css" type="text/css">
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>

<body>
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0 class=bg_top>
    <tr>
        <td height=100% align=center valign=top>
            <table width=834 border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td>
                        <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="834" height="318" id="cab_header" align="middle">
                            <param name="allowScriptAccess" value="sameDomain" />
                            <param name="allowFullScreen" value="false" />
                            <param name="movie" value="img/cab/{$prefix}cab_header.swf" />
                            <param name="loop" value="false" />
                            <param name="menu" value="false" />
                            <param name="quality" value="best" />
                            <param name="wmode" value="transparent" />
                            <param name="bgcolor" value="#333333" />
                            <embed src="img/cab/{$prefix}cab_header.swf" loop="false" menu="false" quality="best" wmode="transparent" bgcolor="#333333" width="834" height="318" name="cab_header" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
                        </object>
                    </td>
                </tr>
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
                                        <tr>
                                            <td height=100% valign=top bgcolor=#3e456a>
                                                <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                    {if key_exists('USER', $PARAMS) && count($PARAMS.USER) > 0}
                                                        {if $PARAMS.USER.IS_ACTIVE}
                                                            {if !empty($PARAMS.MSG)}
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
                                                                                        {if $PARAMS.MSG_TYPE == 'OK'}
                                                                                            <td style="padding:1px;" align=right><img src="img/cabinet/icons/info.gif" border=0></td>
                                                                                            <td style="padding:1px;" align=left><span class=sys_message_ok>{$PARAMS.MSG}</span></td>
                                                                                        {else}
                                                                                            <td style="padding:1px;" align=right><img src="img/cabinet/icons/alert.gif" border=0></td>
                                                                                            <td style="padding:1px;" align=left><span class=sys_message_error>{$PARAMS.MSG}</span></td>
                                                                                        {/if}
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
                                                        
                                                    
                                                        <!--BEGIN RIGHT FRAME-->
                                                        <td width="65%" valign=top>
                                                            <table width="100%" cellpadding=0 cellspacing=0 border=0>   
                                                                                                                                                                                    
                                                                <!--Payment-Servces-->
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/t_l.gif" border=0></td>
                                                                    <td height=11 align=right background="../img/v4/borders/04/t.gif"><img src="../img/v4/borders/04/t_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="../img/v4/borders/04/l.gif"></td>
                                                                    <td width=100% class=bg04 style="padding:2px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td width=100%>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.PAYMENT_SERVICES}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width=100% align=center style="padding:1px;">
                                                                                    <a href="user_cabinet.php?action=payment_services">
                                                                                    <img src="../img/cabinet/icons/payment_services.jpg" border="0" height="194" width="274"><br>
                                                                                    <span style="font-size:14px; color:#5d4e86;">{$lang.GO_TO_PAYMENT_SERVICE}</span>
                                                                                    </a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="../img/v4/borders/04/r.gif"></td>
                                                                </tr>                                                               
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/b_l.gif" border=0></td>
                                                                    <td height=12 align=right background="../img/v4/borders/04/b.gif"><img src="../img/v4/borders/04/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/b_r.gif" border=0></td>
                                                                </tr>
                                                                <!--End_Of_Payment-Servces-->
                                                                
                                                                <!--SMS-Servces-->
                                                                <!--<tr>
                                                                    <td><img src="../img/v4/borders/04/t_l.gif" border=0></td>
                                                                    <td height=11 align=right background="../img/v4/borders/04/t.gif"><img src="../img/v4/borders/04/t_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="../img/v4/borders/04/l.gif"></td>
                                                                    <td width=100% class=bg04 style="padding:2px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td width=100%>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.SMS}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <form action="sms.php" method="POST">
                                                                                <td width=100% align="center" style="padding:1px;">
                                                                                    <input type="submit" value="{$lang.GO}" class=submit01>
                                                                                </td>
                                                                                </form>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="../img/v4/borders/04/r.gif"></td>
                                                                </tr>                                                               
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/b_l.gif" border=0></td>
                                                                    <td height=12 align=right background="../img/v4/borders/04/b.gif"><img src="../img/v4/borders/04/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/b_r.gif" border=0></td>
                                                                </tr>-->
                                                                <!--End of SMS-Servces-->
                                                                
                                                                <!--GLOBAL ALLIANCES-->
                                                                <!--<tr>
                                                                    <td><img src="../img/v4/borders/04/t_l.gif" border=0></td>
                                                                    <td height=11 align=right background="../img/v4/borders/04/t.gif"><img src="../img/v4/borders/04/t_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="../img/v4/borders/04/l.gif"></td>
                                                                    <td width=100% class=bg04 style="padding:2px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td width=100%>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.GLOBAL_ALLINCE_CONTROL}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width=100% align=center style="padding:1px;">
                                                                                    <a href="user_cabinet.php?action=global_alliance">{$lang.GO_TO_CONTROL_PANEL}<br>>>></a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="../img/v4/borders/04/r.gif"></td>
                                                                </tr>                                                               
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/b_l.gif" border=0></td>
                                                                    <td height=12 align=right background="../img/v4/borders/04/b.gif"><img src="../img/v4/borders/04/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/b_r.gif" border=0></td>
                                                                </tr>-->
                                                                <!--END OF GLOBAL ALLIANCES-->
                                                                
                                                                <!--PROFILE-->
                                                                <!--<tr>
                                                                    <td><img src="../img/v4/borders/04/t_l.gif" border=0></td>
                                                                    <td height=11 align=right background="../img/v4/borders/04/t.gif"><img src="../img/v4/borders/04/t_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="../img/v4/borders/04/l.gif"></td>
                                                                    <td width=100% class=bg04 style="padding:2px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td width=100%>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.PROFILE}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width=100% align="left" style="padding:1px;">
                                                                                    {$lang.EDIT_PROFILE}
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <form action="" method="POST" name="exhangeForm">
                                                                                <input type="hidden" name="action" value="user_profile">
                                                                                <td width=100% align="right" style="padding:1px;">
                                                                                    <input type="submit" value="{$lang.GO}" class=submit01>
                                                                                </td>
                                                                                </form>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="../img/v4/borders/04/r.gif"></td>
                                                                </tr>                                                               
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/b_l.gif" border=0></td>
                                                                    <td height=12 align=right background="../img/v4/borders/04/b.gif"><img src="../img/v4/borders/04/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/b_r.gif" border=0></td>
                                                                </tr>-->
                                                                <!--END OF PROFILE-->
                                                                
                                                                <!--LAST OPERATIONS-->
                                                                {if key_exists('ACCOUNT_OPS', $PARAMS)}
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/t_l.gif" border=0></td>
                                                                    <td height=11 align=right background="../img/v4/borders/04/t.gif"><img src="../img/v4/borders/04/t_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/t_r.gif" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td background="../img/v4/borders/04/l.gif"></td>
                                                                    <td width=100% class=bg04 style="padding:2px;">
                                                                        <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                            <tr>
                                                                                <td width=100%>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.LAST_OPERATIONS}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width=100% align=center style="padding:1px;">
                                                                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=2>
                                                                                        {foreach from=$PARAMS.ACCOUNT_OPS item=STAT}
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/cap_div.gif" border=0> {$STAT.DATE}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>{$STAT.ACTION}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>{$STAT.INFO}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td class=delimetr01></td>
                                                                                        </tr>
                                                                                        {/foreach}
                                                                                        <tr>
                                                                                            <td align=right><a href="javascript:openURL('user_cabinet.php?action=account_stat');">{$lang.ALL_STAT}</a></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td background="../img/v4/borders/04/r.gif"></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/04/b_l.gif" border=0></td>
                                                                    <td height=12 align=right background="../img/v4/borders/04/b.gif"><img src="../img/v4/borders/04/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/04/b_r.gif" border=0></td>
                                                                </tr>
                                                                {/if}
                                                                <!--END OF LAST OPERATIONS-->
                                                        </table>
                                                    </td>
                                                    <!--END RIGHT FRAME-->
                                                    <!--BEGIN LEFT FRAME-->
                                                        <td valign=top style="padding-left:2px; padding-right:2px;">
                                                            <table width=100% cellpadding=0 cellspacing=0 border=0>
<tr><td colspan=3><div style="background:#EBEDF1;border:1px solid #A9B7D6;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.12);overflow:hidden;margin-bottom:6px;">
<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td><img src="../img/v4/graph/p01.gif" border=0></td><td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.EXCHANGE_TITLE}</span></td></tr></table><div style="height:1px;background-color:#C7CEDA;margin:3px 0;"></div>
<form action="" method="POST" name="exhangeForm">
<input type="hidden" name="action" value="exchange_confirm">
<script language="javascript">
<!--
    var maxCredits = {$PARAMS.USER.GC};
{literal}
    function checkLimit()
    {
        if(document.forms('exhangeForm').gc.value > maxCredits)
        {
            document.forms('exhangeForm').gc.value = maxCredits;
        }
    }

    function openURL(url)
    {
        window.open(url, '_stat_', 'status=0, toolbar=0, menubar=0, resizable=1, scrollbars=1, height=500, width=430');
    }
{/literal}
//-->
</script>
<div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.EXCHANGE}: {$lang.GOLD_CREDITS}</label><input onkeyup="checkLimit();" type="text" size="5" maxlength="8" name="gc" style="width:100%;box-sizing:border-box;"></div><div style="padding:6px 8px 8px;"><input type="submit" value="{$lang.SUBMIT_02}" class=submit01 style="width:100%;height:24px;"></div>
</form>
</div></td></tr>
                                                                
                                                                <!--2-->
                                                                {if $show_enter_cred_form}
                                <!--ENTER MONEY2-->         
                               
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/05/t_l.gif" border=0></td>
                                                                    <td height=13 align=left background="../img/v4/borders/05/t.gif"><img src="../img/v4/borders/05/t_l_l.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/05/t_r.gif" border=0></td>
                                                                </tr>

                                                                <tr>
                                                                    <td valign=top background="../img/v4/borders/05/l.gif"><img src="../img/v4/borders/05/l_t.jpg" border=0></td>
                                                                    <td width=100% class=bg04 style="padding:2px;">
                                                                        <table width=100% cellpadding=0 cellspacing=1 border=0>
                                                                            <tr>
                                                                                <td>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.BUY_GC}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height=1 class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <form action="popmog_payment.php" method="POST" name="buyForm" target="_blank">
                                                                                <input type="hidden" name="action" value="buy_gc">
                                                                                <td>
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td align=left>
                                                                                                {$lang.ENTER_CREDITS}:
                                                                                            </td>
                                                                                            <td align=right><input type="submit" value="{$lang.BUY}" class=submit01 onClick="return checkGoldToBuy();"></td>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <input type="text" size="5" maxlength="8" name="gc" id= "gcbuy" style="width:100px; text-align: center;"><br>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                                </form>
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
                                <!--End of ENTER_MONEY2-->
                                {/if}
<tr><td colspan=3><div style="background:#EBEDF1;border:1px solid #A9B7D6;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.12);overflow:hidden;margin-bottom:6px;">
<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td><img src="../img/v4/graph/p01.gif" border=0></td><td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.TRANSFER_TITLE_TO}</span></td></tr></table><div style="height:1px;background-color:#C7CEDA;margin:3px 0;"></div>
{if count($PARAMS.USER_INSTANCE) == 0}
<div style="padding:4px 8px;">” вас нет доступных миров дл€ перевода</div>
{else}
<form action="" method="POST" name="transferToForm">
<input type="hidden" name="action" value="transfer_to_instance_confirm">
<div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.GOLD_CREDITS}:</label><input type="text" size="8" maxlength="8" name="gc" value="" style="width:100%;box-sizing:border-box;"></div><div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.SILVER_CREDITS}:</label><input type="text" size="8" maxlength="8" name="sc" value="" style="width:100%;box-sizing:border-box;"></div><div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.TO_WORLD}:</label><select name="world" style="width:100%;box-sizing:border-box;">{foreach from=$PARAMS.USER_INSTANCE item=DATA key=WORLD}<option value="{$DATA.instance}">{$WORLD}{/foreach}</select></div><div style="padding:6px 8px 8px;"><input type="submit" value="{$lang.SUBMIT_01}" class=submit01 style="width:100%;height:24px;"></div>
</form>
{/if}
</div></td></tr>

                                                                <!--3-->
                                                                
<tr><td colspan=3><div style="background:#EBEDF1;border:1px solid #A9B7D6;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.12);overflow:hidden;margin-bottom:6px;">
<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td><img src="../img/v4/graph/p01.gif" border=0></td><td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.TRANSFER_TITLE_FROM}</span></td></tr></table><div style="height:1px;background-color:#C7CEDA;margin:3px 0;"></div>
{if count($PARAMS.USER_INSTANCE) == 0}
<div style="padding:4px 8px;">” вас нет доступных миров дл€ перевода</div>
{else}
<form action="" method="POST" name="transferFromForm">
<input type="hidden" name="action" value="transfer_to_user_confirm">
<div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.GOLD_CREDITS}:</label><input type="text" size="8" maxlength="8" name="gc" value="" style="width:100%;box-sizing:border-box;"></div><div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.SILVER_CREDITS}:</label><input type="text" size="8" maxlength="8" name="sc" value="" style="width:100%;box-sizing:border-box;"></div><div style="padding:4px 8px;"><label style="display:block;font-size:10px;font-weight:bold;color:#2D2F60;margin-bottom:2px;">{$lang.FROM_WORLD}:</label><select name="world" style="width:100%;box-sizing:border-box;">{foreach from=$PARAMS.USER_INSTANCE item=DATA key=WORLD}<option value="{$DATA.instance}">{$WORLD}{/foreach}</select></div><div style="padding:6px 8px 8px;"><input type="submit" value="{$lang.SUBMIT_01}" class=submit01 style="width:100%;height:24px;"></div>
</form>
{/if}
</div></td></tr>
                                                            </table>
                                                        </td>
                                                        <!--END LEFT FRAME-->
                                                        {else}
                                                            <tr>
                                                                <td colspan=2 height=100%>
                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                        <tr>
                                                                            <td><img src="../img/v4/borders/05/t_l.gif" border=0></td>
                                                                            <td height=13 align=left background="../img/v4/borders/05/t.gif"><img src="../img/v4/borders/05/t_l_l.jpg" border=0></td>
                                                                            <td><img src="../img/v4/borders/05/t_r.gif" border=0></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td valign=top background="../img/v4/borders/05/l.gif"><img src="../img/v4/borders/05/l_t.jpg" border=0></td>
                                                                            <td width=100% class=bg04 style="padding:2px;">
                                                                                <div align="center"><font color="Red"><b>{$lang.ACCOUNT_BLOCKED}</b></font></div><br>
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
                                                    {else}
                                                        <tr>
                                                            <td colspan=2 height=100%>
                                                                <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                    <tr>
                                                                        <td><img src="../img/v4/borders/05/t_l.gif" border=0></td>
                                                                        <td height=13 align=left background="../img/v4/borders/05/t.gif"><img src="../img/v4/borders/05/t_l_l.jpg" border=0></td>
                                                                        <td><img src="../img/v4/borders/05/t_r.gif" border=0></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td valign=top background="../img/v4/borders/05/l.gif"><img src="../img/v4/borders/05/l_t.jpg" border=0></td>
                                                                        <td width=100% class=bg04 style="padding:2px;">
                                                                            <div align="center"><font color="Red"><b>{$lang.NO_ACCOUNT}</b></font></div><br>
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
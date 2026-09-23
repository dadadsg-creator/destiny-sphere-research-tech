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
                                                                                    <img src="../img/cabinet/icons/shop.png" border="0" height="194" width="274"><br>
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
                                                                                            <td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">{$lang.EXCHANGE_TITLE}</span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height=1 class=delimetr01></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <form action="" method="POST" name="exhangeForm">
                                                                                <input type="hidden" name="action" value="exchange_confirm">
                                                                                <td>
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
                                                                                    <table width=100% cellpadding=0 cellspacing=0 border=0>
                                                                                        <tr>
                                                                                            <td align=left>
                                                                                                {$lang.EXCHANGE}:
                                                                                            </td>
                                                                                            <td align=right><input type="submit" value="{$lang.SUBMIT_02}" class=submit01></td>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <input onkeyup="checkLimit();" type="text" size="5" maxlength="8" name="gc" style="width:100px; text-align: center;"><br>
                                                                                                {$lang.GOLD_CREDITS}
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
<tr><td colspan=3><div class="dsw"><style>{literal}.dsw{background:#1E2A4A;border:1px solid #3A4A7A;border-radius:8px;padding:12px;margin-bottom:6px;font-family:Tahoma,Verdana,Arial,sans-serif;}.dsw h4{color:#fff;font-size:11px;font-weight:bold;text-transform:uppercase;margin:0 0 8px;letter-spacing:.5px;}.dsw .dsw-row{display:flex;gap:8px;margin-bottom:10px;}.dsw .dsw-tile{flex:1;background:#28355C;border:1px solid #3A4A7A;border-radius:6px;padding:8px 10px;}.dsw .dsw-tile .lbl{color:#93A2C7;font-size:10px;}.dsw .dsw-tile .val{color:#fff;font-size:16px;font-weight:bold;}.dsw .dsw-toggle{display:flex;background:#28355C;border:1px solid #3A4A7A;border-radius:6px;overflow:hidden;margin-bottom:10px;}.dsw .dsw-toggle button{flex:1;border:0;padding:8px 6px;font-size:11px;font-weight:bold;color:#93A2C7;background:transparent;cursor:pointer;font-family:Tahoma,Verdana,Arial,sans-serif;}.dsw .dsw-toggle button.active{background:linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%);color:#fff;}.dsw label{display:block;color:#93A2C7;font-size:10px;font-weight:bold;margin-bottom:3px;}.dsw select,.dsw input[type=number]{width:100%;box-sizing:border-box;height:26px;border:1px solid #3A4A7A;border-radius:4px;padding:0 8px;font-size:11px;font-family:Tahoma,Verdana,Arial,sans-serif;}.dsw .dsw-field{margin-bottom:8px;}.dsw .dsw-avail{color:#93A2C7;font-size:9px;margin-top:2px;}.dsw .dsw-amount-row{display:flex;gap:6px;align-items:flex-start;}.dsw .dsw-amount-row .dsw-field{flex:1;margin-bottom:0;}.dsw .dsw-max{height:26px;padding:0 10px;border:0;border-radius:4px;background:#3A4A7A;color:#fff;font-size:10px;font-weight:bold;cursor:pointer;margin-top:15px;}.dsw .dsw-dir{background:#28355C;border:1px solid #3A4A7A;border-radius:6px;padding:6px 10px;margin:8px 0;color:#fff;font-size:11px;font-weight:bold;text-align:center;}.dsw .dsw-summary{color:#93A2C7;font-size:10px;margin:6px 0;}.dsw .dsw-error{color:#FF6B6B;font-size:10px;margin:4px 0;display:none;}.dsw .dsw-submit{width:100%;height:30px;border:0;border-radius:6px;background:linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%);color:#fff;font-size:11px;font-weight:bold;cursor:pointer;margin-top:6px;}.dsw .dsw-submit:disabled{background:#28355C;color:#93A2C7;cursor:default;}{/literal}</style><h4>Баланс кабинета</h4><div class="dsw-row"><div class="dsw-tile"><div class="lbl">Золото</div><div class="val">{$PARAMS.USER.GC|default:0}</div></div><div class="dsw-tile"><div class="lbl">Серебро</div><div class="val">{$PARAMS.USER.SC|default:0}</div></div></div><h4>Перевод валюты</h4>{if count($PARAMS.USER_INSTANCE) == 0}<div class="dsw-summary">У вас нет доступных миров для перевода</div>{else}<div class="dsw-toggle"><button type="button" id="dsDirTo" onclick="dsSetDirection('to')">В игровой мир</button><button type="button" id="dsDirFrom" onclick="dsSetDirection('from')">В кабинет</button></div><form action="" method="POST" name="dsTransferForm" onsubmit="return dsSubmitGuard(this)"><input type="hidden" name="action" id="dsActionField" value="transfer_to_instance_confirm"><div class="dsw-field"><label>Игровой мир</label><select name="world" id="dsWorldSelect" onchange="dsWorldChanged()">{foreach from=$PARAMS.USER_INSTANCE item=DATA key=WORLD}<option value="{$DATA.instance}" data-name="{$WORLD}" data-gc="{$DATA.GC|default:0}" data-sc="{$DATA.SC|default:0}">{$WORLD}{/foreach}</select></div><div class="dsw-avail">В мире: <span id="dsWorldGC"></span> золота, <span id="dsWorldSC"></span> серебра</div><div class="dsw-dir"><span id="dsDirFromLabel"></span> &rarr; <span id="dsDirToLabel"></span></div><div class="dsw-amount-row"><div class="dsw-field"><label>Золото</label><input type="number" name="gc" id="dsGC" min="0" step="1" value="" oninput="dsRecalc()"><div class="dsw-avail">Доступно <span id="dsAvailGC"></span></div></div><button type="button" class="dsw-max" onclick="dsSetMax('gc')">Всё</button></div><div class="dsw-amount-row"><div class="dsw-field"><label>Серебро</label><input type="number" name="sc" id="dsSC" min="0" step="1" value="" oninput="dsRecalc()"><div class="dsw-avail">Доступно <span id="dsAvailSC"></span></div></div><button type="button" class="dsw-max" onclick="dsSetMax('sc')">Всё</button></div><div class="dsw-summary" id="dsSummary"></div><div class="dsw-error" id="dsError"></div><button type="submit" class="dsw-submit" id="dsSubmitBtn">Перевести</button></form><script type="text/javascript">var dsCabGC = {$PARAMS.USER.GC|default:0};var dsCabSC = {$PARAMS.USER.SC|default:0};{literal}var dsDirection = "to";var dsWorlds = {};function dsInit(){  var sel = document.getElementById("dsWorldSelect");  for (var i=0;i<sel.options.length;i++){    var o = sel.options[i];    dsWorlds[o.value] = {name:o.getAttribute("data-name"), gc:parseInt(o.getAttribute("data-gc"),10)||0, sc:parseInt(o.getAttribute("data-sc"),10)||0};  }  dsSetDirection("to");}function dsCurWorld(){  var sel = document.getElementById("dsWorldSelect");  return dsWorlds[sel.value] || {name:"",gc:0,sc:0};}function dsAvail(cur){  return (dsDirection=="to") ? {gc:dsCabGC, sc:dsCabSC} : {gc:cur.gc, sc:cur.sc};}function dsSetDirection(dir){  dsDirection = dir;  document.getElementById("dsDirTo").className = (dir=="to") ? "active" : "";  document.getElementById("dsDirFrom").className = (dir=="from") ? "active" : "";  document.getElementById("dsActionField").value = (dir=="to") ? "transfer_to_instance_confirm" : "transfer_to_user_confirm";  document.getElementById("dsGC").value = "";  document.getElementById("dsSC").value = "";  dsRefresh();}function dsWorldChanged(){  document.getElementById("dsGC").value = "";  document.getElementById("dsSC").value = "";  dsRefresh();}function dsRefresh(){  var cur = dsCurWorld();  document.getElementById("dsWorldGC").innerHTML = cur.gc;  document.getElementById("dsWorldSC").innerHTML = cur.sc;  var avail = dsAvail(cur);  document.getElementById("dsAvailGC").innerHTML = avail.gc;  document.getElementById("dsAvailSC").innerHTML = avail.sc;  if (dsDirection=="to"){    document.getElementById("dsDirFromLabel").innerHTML = "Кабинет";    document.getElementById("dsDirToLabel").innerHTML = cur.name;    document.getElementById("dsSubmitBtn").innerHTML = "Перевести в " + cur.name;  } else {    document.getElementById("dsDirFromLabel").innerHTML = cur.name;    document.getElementById("dsDirToLabel").innerHTML = "Кабинет";    document.getElementById("dsSubmitBtn").innerHTML = "Вернуть в кабинет";  }  dsRecalc();}function dsSetMax(which){  var cur = dsCurWorld();  var avail = dsAvail(cur);  document.getElementById(which=="gc"?"dsGC":"dsSC").value = which=="gc" ? avail.gc : avail.sc;  dsRecalc();}function dsRecalc(){  var cur = dsCurWorld();  var avail = dsAvail(cur);  var gc = parseInt(document.getElementById("dsGC").value, 10); if (isNaN(gc) || gc < 0) gc = 0;  var sc = parseInt(document.getElementById("dsSC").value, 10); if (isNaN(sc) || sc < 0) sc = 0;  var err = "";  if (gc > avail.gc) { gc = avail.gc; document.getElementById("dsGC").value = gc; }  if (sc > avail.sc) { sc = avail.sc; document.getElementById("dsSC").value = sc; }  if (gc <= 0 && sc <= 0) { err = "Укажите хотя бы одну сумму больше нуля"; }  var target = (dsDirection=="to") ? cur.name : "кабинет";  document.getElementById("dsSummary").innerHTML = "Поступит " + gc + " золота и " + sc + " серебра в " + target;  var errEl = document.getElementById("dsError");  if (err) { errEl.innerHTML = err; errEl.style.display = "block"; } else { errEl.style.display = "none"; }  var world = document.getElementById("dsWorldSelect").value;  document.getElementById("dsSubmitBtn").disabled = !!err || !world;}function dsSubmitGuard(f){  var btn = document.getElementById("dsSubmitBtn");  if (btn.disabled) return false;  btn.disabled = true;  btn.innerHTML = "Перевод...";  return true;}{/literal}</script><script type="text/javascript">dsInit();</script>{/if}</div></td></tr>
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
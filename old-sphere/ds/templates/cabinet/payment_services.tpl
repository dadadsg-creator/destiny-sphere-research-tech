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

{literal}
<script language="javascript">
function getRadioGroupValue(radioGroupObj)
{
    for (var i=0; i < radioGroupObj.length; i++)
    if (radioGroupObj[i].checked) return radioGroupObj[i].value;

    return null;
}
</script>
{/literal}

<table width=100% height=100% border=0 cellpadding=0 cellspacing=0 class=bg_top>
    <tr>
        <td height=100% align=center valign=top>
            <table width=834 border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td>
                        <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="834" height="318" id="cab_header" align="middle">
                            <param name="allowScriptAccess" value="sameDomain" />
                            <param name="allowFullScreen" value="false" />
                            <param name="movie" value="img/cab/cab_header.swf" />
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
                                                                            <!--{if $PARAMS.MSG_TYPE == 'OK'}
                                                                                <td style="padding:1px;" align=right><img src="img/cabinet/icons/info.gif" border=0></td>
                                                                                <td style="padding:1px;" align=left><span class=sys_message_ok>{$PARAMS.MSG}</span></td>
                                                                            {else}-->
                                                                                <td style="padding:1px;" align=right><img src="img/cabinet/icons/alert.gif" border=0></td>
                                                                                <td style="padding:1px;" align=left><span class=sys_message_error>{$PARAMS.MSG}</span></td>
                                                                            <!--{/if}-->
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
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt>
                                                                                                <span style="padding-left:5px; position: relative; top: -3px;">
                                                                                                    <a href='?action=payment_services&ps_action=index'>{$lang.PAYMENT_SERVICES}</a></span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <!--
                                                                            <tr>
                                                                                <td class=delimetr01_5></td>Путкты левого меню
                                                                            </tr>
                                                                            -->
                                                                        </table>
                                                                    </td>
                                                                    <td valign=bottom background="../img/v4/borders/05/r.gif"><img src="../img/v4/borders/05/r_b.jpg" border=0></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><img src="../img/v4/borders/05/b_l.gif" border=0></td>
                                                                    <td height=13 align=right background="../img/v4/borders/05/b.gif"><img src="../img/v4/borders/05/b_r_r.jpg" border=0></td>
                                                                    <td><img src="../img/v4/borders/05/b_r.gif" border=0></td>
                                                                </tr>
                                                                <!--BUTTON TO MAIN MENU-->
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
                                                                                        <tr>
                                                                                            <td><img src="../img/v4/graph/p01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt>
                                                                                                <span style="padding-left:5px; position: relative; top: -3px;">
                                                                                                    <a href='?action=index'>{$lang.BACK_TO_MAIN_MENU}</a></span></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                            <!--
                                                                            <tr>
                                                                                <td class=delimetr01_5></td>Путкты левого меню
                                                                            </tr>
                                                                            -->
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
                                                        <td width=600 valign=top height="100%">
                                                            <table width=600 height="100%" cellpadding=0 cellspacing=0 border=0>
                                                            <tr><td height="4"></tr>
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
                                                                                            <td><img src="./img/cabinet/graph/a_01.gif" border=0></td>
                                                                                            <td width=100% align=left class=header_txt>
                                                                                                {if !empty($PARAMS.PS)}
                                                                                                {assign var=HEADER value=$PARAMS.PS}
                                                                                                {$lang.$HEADER}
                                                                                                {else}
                                                                                                {$lang.PAYMENT_SERVICES}
                                                                                                {/if}
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
                                                                                <table width="100%" border="0" cellpadding="2" cellspacing="0">
                                                                                    {if !empty($PARAMS.PS)}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {assign var=DESC value="DESC_`$PARAMS.PS`"}
                                                                                        {$lang.$DESC}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>&nbsp;</td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td class=delimetr01></td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    {if !empty($PARAMS.PRICE) && $PARAMS.PS != 'PS_TRAIN_SPEEDUP'}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            <font color="red"><b>{$lang.COST}: {$PARAMS.PRICE} {$lang.SILVER_CREDITS} {if $PARAMS.PRICE_IN_GOLD != 0}{$lang.OR} {$PARAMS.PRICE_IN_GOLD} {$lang.GOLD_CREDITS}{/if}</b></font> 
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>&nbsp;</td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    {if !empty($PARAMS.WORLD)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="hidden" name='world' value="{$PARAMS.WORLD}"> {$lang.WORLD}: {$PARAMS.WORLD_NAME}
                                                                                        </td>
                                                                                    </tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    {if $PARAMS.MODE == 'INDEX'}
                                                                                    {if count($PARAMS.USER_WORLDS) > 0}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">  
                                                                                    {if !empty($PARAMS.RING)}
                                                                                    <input type="hidden" name="ring" value="{$PARAMS.RING}">    
                                                                                    {/if}
                                                                                                                                                                    
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {if $PARAMS.PS == 'PS_BY_BONUS_RESOURCE_SINGLE' or $PARAMS.PS == 'PS_BY_BONUS_RESOURCE'}
                                                                                        {$lang.SELECT_BONUS_DAYS}:<br>
                                                                                        <table>
                                                                                        <tr>
                                                                                         <td>14 {$lang.DAYS}</td><td> <input type=radio name='days' value=14 checked>&nbsp;&nbsp;</td>
                                                                                         <td>30 {$lang.DAYS}</td><td> <input type=radio name='days' value=30>&nbsp;&nbsp;</td>
                                                                                         <td>90 {$lang.DAYS}</td><td> <input type=radio name='days' value=90>&nbsp;&nbsp;</td>
                                                                                         <td>180 {$lang.DAYS}</td><td> <input type=radio name='days' value=180>&nbsp;&nbsp;</td>
                                                                                         <td>360 {$lang.DAYS}</td><td> <input type=radio name='days' value=360>&nbsp;&nbsp;</td>
                                                                                        </tr>
                                                                                        </table>
                                            <br>
                                                                                        {/if}
                                                                                        {if !empty($PARAMS.USER_WORLDS)}
                                                                                        {$lang.CHOOSE_WORLD}:
                                                                                        <select name='world'>
                                                                                            {foreach from=$PARAMS.USER_WORLDS item=WORLD}
                                                                                            <option value="{$WORLD.dbId}_{$WORLD.id_world}_{$WORLD.worldname}">{$WORLD.worldname}
                                                                                            {/foreach}
                                                                                        </select>
                                                                                        {/if}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>&nbsp;</td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    {else}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.NOT_AVAILABLE_WORLD}
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'BUY_ANALIZATOR_INDEX'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="buy_analizator">                                                                             
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.ANALIZATOR_30}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'DELETE_INSTANCE_INDEX'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">  
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">                                                                              
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.ENTER_YOUR_PASSWORD}: <input type="password" name='password'>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'MYLTY_WORLD_ACCESS_INDEX'}
                                                                                    {if key_exists('ALREADY_HAVE', $PARAMS)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            {$lang.YOUR_ALREADY_HAS_MULTIWORLD_ABILITY}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>   
                                                                                    {else}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">                                                                                  
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.MULTIWORLD_ABILITY}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.GET_ABILITY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    {/if}
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'BY_PREMIUM_BAN_INDEX'}
                                                                                    {if key_exists('ALREADY_HAVE', $PARAMS)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                        {if key_exists('TIME_TO_ACTIVATION', $PARAMS)}
                                                                                            <b>{$lang.TIME_TOACTIVATION_BAN} {$PARAMS.TIME_TO_ACTIVATION}</b>
                                                                                        {else}
                                                                                            <b>{$lang.YOUR_ALREADY_HAS_PREMIUM_BAN} {$PARAMS.TIME_TO_END_BAN}</b>
                                                                                        {/if}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    {elseif key_exists('MOUNTH_DELAY', $PARAMS)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <b>{$lang.MOUNTH_DELAY} {$PARAMS.TIME_TO_NEXT_ORDER}</b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>   
                                                                                    {else}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">                                                                                  
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.ENTER_PREMIUM_BAN_DAYS} <input type="text" size="2" name="count_of_days" align="right">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            {assign var=LANG_COMMENT value="`$PARAMS.PS`_COMMENT"}
                                                                                            <font color="Red">{$lang.$LANG_COMMENT}</font> 
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    {/if}
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'BY_PREMIUM_BAN_CONFIM'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">  
                                                                                    
                                                                                    <input type="hidden" name="count_of_days" value="{$PARAMS.COUNT_OD_DAYS}">                                                                              
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.PREMIUM_BAN_WILL_ADDED} <b>{$PARAMS.COUNT_OD_DAYS}</b> {$lang.DAYS}.
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="OK" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'RESERVE_COMB_INDEX'}
                                                                                    {if !empty($PARAMS.RESERVED_COMB)}
                                                                                        <tr>
                                                                                            <td>
                                                                                                <font color="Red">
                                                                                                {$lang.YOU_ALREADY_RESERVED_COMB} <b>({$PARAMS.RESERVED_COMB})</b> {$lang.IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>.<br> {$lang.RESERVATION_WILL_BE_CANCELED}
                                                                                                </font>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                    {/if}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">                                                                                  
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">  
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.ENTER_COMB_COORDS}:  
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="text" maxlength="1" size="4" name="ring" value="{$PARAMS.ring}"> <b>.</b>
                                                                                            <input type="text" maxlength="4" size="4" name="complex" value="{$PARAMS.complex}"> <b>.</b>
                                                                                            <input type="text" maxlength="1" size="4" name="comb" value="{$PARAMS.comb}">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            <input type="submit" class="submit03" value="OK" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}!{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'DELETE_INSTANCE_COMFIRM'}
                                                                                    <tr>
                                                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                                                            <h4>{$lang.YOUR_COMB_IN_WORLD} {$PARAMS.WORLD_NAME} {$lang.WAS_DELETED}</h4>
                                                                                        </td>
                                                                                    </tr>
                                                                                            <tr>
                                                                                                <td colspan=2 align="center" class="wnd_gen_txt_large" style="height: 20px;">
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td colspan="2" align="center">
                                                                                                    <form action="user_cabinet.php" method="post">
                                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                                    <input type="submit" class="submit03" value="{$lang.DELETE_COMB}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                                    </form>
                                                                                                </td>
                                                                                            </tr>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'BUY_TRAINING_COMPLETION'}
                                                                                <tr><td>
                                                                                    {if $PARAMS.USER_IS_NUB && $PARAMS.USER_EVENTS}
                                                                                    <p>{$lang.TRAINING_BUY_THIS_TEXT}</p>
                                                                                    <form action="user_cabinet.php" method="POST">
                                                                                        <input type="hidden" name="action" value="payment_services">
                                                                                        <input type="hidden" name="exec_action" value="complete">
                                                                                        <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                        <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                        <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}!{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                    </form>
                                                                                    {else}
                                                                                    {if !$PARAMS.USER_IS_NUB}<p>{$lang.TRAINING_NOT_NUB}</p>{/if}
                                                                                    {if !$PARAMS.USER_EVENTS}<p>{$lang.TRAINING_NO_EVENTS}</p>{/if}
                                                                                    {/if}
                                                                                </td></tr>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'BUY_SCIENCE_ACCELERATION'}
                                                                                <tr><td>
                                                                                    {if $PARAMS.NO_RESERCH}
                                                                                        <p>{$lang.NO_RESERCH}</p>
                                                                                    {elseif $PARAMS.MIN_SCIENCE_LEVEL}
                                                                                        <p>{$lang.MIN_SCIENCE_LEVEL} <b>{$PARAMS.MIN_SCIENCE_LEVEL}</b> {$lang.LEVEL}.</p>
                                                                                    {elseif $PARAMS.SCIENCE_ALREADY_ACCELERATED}
                                                                                        <p>{$lang.SCIENCE_ALREADY_ACCELERATED}</p>
                                                                                    {else}
                                                                                        <p>
                                                                                        {$lang.OLD_RESEARCH_DATE} <b>{$PARAMS.LEVEL}</b> {$lang.SCIENCE_LEVEL} <b>{$PARAMS.RESEARCH_NAME}</b> {$lang.STAY} <b>{$PARAMS.OLD_RESEARCH_DATE}</b><br>
                                                                                        {$lang.NEW_RESEARCH_DATE} <b>{$PARAMS.NEW_RESEARCH_DATE}</b><br>
                                                                                        </p>
                                                                                        <form action="user_cabinet.php" method="POST">
                                                                                            <input type="hidden" name="action" value="payment_services">
                                                                                            <input type="hidden" name="exec_action" value="complete">
                                                                                            <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                            <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                            <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </form>
                                                                                    {/if}
                                                                                </td></tr>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'BUY_RESOURCE_SECOND_MENU'}
                                                                                    <tr>
                                                                                    <td>
                                                                                    <table border="0">
                                                                                    
                                                                                    {foreach from=$PARAMS.RES_DATA key=TEG item=RESOURCE}
                                                                                    <form name="{$TEG}" action="user_cabinet.php" method="POST">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="confirm_buy_resource">
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">  
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        
                                                                                        {if $RESOURCE.res_available > 0}
                                                                                        
                                                                                        {$lang.BY} <input id="{$TEG}" type="text" maxlength="3" onblur="{literal}if (document.{/literal}{$TEG}{literal}.packet_amount.value != parseInt(document.{/literal}{$TEG}{literal}.packet_amount.value)) alert('{/literal}{$lang.WRONG_DATA}{literal}'); {/literal}" size="3" name="packet_amount" value="1"> {$lang.PACKETS} {$lang.$TEG}<br>
                                                                                                                                                                                <b>1 {$lang.PACKET} = {$RESOURCE.amount} {$lang.$TEG}</b><br>
                                                                                        <font color="Red"><b>{$lang.PACKET_COST}: {$RESOURCE.PRICE} {if $RESOURCE.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $RESOURCE.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</b></font><br>
                                                                                        ({$lang.YOU_CAN_BY} {$RESOURCE.res_available} {$lang.PACKETS})
                                                                                        </td>
                                                                                        <td align="center"  valign="middle">
                                                                                            <input type="hidden" name="resource" value="{$TEG}">
                                                                                            <input type="hidden" name="amount" value="{$RESOURCE.amount}">
                                                                                            <input type="hidden" name="resName" value="{$lang.$TEG}">
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                        </form>
                                                                                        {else}
                                                                                        <font color="#696969">
                                                                                        <b>1 {$lang.PACKET} = {$RESOURCE.amount} {$lang.$TEG}</b><br>
                                                                                        <i>{$lang.PACKET_COST}: {$RESOURCE.PRICE} {if $RESOURCE.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $RESOURCE.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</i><br>
                                                                                        </td>
                                                                                        <td align="center" valign="middle" colspan="2">
                                                                                        {$lang.YOU_CAN_NOT_BY}
                                                                                        </font>
                                                                                        </td>
                                                                                        {/if}
                                                                                    </tr>
                                                                                    <td>
                                                                                        &nbsp;
                                                                                    </td>
                                                                                    {/foreach}
                                                                                    </table>
                                                                                    </td>
                                                                                    </tr>
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'BUY_TURNIR_SECOND_MENU'}
                                                                                        {if key_exists('ALREADY_HAS_TURNIR', $PARAMS) && $PARAMS.ALREADY_HAS_TURNIR == 'YES'}
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.ALREADY_HAS_TURNIR}
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        {elseif key_exists('ALREADY_TURNIR_MAX_USERS', $PARAMS)}
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.ALREADY_TURNIR_MAX_USERS}
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        {else}
                                                                                        <form action="user_cabinet.php" method="post" name="by_turnir">
                                                                                        <input type="hidden" name="action" value="payment_services">
                                                                                        <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                        <input type="hidden" name="exec_action" value="buy_turnir">     
                                                                                        <input type="hidden" name="world" value="{$PARAMS.WORLD}">                                                                      
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.TURNIR_ENTRY}
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <table border="0">
                                                                                                    <tr>
                                                                                                        <td>
                                                                                                            {$lang.HOME_COMB_NAME}:
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <input type="text" name="comb_name" value="{$PARAMS.COMB_NAME}">
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td>
                                                                                                            {$lang.RACE}:
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <select name="race">
                                                                                                            {foreach from=$PARAMS.RACES key=KEY item=RACE}
                                                                                                                {if $PARAMS.RACE == $KEY}
                                                                                                                    {assign var=SELECTED value='selected'}
                                                                                                                {else}
                                                                                                                    {assign var=SELECTED value=''}
                                                                                                                {/if}
                                                                                                                <option value="{$KEY}" {$SELECTED}>{$RACE}
                                                                                                            {/foreach}
                                                                                                            </select>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                </table>    
                                                                                            </td>
                                                                                        </tr>   
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        {if $PARAMS.PRICE_IN_GOLD != 0} 
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.CHOOSE_CURRENCY}:
                                                                                            </td>
                                                                                        </tr>   
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                 <input type="radio" name="curency" value="GC" style="border:0" checked> {$lang.GCC}<br>
                                                                                                 <input type="radio" name="curency" value="SC" style="border:0" {if $PARAMS.SC_SELECTED}checked{/if}> {$lang.SCC}<br>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        {else}
                                                                                        <input type="hidden" name="curency" value="SC">
                                                                                        {/if}
                                                                                        
                                                                                        <tr>
                                                                                            <td>
                                                                                                <input type="submit" class="submit03" value="{$lang.BY}" onclick="{literal}if(getRadioGroupValue(document.by_turnir.curency) == 'GC'){return confirm('{/literal}{$lang.WILL_PAY} {$PARAMS.PRICE_IN_GOLD} {$lang.GOLD_CREDITS}{literal}')}else return confirm('{/literal}{$lang.WILL_PAY} {$PARAMS.PRICE} {$lang.SILVER_CREDITS}{literal}');{/literal}"><br><br>
                                                                                            </td>
                                                                                        </tr>   
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        </form>
                                                                                        {/if}
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'BUY_PREMIUM_SECOND_MENU'}
                                                                                    <tr>
                                                                                    <td>
                                                                                    <table border="0">
                                                                                    
                                                                                        <form action="user_cabinet.php" method="POST">
                                                                                        <input type="hidden" name="action" value="payment_services">
                                                                                        <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                        <input type="hidden" name="exec_action" value="buy_premium">
                                                                                        <input type="hidden" name='days' value="{$PARAMS.COUNT_OF_DAY}">                                                                                
                                                                                        {if key_exists('BUY_ANALIZATOR', $PARAMS)}
                                                                                        <input type="hidden" name='analizator' value="yes"> 
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.PREMIUM_ON} <b>{$PARAMS.COUNT_OF_DAY}</b> {$lang.DAYS_AND_ACCESS_TO_ANALIZATOR_PLUS}<br>
                                                                                                <font color="Red"><b>{$lang.COST2}: {$PARAMS.PRICE_PLUS} {if $PARAMS.CREDIT_PLUS == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT_PLUS == 'GC'}{$lang.GOLD_CREDITS}{/if}</b></font><br>
                                                                                            </td>
                                                                                        </tr>
                                                                                        {else}
                                                                                        <input type="hidden" name='analizator' value="no">
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.PREMIUM_ON} <b>{$PARAMS.COUNT_OF_DAY}</b> {$lang.DAYS}<br>
                                                                                                <font color="Red"><b>{$lang.COST2}: {$PARAMS.PRICE_PLUS} {if $PARAMS.CREDIT_PLUS == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT_PLUS == 'GC'}{$lang.GOLD_CREDITS}{/if}</b></font><br>
                                                                                            </td>
                                                                                        </tr>
                                                                                        {/if}
                                                                                        
                                                                                        <!-- Комментируем для Классики, чтобы не было возможности покупать прем другим игрокам, не зареганным на волке
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                {$lang.PEMIYM_FOR_USER}* <input type="text" name="userName" value="{$PARAMS.FOR_USER}">
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                * {$lang.NO_DATA_IF_NOT_FOR_YOURSELF}</td>
                                                                                        </tr> -->
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <input type="hidden" name="userName" value="{$PARAMS.FOR_USER}">&nbsp;
                                                                                            </td>
                                                                                        </tr> 
                                                                                        
                                                                                        
                                                                                        
                                                                                        
                                                                                        <tr>
                                                                                            <td align="center"  valign="middle">
                                                                                                <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE_PLUS} {if $PARAMS.CREDIT_PLUS == 'SC'}{$lang.SILVER_CREDITS}!{/if}{if $PARAMS.CREDIT_PLUS == 'GC'}{$lang.GOLD_CREDITS}{/if}');">

                                                                                            </td>
                                                                                        </form>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                    </td>
                                                                                    </tr>                                               
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'BUY_PREMIUM_INDEX'}
                                                                                    <tr>
                                                                                    <td>
                                                                                    <table border="0">
                                                                                    {if key_exists('PREM_DAYS', $PARAMS)}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.PREMIUM_VALID_YET} <b>{$PARAMS.PREM_DAYS}</b> {$lang.DAYS} <b>{$PARAMS.PREM_HOURS}</b> {$lang.HOURS}
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                                                                                                        
                                                                                    {foreach from=$PARAMS.DAYS key=COUNT_OF_DAYS item=DATA}
                                                                                    <form action="user_cabinet.php" method="POST">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="show_second_menu">
                                                                                    <input type="hidden" name='days' value="{$COUNT_OF_DAYS}">
                                                                                    <input type="hidden" name='analizator' value="no">  
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.PREMIUM_ON} <b>{$COUNT_OF_DAYS}</b> {$lang.DAYS}<br>
                                                                                            <font color="Red"><b>{$lang.COST2}: {$DATA.PRICE} {if $DATA.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $DATA.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</b></font><br>
                                                                                        </td>
                                                                                        </td>
                                                                                        <td align="center"  valign="middle">
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </form>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/foreach}
                                                                                    </table>
                                                                                    </td>
                                                                                    </tr>
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'BUY_RESOURCE_CONFIRM'}
                                                                                    
                                                                                    
                                                                                    <form name="{$TEG}" action="user_cabinet.php" method="POST">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="buy_resource">
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">  
                                                                                    <input type="hidden" name='resource' value="{$PARAMS.RESOURSE}">    
                                                                                    <input type="hidden" name='resName' value="{$PARAMS.RES_NAME}"> 
                                                                                    <input type="hidden" name='amount' value="{$PARAMS.AMOUNT}">    
                                                                                    <input type="hidden" name='packet_amount' value="{$PARAMS.PACKET_AMOUNT}">  
                                                                                    </tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {$lang.ON_YOUR_COMB_IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b> {$lang.WILL_ADDED} <b>{$PARAMS.CALCULATED_AMOUNT} {$PARAMS.RES_NAME}</b>.<br>
                                                                                        {$lang.WILL_PAY} <b>{$PARAMS.CALCULATED_PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}</b>{/if}.
                                                                                        </td>
                                                                                    </tr>
                                                                                    </tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <input type="submit" class="submit03" value="OK" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.CALCULATED_PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'TRANSFER_COMB_TO_BLACK_HOLE_SECOND_MENU'}
                                                                                
                                                                                {if !empty($PARAMS.ERROR_TEXT)}
                                                                                    {assign var=ERROR_TEXT value=$PARAMS.ERROR_TEXT}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.$ERROR_TEXT}
                                                                                        </td>
                                                                                    </tr>
                                                                                {else}
                                                                                    <form action="user_cabinet.php" method="POST">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="transfer_comb_to_black_hole">
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">  
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {$lang.YOUR_COMB_IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b> {$lang.WILL_TRANSFER_IN_BLACK_HOLE}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <input type="submit" class="submit03" value="{$lang.TRANSFER}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'GRANT_BUILDING_SECOND_MENU'}
                                                                                    {if isset($PARAMS.AlREADY_HAS)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.YOU_ALREADY_CAN_BUILD_IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>.
                                                                                        </td>
                                                                                    </tr>
                                                                                    
                                                                                    {else}
                                                                                    <form action="user_cabinet.php" method="POST">
                                                                                        <input type="hidden" name="action" value="payment_services">
                                                                                        <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                        <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                        <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                        
                                                                                        <input type="hidden" name='user_instance' value="{$PARAMS.USER_INSTANCE}">
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.ABILITY_BUILD_IN_WORLD}: <b>{$PARAMS.WORLD_NAME}</b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.BY_BUILD}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    </form>
                                                                                    {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'WORLD_ABILITY_SECOND_MENU'}
                                                                                
                                                                                    {if isset($PARAMS.USER_REGIRTEREG_IN_ALL_WORLDS)}<tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td>
                                                                                            <b>{$lang.YOU_WAS_REGISTERED_IN_ALL_WORLDS}</b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    {else}
                                                                                    <form action="user_cabinet.php" method="POST">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.PLAY_IN_WORLD}: <b>{$PARAMS.WORLD_NAME}</b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>   
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.GET_ABILITY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    </form>
                                                                                    {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'EXCHANGE_COORDS_SECOND_MENU'}
                                                                                    
                                                                                    {if isset($PARAMS.ORDERED_TRANSFER)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.EXCHANGE_COORDS} 
                                                                                            <b>{$PARAMS.TRANSFER_COMB_TO.combname}
                                                                                            ({$PARAMS.TRANSFER_COMB_TO.ringnumber}.{$PARAMS.TRANSFER_COMB_TO.complexnumber}.{$PARAMS.TRANSFER_COMB_TO.combnumber})
                                                                                            </b>
                                                                                             {$lang.WITH_COMB}
                                                                                            <b>{$PARAMS.TRANSFER_COMB_FROM.combname}
                                                                                            ({$PARAMS.TRANSFER_COMB_FROM.ringnumber}.{$PARAMS.TRANSFER_COMB_FROM.complexnumber}.{$PARAMS.TRANSFER_COMB_FROM.combnumber})
                                                                                            </b>
                                                                                             {$lang.PLAYER1} <b>{$PARAMS.USER_NAME_WITH}</b>. 
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <form action="user_cabinet.php" method="POST">
                                                                                                <input type="hidden" name="action" value="payment_services">
                                                                                                <input type="hidden" name="ring" value="{$PARAMS.RING}">
                                                                                                <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                                <input type="hidden" name="exec_action" value="cansel_exchange">
                                                                                                <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                                
                                                                                                <input type="hidden" name='user_id_with' value="{$PARAMS.USER_ID_WITH}">
                                                                                                <input type="hidden" name='user_name_with' value="{$PARAMS.USER_NAME_WITH}">
                                                                                                <input type="submit" class="submit03" value="{$lang.CANCEL_TRANSFER}">
                                                                                                
                                                                                            </form>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {elseif isset($PARAMS.ORDERED_FREE_RING_TRANSFER)}
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            {$lang.EXCHANGE_ALREADY_ORDERED}
                                                                                        </td>
                                                                                    </tr>
                                                                                    {elseif isset($PARAMS.TRANSFER_COMPLITE)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.YOU_ORDER_EXCHANGE_COMB} <b>{$PARAMS.TRANSFER_COMB_TO.combname}
                                                                                            ({$PARAMS.TRANSFER_COMB_TO.ringnumber}.{$PARAMS.TRANSFER_COMB_TO.complexnumber}.{$PARAMS.TRANSFER_COMB_TO.combnumber})</b>
                                                                                            {$lang.WITH_COMB} <b>{$PARAMS.TRANSFER_COMB_FROM.combname}
                                                                                            ({$PARAMS.TRANSFER_COMB_FROM.ringnumber}.{$PARAMS.TRANSFER_COMB_FROM.complexnumber}.{$PARAMS.TRANSFER_COMB_FROM.combnumber})</b>
                                                                                            </b> {$lang.PLAYER1} <b>{$PARAMS.USER_NAME_WITH}</b>.<br>
                                                                                            {$lang.EXCHANGE_WILL_IN_EVENING}
                                                                                        </td>
                                                                                    </tr>
                                                                                    
                                                                                    {elseif isset($PARAMS.ORDERED_TRANSFER_WITH)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b> {$lang.HAS_ORDER_EXCHENGE} 
                                                                                            <b>{$PARAMS.TRANSFER_COMB_FROM.combname}
                                                                                            ({$PARAMS.TRANSFER_COMB_FROM.ringnumber}.{$PARAMS.TRANSFER_COMB_FROM.complexnumber}.{$PARAMS.TRANSFER_COMB_FROM.combnumber})</b>
                                                                                            </b> {$lang.WITH_COMB} 
                                                                                            <b>{$PARAMS.TRANSFER_COMB_TO.combname}
                                                                                            ({$PARAMS.TRANSFER_COMB_TO.ringnumber}.{$PARAMS.TRANSFER_COMB_TO.complexnumber}.{$PARAMS.TRANSFER_COMB_TO.combnumber})</b>
                                                                                            . 
                                                                                            {if $PARAMS.WHO_PAY == 1}
                                                                                                <br><b>{$PARAMS.USER_NAME_WITH} {$lang.YOU_WILL_PAY}</b>
                                                                                            {else}
                                                                                                <b>{$PARAMS.USER_NAME_WITH}</b> {$lang.ALREADY_WAS_PAY} 
                                                                                            {/if}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                        <table border="0" width="100%">
                                                                                        <tr>
                                                                                            <form action="user_cabinet.php" method="POST">
                                                                                            <input type="hidden" name="action" value="payment_services">
                                                                                            <input type="hidden" name="ring" value="{$PARAMS.RING}">
                                                                                            <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                            <input type="hidden" name="exec_action" value="cansel_exchange">
                                                                                            <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                            
                                                                                            <input type="hidden" name='user_id_with' value="{$PARAMS.USER_ID_WITH}">
                                                                                            <input type="hidden" name='user_name_with' value="{$PARAMS.USER_NAME_WITH}">
                                                                                                <td align="left">
                                                                                                
                                                                                                    <input type="submit" class="submit03" value="{$lang.CANCEL_TRANSFER}">
                                                                                                </td>
                                                                                            </form>
                                                                                            <form action="user_cabinet.php" method="POST">
                                                                                            <input type="hidden" name="action" value="payment_services">
                                                                                            <input type="hidden" name="ring" value="{$PARAMS.RING}">
                                                                                            <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                            <input type="hidden" name="exec_action" value="confirm_exchange_coords">
                                                                                            <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                            
                                                                                            <input type="hidden" name='who_pay' value="{$PARAMS.WHO_PAY}">
                                                                                            <input type="hidden" name='comb_id_from' value="{$PARAMS.COMB_ID_FROM}">
                                                                                            <input type="hidden" name='comb_id_to' value="{$PARAMS.COMB_ID_TO}">
                                                                                            <input type="hidden" name='user_id_with' value="{$PARAMS.USER_ID_WITH}">
                                                                                            <input type="hidden" name='user_name_with' value="{$PARAMS.USER_NAME_WITH}">
                                                                                            <input type="hidden" name='user_instance_from' value="{$PARAMS.USER_INSTANSE_FROM}">
                                                                                                <td align="right">
                                                                                                {if $PARAMS.WHO_PAY == 1}
                                                                                                    <input type="submit" class="submit03" value="{$lang.CONFIRM_TRANSFER}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                                {else}
                                                                                                    <input type="submit" class="submit03" value="{$lang.CONFIRM_TRANSFER}">
                                                                                                {/if}
                                                                                                </td>
                                                                                            </form>
                                                                                        </tr>
                                                                                        </table>
                                                                                        <td>
                                                                                    </tr>   
                                                                                    </form>
                                                                                    {else}  
                                                                                    <tr>
                                                                                        <td>
                                                                                        </td>
                                                                                    </tr>
                                                                                        {if count($PARAMS.COMBS) < 1}
                                                                                        {if $PARAMS.RING == 4}
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                {$lang.YOU_HAS_NOT_COLLONIS_IN_WORLD} {$PARAMS.WORLD_NAME}
                                                                                            </td>
                                                                                        </tr>
                                                                                        {else}
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                {$lang.YOU_HAS_NOT_COMBS_IN_WORLD} {$PARAMS.WORLD_NAME}
                                                                                            </td>
                                                                                        </tr>
                                                                                        {/if}
                                                                                    {else}  
                                                                                    
                                                                                    <form name="exchenge_coords" action="user_cabinet.php" method="POST">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ring" value="{$PARAMS.RING}">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="exchange_coords">
                                                                                    <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                    <input type="hidden" name="user_instance_from" value="{$PARAMS.COMBS.0.id_userinstance}">   
                                                                                    <tr>
                                                                                        <td width="50%" valign="top" align="left" class="wnd_gen_txt_large">    
                                                                                            {if count($PARAMS.COMBS) == 1}
                                                                                            {$lang.ENTER_PLAYER_TO_EXCHANGE} <b>{$PARAMS.COMBS.0.combname}({$PARAMS.COMBS.0.ringnumber}.{$PARAMS.COMBS.0.complexnumber}.{$PARAMS.COMBS.0.combnumber})</b>
                                                                                            <input type="hidden" name='comb_id_from' value="{$PARAMS.COMBS.0.id_comb}">
                                                                                            {else}
                                                                                            {$lang.CHOOSE_COMB_TO_EXCHANGE}
                                                                                            <select name='comb_id_from'>
                                                                                            {foreach from=$PARAMS.COMBS item=COMB}
                                                                                                    {if $PARAMS.COMB_ID_FROM == $COMB.id_comb}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                                <option value="{$COMB.id_comb}" {$SELECTED}>{$COMB.combname}({$COMB.ringnumber}.{$COMB.complexnumber}.{$COMB.combnumber})
                                                                                            {/foreach}
                                                                                            </select>
                                                                                            {/if}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.NICK_NAME}: <input type="text" name="user_name_with" value="{$PARAMS.USER_NAME_WITH}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.WHO_PAY}: 
                                                                                            <select name="who_pay"> 
                                                                                                <option value="not_chossen">----</option>
                                                                                                
                                                                                                    {if $PARAMS.WHO_PAY == 1}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                                    
                                                                                                <option value="1" {$SELECTED}>{$lang.YOU}</option>
                                                                                                
                                                                                                    {if $PARAMS.WHO_PAY == 2}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                                <option value="2" {$SELECTED}>{$lang.OTHER_PLAYER}</option>
                                                                                            </select>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {if $PARAMS.RING == 4}  
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.RING}:&nbsp;<b>{$PARAMS.RING}</b>&nbsp;&nbsp;
                                                                                            {$lang.COMPLEX}:&nbsp;<input type="text" size="4" maxlength="4" name="comb_complex_to"  value="{$PARAMS.COMB_COMPLEX_TO}">&nbsp;&nbsp;
                                                                                            {$lang.COMB}:&nbsp;<input type="text" size="1" maxlength="1" name="comb_number_to"  value="{$PARAMS.COMB_NUMBER_TO}">&nbsp;&nbsp;<br><br>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.ORDER_TRANSFER}" onclick="if(document.exchenge_coords.who_pay.options[document.exchenge_coords.who_pay.selectedIndex].value == 1)return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>   
                                                                                    </form>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                        {/if}
                                                                                    {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'CHANGE_COORDS_SECOND_MENU'}
                                                                                        {if count($PARAMS.COMBS) < 1}
                                                                                        {if $PARAMS.RING == 4}
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                {$lang.YOU_HAS_NOT_COLLONIS_IN_WORLD} {$PARAMS.WORLD_NAME}
                                                                                            </td>
                                                                                        </tr>
                                                                                        {else}
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                {$lang.YOU_HAS_NOT_COMBS_IN_WORLD} {$PARAMS.WORLD_NAME}
                                                                                            </td>
                                                                                        </tr>
                                                                                        {/if}
                                                                                    {else}
                                                                                    <tr>
                                                                                        <td>
                                                                                            <form action="user_cabinet.php" method="POST">
                                                                                                <input type="hidden" name="action" value="payment_services">
                                                                                                <input type="hidden" name="ring" value="{$PARAMS.RING}">
                                                                                                <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                                <input type="hidden" name="exec_action" value="change_coords">
                                                                                                <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                                
                                                                                            {if count($PARAMS.COMBS) == 1}
                                                                                            {$lang.ENTER_COORDS_TO_TRANSFER} <b>{$PARAMS.COMBS.0.combname}({$PARAMS.COMBS.0.ringnumber}.{$PARAMS.COMBS.0.complexnumber}.{$PARAMS.COMBS.0.combnumber})</b>
                                                                                            <input type="hidden" name='old_coords' value="{$PARAMS.COMBS.0.ringnumber}.{$PARAMS.COMBS.0.complexnumber}.{$PARAMS.COMBS.0.combnumber}_{$PARAMS.COMBS.0.combname}">
                                                                                            {else}
                                                                                            {$lang.CHOOSE_COMB_TO_TRANSFER}:
                                                                                            <select name='old_coords'>
                                                                                            {foreach from=$PARAMS.COMBS item=COMB}
                                                                                                    {if $PARAMS.OLD_COORDS == "`$COMB.ringnumber`.`$COMB.complexnumber`.`$COMB.combnumber`_`$COMB.combname`"}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                                <option value="{$COMB.ringnumber}.{$COMB.complexnumber}.{$COMB.combnumber}_{$COMB.combname}" {$SELECTED}>{$COMB.combname}({$COMB.ringnumber}.{$COMB.complexnumber}.{$COMB.combnumber})
                                                                                            {/foreach}
                                                                                            </select><br><br>
                                                                                            {$lang.ENTER_COORDS_TO_TRANSFER}:
                                                                                            {/if}
                                                                                            <br><br>
                                                                                                {$lang.RING}:&nbsp;<b>{$PARAMS.RING}</b>&nbsp;&nbsp;
                                                                                                {$lang.COMPLEX}:&nbsp;<input type="text" size="4" maxlength="4" name="complex"  value="{$PARAMS.COMPLEX}">&nbsp;&nbsp;
                                                                                                {$lang.COMB}:&nbsp;<input type="text" size="1" maxlength="1" name="comb"  value="{$PARAMS.COMB_NUMBER}">&nbsp;&nbsp;<br><br>
                                                                                                <input type="submit" class="submit03" value="{$lang.CHANGE_COORDS}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                                
                                                                                            </form>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <form action="RingView.php" method="POST" target="_blank">
                                                                                                <input type="hidden" name="ring" value="{$PARAMS.RING}">
                                                                                                <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                                <input type="submit" class="submit02" value="{$lang.RING_MAP}">
                                                                                            </form>
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'BY_UNIT_SECOND_MENU'}
                                                                                    <tr>
                                                                                        <td colspan=2 align="left">
                                                                                            {assign var=UNIT_NAME value="`$PARAMS.UNIT_NAME`2"}
                                                                                            <b><font color="Red">{$lang.UNIT_COST} {$lang.$UNIT_NAME}: {$PARAMS.UNIT_PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</font></b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td colspan=2 align="left">
                                                                                            {assign var=LIMIT value="UNIT_LIMIT_`$PARAMS.UNIT_NAME`"}
                                                                                            <i><font color="Red">{$lang.$LIMIT} {$PARAMS.UNIT_LIMIT}.</font></i>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {if count($PARAMS.COMBS) < 1}
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                {$lang.YOU_HAS_NOT_COMBS_IN_WORLD} {$PARAMS.WORLD_NAME}
                                                                                            </td>
                                                                                        </tr>
                                                                                    {else}
                                                                                    <tr>
                                                                                        <td>
                                                                                        <form action="user_cabinet.php" method="POST">
                                                                                            <input type="hidden" name="action" value="payment_services">
                                                                                            <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                            <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                            <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                                
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                {assign var=UNIT_NAME value="`$PARAMS.UNIT_NAME`S"}
                                                                                                {if count($PARAMS.COMBS) == 1}
                                                                                                    <input type="hidden" name='id_comb' value="{$PARAMS.COMBS.0.id_comb}">
                                                                                                    <td>
                                                                                                        {$lang.COMB_TO_BY_UNIT} {$lang.$UNIT_NAME}:
                                                                                                    </td>
                                                                                                    <td>
                                                                                                        <b>{$PARAMS.COMBS.0.combname}({$PARAMS.COMBS.0.ringnumber}.{$PARAMS.COMBS.0.complexnumber}.{$PARAMS.COMBS.0.combnumber})</b>
                                                                                                    </td>
                                                                                                {else}
                                                                                                <td>
                                                                                                    {$lang.CHOOSE_COMB_TO_BY_UNIT} {$lang.$UNIT_NAME}:
                                                                                                </td>
                                                                                                <td>
                                                                                                    <select name='id_comb'>
                                                                                                        {foreach from=$PARAMS.COMBS item=COMB}
                                                                                                            <option value="{$COMB.id_comb}">{$COMB.combname}({$COMB.ringnumber}.{$COMB.complexnumber}.{$COMB.combnumber})
                                                                                                            {/foreach}
                                                                                                    </select>
                                                                                                </td>
                                                                                                {/if}
                                                                                                
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    {$lang.ENTER_UNIT_AMOUNT}:
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="text" name="count">
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    &nbsp;
                                                                                                </td>
                                                                                            </tr>
                                                                                            </table> 
                                                                                        <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </form>
                                                                                        </td>
                                                                                    </tr>
                                                                                {/if}
                                                                                
                                                                                {elseif $PARAMS.MODE == 'BY_UNIT_CONFIRM'}
                                                                                    <tr>
                                                                                        <td colspan=2 align="left">
                                                                                            {assign var=UNIT_NAME value="`$PARAMS.UNIT_NAME`2"}
                                                                                            <b><font color="Red">{$lang.UNIT_COST} {$lang.$UNIT_NAME}: {$PARAMS.UNIT_PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</font></b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td colspan=2 align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    {assign var=UNIT_NAME value="`$PARAMS.UNIT_NAME`3"}
                                                                                    
                                                                                    {if !($PARAMS.ENABLE_BY_UNIT)}
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                <b>{$lang.UNIT_HAS_NOT_PLACE} {$lang.$UNIT_NAME}</b>.
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td colspan=2 align="center">
                                                                                                &nbsp;
                                                                                            </td>
                                                                                        </tr>
                                                                                    {else}
                                                                                    <form action="user_cabinet.php" method="POST">
                                                                                            <input type="hidden" name="action" value="payment_services">
                                                                                            <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                            <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                            <input type="hidden" name='world' value="{$PARAMS.WORLD}">
                                                                                            
                                                                                            <input type="hidden" name="id_comb" value="{$PARAMS.COMB_ID}">
                                                                                            <input type="hidden" name="count" value="{$PARAMS.UNIT_AMOUNT}">
                                                                                    {if key_exists('NOT_PLACE_TO_ALL', $PARAMS)}
                                                                                    <tr>
                                                                                        <td>
                                                                                            <b>{$lang.NOT_PLACE_TO_ALL} {$PARAMS.OLD_UNIT_AMOUNT} {$lang.$UNIT_NAME}.</b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    <tr>
                                                                                        <td>
                                                                                            {$lang.ON_YOUR} <b>{$PARAMS.COMB_NAME}</b> {$lang.IN_WORLD} {$PARAMS.WORLD_NAME} {$lang.WILL_ADDED} <b>{$PARAMS.UNIT_AMOUNT}</b> {$lang.$UNIT_NAME}.<br>
                                                                                            <b><font color="red">{$lang.WILL_PAY} {$PARAMS.FINAL_COST} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}.</font></b>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.FINAL_COST} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                {/if}
                                                                                {elseif $PARAMS.MODE == 'BY_UNIT_MAX_UNIT_LIMIT'}
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    {assign var=UNIT_NAME value="`$PARAMS.UNIT_NAME`3"}
                                                                                    {if key_exists('ENABLE_TO_BY', $PARAMS)}
                                                                                    </tr>
                                                                                        <td>
                                                                                            {assign var=LIMIT_LANG value=$PARAMS.UNIT_LIMIT_LANG}
                                                                                            <h4>{$lang.$LIMIT_LANG} <b>{$PARAMS.UNIT_LIMIT}</b>. {$lang.UNIT_CAN_BY} {$PARAMS.ENABLE_TO_BY} {$lang.$UNIT_NAME}.</h4>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {else}
                                                                                    </tr>
                                                                                        <td>
                                                                                            {assign var=LIMIT_LANG value="COMB_UNIT_LIMIT_`$PARAMS.UNIT_NAME`"}
                                                                                            <h3>{$lang.$LIMIT_LANG} <b>{$PARAMS.UNIT_LIMIT}</b>{$lang.UNIT_CANT_BY}.</h3>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'CHANGE_COMB_NAME_SECOND_MENU'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {if count($PARAMS.COMBS) > 1}
                                                                                            {$lang.CHOOSE_COMB_TO_RENAME}: 
                                                                                            <select name="comb">
                                                                                            {foreach from=$PARAMS.COMBS item=COMB}
                                                                                                {if $PARAMS.COMB_ID == $COMB.id_comb}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                            <option value="{$COMB.id_comb}_{$COMB.combname}" {$SELECTED}>{$COMB.combname}({$COMB.coords})
                                                                                            {/foreach}
                                                                                            </select>
                                                                                        {else}
                                                                                        {$lang.YOU_RENAME_COMB} {foreach from=$PARAMS.COMBS item=COMB}
                                                                                            {$COMB.combname}({$COMB.coords})
                                                                                            <input type="hidden" name="comb" value="{$COMB.id_comb}_{$COMB.combname}">
                                                                                            {/foreach}
                                                                                        {/if}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.NEW_NAME}:
                                                                                            <input type="text" maxlength="30" name="comb_name" value="{$PARAMS.comb_name}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            {assign var=BUTTON_TEXT value=$PARAMS.BUTTON_TEXT}
                                                                                            <input type="submit" class="submit03" value="{$lang.$BUTTON_TEXT}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'BY_BONUS_SECOND_MENU'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <input type="hidden" name="days" value="{$PARAMS.DAYS}">
                                                                                    {if $PARAMS.ALREADY_HAS == 1}
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <b>{$lang.USER_ALREADY_HAS_BONUS}</b><br><br>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {else}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.RES_BONUSES}<br>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            {assign var=BUTTON_TEXT value=$PARAMS.BUTTON_TEXT}
                                                                                            <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    </form>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'BY_BONUS_SINGLE_SECOND_MENU'}
                                                                                    <tr>
                                                                                        <td>
                                                                                            <table border="0">
                                                                                            <tr>
                                                                                                <td align="left">
                                                                                                    {$lang.BY_BONUS_RESOURCE_SINGLE} <b>{$PARAMS.DAYS}</b> {$lang.DAYS}:
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td align="left">
                                                                                                    &nbsp;
                                                                                                </td>
                                                                                            </tr>
                                                                                            {foreach from=$PARAMS.RES_BONUSES key=RESOURCE item=PERSENT}
                                                                                            <form action="user_cabinet.php" method="post">
                                                                                            <input type="hidden" name="action" value="payment_services">
                                                                                            <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                            <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                            <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                            <input type="hidden" name="resource" value="{$RESOURCE}">
                                                                                            <input type="hidden" name="days" value="{$PARAMS.DAYS}">                                                                                            
                                                                                            <tr>
                                                                                                    <td align="left"> 
                                                                                                    {if $PERSENT.ALREADY_HAS == 1}
                                                                                                        <font color="Gray">{$lang.BY} {$PERSENT.PERSENT}% {$PERSENT.NAME}</font><br>
                                                                                                        <i>{$lang.ALREADY_HAS_BONUS_THIS_RES}</i><br>
                                                                                                        <font color="Red"><b>{$lang.COST2}: {$PERSENT.PRICE} {if $PERSENT.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PERSENT.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</b></font><br>
                                                                                                        </td>
                                                                                                        </td>
                                                                                                        <td align="center"  valign="middle">
                                                                                                            <input type="button" class="submit03" value="{$lang.BY}" disabled>
                                                                                                        </td>
                                                                                                    {else}
                                                                                                        {$lang.BY} {$PERSENT.PERSENT}% {$PERSENT.NAME}<br>
                                                                                                        <font color="Red"><b>{$lang.COST2}: {$PERSENT.PRICE} {if $PERSENT.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PERSENT.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}</b></font><br>
                                                                                                        </td>
                                                                                                        </td>
                                                                                                        <td align="center"  valign="middle">
                                                                                                            <input type="submit" class="submit03" value="{$lang.BY}" onclick="return confirm('{$lang.WILL_PAY} {$PERSENT.PRICE} {if $PERSENT.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PERSENT.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                                        </td>
                                                                                                    {/if}
                                                                                                    
                                                                                                        
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    &nbsp;
                                                                                                </td>
                                                                                            </tr>
                                                                                            </form>
                                                                                            
                                                                                            {/foreach}
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'DELETE_UNIQUE_CHOOSE_BUILDING'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <input type="hidden" name="comb" value="{$PARAMS.COMB}">
                                                                                    {if count($PARAMS.BUILDINGS_TO_DELETE) >= 1}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.CHOOSE_BUILDING_TO_DELETE}:
                                                                                            <select name="building">
                                                                                            {assign var=SELECTED value=''}
                                                                                            {foreach from=$PARAMS.BUILDINGS_TO_DELETE key=celltypename item=celltypearr}                                                                                                
                                                                                                <option value="0_0_0">-----------------{$celltypename}-----------------
                                                                                                {foreach from=$celltypearr item=building}
                                                                                                {if isset($PARAMS.ID_ATTACHED_BUILDING)}
                                                                                                    {if $PARAMS.ID_ATTACHED_BUILDING == $building.id_attachedbuilding}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                                {/if}
                                                                                                    <option value="{$building.id_attachedbuilding}_{$building.id_building}_{$building.buildingname}" {$SELECTED}>{$building.buildingname}
                                                                                                {/foreach}
                                                                                            {/foreach}
                                                                                            </select>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    {else}
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            {$lang.NOT_ALLOWED_BUINDING_TO_DELETE}
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'DELETE_UNIQUE_CONFIRM'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <input type="hidden" name="comb" value="{$PARAMS.COMB}">
                                                                                    <input type="hidden" name="building" value="{$PARAMS.BUILDING}">
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.CONFIRM_DELETE_BUILDING} <b>{$PARAMS.BUILDING_NAME}</b> {$lang.ON_COMB} <b>{$PARAMS.COMB_NAME}</b> {$lang.IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>?
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <input type="submit" class="submit03" value="{$lang.DELETE_BUILDING}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                    {elseif $PARAMS.MODE == 'DELETE_UNIQUE_CHOOSE_COMB'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.CHOOSE_COMB}: 
                                                                                            <select name="comb">
                                                                                            {foreach from=$PARAMS.COMBS item=COMB}
                                                                                                {if $PARAMS.COMB_ID == $COMB.id_comb}
                                                                                                        {assign var=SELECTED value='selected'}
                                                                                                    {else}
                                                                                                        {assign var=SELECTED value=''}
                                                                                                    {/if}
                                                                                            <option value="{$COMB.id_comb}_{$COMB.combname}" {$SELECTED}>{$COMB.combname}({$COMB.coords})
                                                                                            {/foreach}
                                                                                            </select>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'RESTORE_COMB_FROM_BLACK_HOLE_INDEX'}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    {if isset($PARAMS.ERROR_TEXT)}                                                      
                                                                                        {assign var=ERROR_TEXT value=$PARAMS.ERROR_TEXT}
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                <h5>{$lang.$ERROR_TEXT}</h5>
                                                                                            </td>
                                                                                        </tr>
                                                                                    {else}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    
                                                                                    <input type="hidden" name="db_name_from" value="{$PARAMS.db_name_from}">
                                                                                    <input type="hidden" name="db_id_from" value="{$PARAMS.db_id_from}">
                                                                                    <input type="hidden" name="id_world_from" value="{$PARAMS.id_world_from}">
                                                                                    
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.COMB_WAS_TRANSFER_TO_BLACK_HOLE} {$PARAMS.transfer_date}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            <input type="submit" class="submit03" value="{$lang.RESTORE}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    {/if}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    
                                                                                
                                                                                {elseif $PARAMS.MODE == 'MERGE_ACCOUNTS_INDEX'}
                                                                                <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.ENTER_ALL_ACCOUNTS_TO_MERGE}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    
                                                                                    {if isset($PARAMS.ERROR)}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {assign var=MSG value=$PARAMS.ERROR}
                                                                                            <h5><font color="Red">{$lang.$MSG}</font></h5>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/if}
                                                                                    
                                                                                    
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            <table border="1" cellpadding="4" cellspacing="1" bordercolor="#3e456a">
                                                                                                <tr>
                                                                                                    <th>
                                                                                                        {$lang.ACCOUNT_TO_MERGE}
                                                                                                    </th>
                                                                                                    <th>
                                                                                                        {$lang.ACCOUNT_PASSWORD}
                                                                                                    </th>
                                                                                                </tr>
                                                                                                {section name=login loop=6}
                                                                                                <tr>
                                                                                                    <td>
                                                                                                        <input type="text" maxlength="20" size="30" name="login[]" value="{$PARAMS.LOGINS[login]}">
                                                                                                    </td>
                                                                                                    <td>
                                                                                                        <input type="password" maxlength="20" size="30" name='password[]'>
                                                                                                    </td>
                                                                                                </tr>
                                                                                                {/section}
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left" colspan="2">
                                                                                                <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left" colspan="2">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'MERGE_ACCOUNTS_SECOND_MENU'}
                                                                                <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.TO_ACCOUNT} {$PARAMS.USER_NAME} {$lang.WILL_MERGE}:
                                                                                            {assign var=COMMA value=""}
                                                                                            {foreach from=$PARAMS.MERGE_USERS item=USER}
                                                                                            <input type="hidden" name="login[]" value="{$USER}">
                                                                                            {$COMMA}{$USER}
                                                                                            {assign var=COMMA value=", "}
                                                                                            {/foreach}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left" colspan="2">
                                                                                                <input type="submit" class="submit03" value="{$lang.MERGE}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left" colspan="2">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                
                                                                                
                                                                                {elseif $PARAMS.MODE == 'DELYVERY_INSTANCE_INDEX'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.ENTER_YOUR_PASSWORD}: <input type="password" name='password'>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.ENTER_NICK_TO_DELIVERY}: <input type="text" name="to_user" value="{$PARAMS.TO_USER}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                                <input type="submit" class="submit03" value="{$lang.NEXT}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                
                                                                                {elseif $PARAMS.MODE == 'DELYVERY_INSTANCE_SECOND_MENU'}
                                                                                
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <input type="hidden" name="to_user" value="{$PARAMS.TO_USER}">
                                                                                    <input type="hidden" name="to_user_id" value="{$PARAMS.TO_USER_ID}">
                                                                                    
                                                                                    <input type="hidden" name="world" value="{$PARAMS.WORLD}">
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            {$lang.MONEY_WILL_TRANSFER} <b>{$PARAMS.TO_USER}</b>. {$lang.ARE_YOU_CURE_DELIVERY}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                                <input type="submit" class="submit03" value="{$lang.DELIVERY}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>
                                                                                
                                                                                
                                                                                {elseif $PARAMS.MODE == 'LOGS_SECOND_MENU'}
                                                                                    <form action="user_cabinet.php" method="post">
                                                                                    <input type="hidden" name="action" value="payment_services">
                                                                                    <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                    <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                    <!--<input type="hidden" name="world" value="{$PARAMS.WORLD}"> -->
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.MAIL_TO_LOG}: <input type="text" size="30" name="email" value="{$PARAMS.EMAIL}">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            {$lang.LOG_FROM} {$PARAMS.START_DATE} {$lang.TO} {$PARAMS.END_DATE}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <input type="submit" class="submit03" value="{$lang.SEND}" onclick="return confirm('{$lang.WILL_PAY} {$PARAMS.PRICE} {if $PARAMS.CREDIT == 'SC'}{$lang.SILVER_CREDITS}{/if}{if $PARAMS.CREDIT == 'GC'}{$lang.GOLD_CREDITS}{/if}');">
                                                                                        </td>
                                                                                    </tr>
                                                                                    </form>

                                                                                {elseif $PARAMS.MODE == 'TRAIN_SPEEDUP_INDEX'}
                                                                                    {if !empty($PARAMS.USER_WORLDS)}
                                                                                        {if count($PARAMS.PAID_TILL)}
                                                                                            <b>{$lang.TRAIN_SPEEDUP_ACTIVE_TILL}<br>
                                                                                            {foreach from=$PARAMS.PAID_TILL key=WORLDNAME item=DATE}
                                                                                                {$WORLDNAME}: {$DATE}<br>
                                                                                            {/foreach}
                                                                                            </b><br>
                                                                                        {/if}
                                                                                        <form action="user_cabinet.php" method="post">
                                                                                        <input type="hidden" name="action" value="payment_services">
                                                                                        <input type="hidden" name="ps_action" value="{$PARAMS.PS}">
                                                                                        <input type="hidden" name="exec_action" value="{$PARAMS.EXEC_ACTION}">
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                {$lang.CHOOSE_WORLD}:
                                                                                                <select name='world'>
                                                                                                    {foreach from=$PARAMS.USER_WORLDS item=WORLD}
                                                                                                    <option value="{$WORLD.dbId}_{$WORLD.id_world}_{$WORLD.worldname}">{$WORLD.worldname}
                                                                                                    {/foreach}
                                                                                                </select>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                <select name="days">
                                                                                                    <option value="7">7</option>
                                                                                                    <option value="30">30</option>
                                                                                                    <option value="90">90</option>
                                                                                                    <option value="180">180</option>
                                                                                                    <option value="360">360</option>
                                                                                                </select> {$lang.DAYS}
                                                                                                <input type="submit" class="submit03" value="{$lang.BUY}" onclick="return confirm('{$lang.TRAIN_SPEEDUP_CONFIRM}')">
                                                                                            </td>
                                                                                        </tr>
                                                                                        </form>
                                                                                    {else}
                                                                                         {$lang.TRAIN_SPEEDUP_INACTIVE}
                                                                                    {/if}
                                                                                {elseif $PARAMS.MODE == 'NOT_ENOUGHT_CREDITS'}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center" class=header_txt>
                                                                                        {if isset($PARAMS.ENOUGHT_GOLD)}
                                                                                        {$lang.NOT_EHOUGH_MONEY_BUT_CAN_EXCHANGE}
                                                                                        {elseif isset($PARAMS.WORLD_WHERE_ENOUGH_MONEY)}
                                                                                         {$lang.NOT_EHOUGH_MONEY_BUT_CAN_TRANSFER_FOM_WORLD} {$PARAMS.WORLD_WHERE_ENOUGH_MONEY}
                                                                                        {else}
                                                                                        {$lang.NOT_EHOUGH_MONEY}
                                                                                        {/if}
                                                                                    <tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    
                                                                                {elseif $PARAMS.MODE == 'SUCCESSFUL'}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                        {assign var=CREDITS value=$PARAMS.CREDIT}
                                                                                        
                                                                                        {if $PARAMS.PS == 'PS_BUY_RESOURCE'}
                                                                                            {$lang.HAS_BEEN_BOUGHT} <b>{$PARAMS.AMOUNT} {$PARAMS.TEG}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.CALCULATED_PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BUY_PREMIUM'}
                                                                                            {if $PARAMS.FOR_OUTHER_PLAYR == 't'}
                                                                                                {$lang.HAS_BEEN_BOUGHT} <b>{$PARAMS.COUNT_OF_DAYS}</b> {$lang.DAYS_OF_PREMIUM}
                                                                                                    {if $PARAMS.ANALIZATOR == 'yes'}
                                                                                                        {$lang.AND_ACCESS_TO_ANALIZATOR_PLUS}
                                                                                                    {/if}
                                                                                                    {$lang.FOR_USER} <b>{$PARAMS.FOR_USER}</b>.<br>
                                                                                                    {$lang.WAS_PAY} <b>{$PARAMS.CALCULATED_PRICE}</b> {$lang.$CREDITS}.
                                                                                            {else}
                                                                                                {$lang.HAS_BEEN_ADDED} <b>{$PARAMS.COUNT_OF_DAYS}</b> {$lang.DAYS_OF_PREMIUM}
                                                                                                    {if $PARAMS.ANALIZATOR == 'yes'}
                                                                                                        {$lang.AND_ACCESS_TO_ANALIZATOR_PLUS}
                                                                                                    {/if}. 
                                                                                                    {$lang.PREMIUM_ACTIVATION}{$lang.PREMIUM_VALID_YET} <b>{$PARAMS.DAYS}</b> {$lang.DAYS} <b>{$PARAMS.HOURS}</b> {$lang.HOURS}.<br>
                                                                                                    {$lang.WAS_PAY} <b>{$PARAMS.CALCULATED_PRICE}</b> {$lang.$CREDITS}.
                                                                                            {/if}
                                                                                        {elseif $PARAMS.PS == 'PS_CHANGE_COMB_NAME'}
                                                                                            {$lang.COMB2} <b>{$PARAMS.OLD_NAME}</b> {$lang.HAS_BEEN_RENAMED_INTO} <b>{$PARAMS.NEW_NAME}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BUY_TRAINING_COMPLETION'}
                                                                                        <b>{$lang.TRAINING_OK}</b>
                                                                                        {elseif $PARAMS.PS == 'PS_RESERVE_COMB'}
                                                                                            {$lang.COMB_HAS_BEEN_RESERVED} <b>{$PARAMS.COORDS}</b> {$lang.IN_WORLD}  <b>{$PARAMS.WORLD}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_DELETE_INSTANCE'}
                                                                                            {$lang.YOUR_COMB_IN_WORLD} {$lang.IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b> {$lang.HAS_BEEN_DELETED}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_DELYVERY_INSTANCE'}
                                                                                            {$lang.COMB_HAS_BEEN_DELEVERED_TO_USER} <b>{$PARAMS.USER_NAME}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_MERGE_ACCOUNTS'}
                                                                                            {$lang.ACCOUNTS} {$PARAMS.MERGE_USERS} {$lang.HAS_BEEN_MERGED}.
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_WORLD_ABILITY'}
                                                                                            {$lang.NOW_YOU_CAN_PLAY_IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_MYLTY_WORLD_ACCESS'}
                                                                                            {$lang.NOW_YOU_CAN_PLAY_IN_ALL_WORLD}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_CHANGE_COORDS'}
                                                                                            {$lang.COMB2}  <b>{$PARAMS.COMB_NAME}({$PARAMS.OLD_COORDS})</b> {$lang.HAS_BEEN_TRANSFER_ON_COORDS} <b>({$PARAMS.NEW_COORDS})</b>.<br>
                                                                                        {elseif $PARAMS.PS == 'PS_CHANGE_COORDS_MELS'}
                                                                                            {$lang.COMB2}  <b>{$PARAMS.COMB_NAME}({$PARAMS.OLD_COORDS})</b> {$lang.HAS_BEEN_TRANSFER_ON_COORDS} <b>({$PARAMS.NEW_COORDS})</b>.<br>
                                                                                        {elseif $PARAMS.PS == 'PS_EXCHANGE_COORDS'}
                                                                                            {if $PARAMS.TRANSFER_ACTION == 'order'}
                                                                                                {$lang.EXCHANGE_HAS_BEEN_ORDERED_WITH_PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b><br><b>{$PARAMS.USER_NAME_WITH}</b> {$lang.MUST_CONFIRM_TRANSFER}.
                                                                                                {if $PARAMS.IS_PAID == 'YES'}
                                                                                                {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                                {/if}
                                                                                            {elseif $PARAMS.TRANSFER_ACTION == 'confrirm'}
                                                                                                {$lang.EXCHANGE_HAS_BEEN_CONFIRM_WITH_PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b>.
                                                                                            {elseif $PARAMS.TRANSFER_ACTION == 'cancel'}
                                                                                                {$lang.EXCHANGE_HAS_BEEN_CANSELED_WITH_PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b>.
                                                                                            {/if}
                                                                                        {elseif $PARAMS.PS == 'PS_EXCHANGE_COORDS_MELS'}
                                                                                            {if $PARAMS.TRANSFER_ACTION == 'order'}
                                                                                                {$lang.EXCHANGE_HAS_BEEN_ORDERED_WITH_PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b>.<br><b>{$PARAMS.USER_NAME_WITH}</b> {$lang.MUST_CONFIRM_TRANSFER}.<br>
                                                                                                {if $PARAMS.IS_PAID == 'YES'}
                                                                                                {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                                {/if}
                                                                                            {elseif $PARAMS.TRANSFER_ACTION == 'confrirm'}
                                                                                                {$lang.EXCHANGE_HAS_BEEN_CONFIRM_WITH_PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b>.
                                                                                            {elseif $PARAMS.TRANSFER_ACTION == 'cancel'}
                                                                                                {$lang.EXCHANGE_HAS_BEEN_CANSELED_WITH_PLAYER} <b>{$PARAMS.USER_NAME_WITH}</b>.
                                                                                            {/if}
                                                                                        {elseif $PARAMS.PS == 'PS_TRANSFER_COMB_TO_BLACK_HOLE'}
                                                                                            {$lang.YOU_HAS_BEEN_ORDERED_TRANSFER_IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b> {$lang.IN_BLACK_HOLE}.<br>{$lang.BLACK_HOLE_SCHEDULE}.
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_RESTORE_COMB_FROM_BLACK_HOLE'}
                                                                                            {$lang.RESTORE_FROM_BLACK_HOLE_HAS_BEEN_ORDERED}.<br>{$lang.BLACK_HOLE_SCHEDULE}.
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_GRANT_BUILDING'}
                                                                                            {$lang.GRAND_BUILDING_ADDED_IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BUY_TURNIR'}
                                                                                            {$lang.TURNIR_HAS_BEEN_BOUGHT}.<br>
                                                                                            {$lang.WAS_PAY} {if $PARAMS.CURENCY == 'SC'}<b>{$PARAMS.PRICE}</b> {$lang.SILVER_CREDITS}{else}<b>{$PARAMS.PRICE_IN_GOLD}</b> {$lang.GOLD_CREDITS}{/if}.
                                                                                        {elseif $PARAMS.PS == 'PS_GET_LOG'}
                                                                                            {$lang.LETTER_HAS_BEEN_SENT_ON_MAIL}: <b>{$PARAMS.MAIL}</b>.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BUY_ANALIZATOR'}
                                                                                            {$lang.ANALIZATOR_HAS_BEEN_ADDED} {$PARAMS.TO_DATE}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_DELETE_UNIQUE_BUILDING'}
                                                                                            {$lang.BUILDING} <b>{$PARAMS.BUILDING_NAME}</b> {$lang.ON_COMB} <b>{$PARAMS.COMB_NAME}</b> {$lang.IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b> {$lang.HAS_BEEN_DELETED2}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BY_BONUS_RESOURCE'}
                                                                                            {$lang.HAS_BEEN_BY_BONUSES}  на {$PARAMS.days} {$lang.DAYS} {$lang.IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>.
                                                                                        {elseif $PARAMS.PS == 'PS_BY_BONUS_RESOURCE_SINGLE'}
                                                                                            {$lang.HAS_BEEN_BY_BONUSES_SINGLE} <b>{$PARAMS.RESOURCE} {$PARAMS.PERSENT}%</b> на {$PARAMS.days} {$lang.DAYS} {$lang.IN_WORLD} <b>{$PARAMS.WORLD_NAME}</b>.
                                                                                        {elseif $PARAMS.PS == 'PS_BY_UNIT_ARCHEOLOG' || $PARAMS.PS == 'PS_BY_UNIT_SCIENTIIFIC'}
                                                                                            {assign var=UNIT_NAME value="`$PARAMS.UNIT_NAME`3"}
                                                                                            {$lang.ON_YOUR} <b>{$PARAMS.COMB_NAME}</b> {$lang.IN_WORLD} {$PARAMS.WORLD_NAME} {$lang.WAS_ADDED} <b>{$PARAMS.UNIT_AMOUNT}</b> {$lang.$UNIT_NAME}.<br>
                                                                                        {elseif $PARAMS.PS == 'PS_BY_PREMIUM_BAN'}
                                                                                            {$lang.PREMIUM_BAN_HAVE_BEEN_ADDED} <b>{$PARAMS.COUNT_OF_DAYS}</b> {$lang.DAYS}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BY_PREMIUM_BAN_30'}
                                                                                            {$lang.PREMIUM_BAN_HAVE_BEEN_ADDED} <b>{$PARAMS.COUNT_OF_DAYS}</b> {$lang.DAYS}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_BY_SCIENCE_ACCELERATION'}
                                                                                            {$lang.OLD_RESEARCH_DATE} <b>{$PARAMS.RESEARCH_NAME}</b> {$lang.STAY} <b>{$PARAMS.NEW_RESEARCH_DATE}</b><br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.
                                                                                        {elseif $PARAMS.PS == 'PS_TRAIN_SPEEDUP'}
                                                                                            {$lang.TRAIN_SPEEDUP_HAVE_BEEN_ADDED} <b>{$PARAMS.COUNT_OF_DAYS}</b> {$lang.DAYS}.<br>
                                                                                            {$lang.WAS_PAY} <b>{$PARAMS.PRICE}</b> {$lang.$CREDITS}.<br>
                                                                                            <b>{$lang.TRAIN_SPEEDUP_ACTIVE_TILL} {$PARAMS.PAID_TILL}</b>
                                                                                        {/if}
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                {else}
                                                                                <!--List Of Payment Services-->
                                                                                {foreach from=$PARAMS.PAYMENT_SERVICES key=GROUP item=SERVICE_ARR}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            <font class="header_txt">{$lang.$GROUP}</span>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {foreach from=$SERVICE_ARR key=KEY item=SERVICE}
                                                                                    <tr>
                                                                                        <td align="left">
                                                                                            &nbsp;&nbsp;&nbsp;<a href="?action=payment_services&ps_action={$SERVICE}">{$lang.$SERVICE}</a>
                                                                                        </td>
                                                                                    </tr>
                                                                                    {/foreach}  
                                                                                {/foreach}  
                                                                                {/if}
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
</body>
</html>
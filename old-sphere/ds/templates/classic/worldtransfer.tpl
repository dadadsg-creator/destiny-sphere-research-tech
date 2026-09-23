<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="../dsstyle.css" type="text/css">
    <link rel="top" href="index.php" title="" />
    <link rel="search" href="search.php" title="" />
    <link rel="help" href="faq.php" title="" />
    <link rel="author" href="memberlist.php" title="" />
    
    <title>{$lang.TITLE}</title>
    
    {literal}
    <script language="JavaScript">
    <!-- 
    function alertIfChoosenHell() () 
    {
        if ( document.world.world_from == 'hel')
        {
          //Если есть выбранный элемент, отобразить его значение (свойство value)
          alert('Ваша сота будет удалена. Восстановить ее можно будет только платно');
        }
    }   
    // -->
    </script>
    {/literal}
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>
<body>

<table width=100% height=95% border=0 cellpadding=0 cellspacing=0>
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
                                        <param name="quality" value="low">
                                        <param name="menu" value="false">
                                        <param name="settings" value="false">
                                        <param name="wmode" value="opaque">
                                        <param name="bgcolor" value="#ffffff">
                                        <embed src="../img/flash/header.swf" quality="low" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
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
        <td valign=top height=100%>
            <table width="100%" border="0" cellpadding="0" cellspacing="10">
                <tr>
                <td valign=top>
                        <table width="100%" border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=5 height=5><img src="../img/border/01/t_l.gif" border=0 width=5 height=5></td>
                                <td height=5 background="../img/border/01/t.gif"></td>
                                <td width=10 height=5><img src="../img/border/01/t_r.gif" border=0 width=10 height=5></td>
                            </tr>
                            <tr>
                                <td width=5 background="../img/border/01/l.gif"></td>
                                <td width=100% class=wnd_outer valign=top>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <!-- -->
                                        {if count($USER_WORLDS) > 0}
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% align="center" class=wnd_inner>
                                                <form action="FreeWorldTransfer.php" method="POST">
                                                <table width=100% border=0 cellpadding=3 cellspacing=3>
                                                    <tr>
                                                        <td colspan="2" align="center" class="wnd_gen_txt_large">
                                                        <b>{$lang.WORLD_FROM_TEXT}</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width="45%" align="right" class="wnd_gen_txt_large">
                                                            <b>{$lang.WORLD_FROM}</b>:
                                                        </td>
                                                        <td width="55%">
                                                            <select name="world_from" class="select_gen">
                                                                {foreach from=$USER_WORLDS item=USER_WORLD}
                                                                    <option value="{$USER_WORLD.id_world}_{$USER_WORLD.dbId}">{$USER_WORLD.worldname}
                                                                {/foreach}
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" align="center">
                                                            <input type="submit" value="{$lang.SELECT}" class="input_button">
                                                        </td>
                                                    </tr>
                                                </table>
                                                </form>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        {else}
                                        <!-- -->
                                        {if !empty($MSG)}
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% align="center" class=wnd_inner>
                                                <div class="wnd_gen_txt_large" style="font-size: 10pt;"><b><font color='#993333'><li>{$lang.$MSG}</li></font></b></div>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        {/if}
                                        <!-- -->
                                        {if !$CRITICAL}
                                        <tr height=25>
                                            <td colspan=3 align=center valign="bottom">
                                                <img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>{$lang.CAPTION}</b></span></td>
                                        </tr>                                       
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% class=wnd_inner align="center" >
                                                <table width=100% border=0 cellpadding=3 cellspacing=3>
                                                    {if $CAN_TRANSFER != 0}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <form action="" name=world method="POST">
                                                                <input type="hidden" name="world_from" value="{$FROM_WORLD}">
                                                                {$lang.WORLD_FROM_TO} <select name="world" class="select_gen">
                                                                {foreach from=$WORLDS item=WORLD}
                                                                    <option value="{$WORLD.dbId}_{$WORLD.id_world}" {if $WORLD.dbId == $DBID_TO && $WORLD.id_world == $WORLDID_TO}selected="selected"{/if}>{$WORLD.worldname}
                                                                {/foreach}
                                                                </select><br><br>
                                                                {$lang.EMPTY_FILD_DESC}<br>
                                                                
                                                                {$lang.RING}: <b>{$RING_NUMBER}</b>&nbsp;&nbsp;&nbsp;
                                                                {$lang.COMPLEX}: <input type="text" class="input_gen" size="4" name="complex">&nbsp;&nbsp;&nbsp;
                                                                {$lang.COMB}: <input type="text" class="input_gen" size="2" name="comb"><br><br>
                                                                
                                                                {if !empty($USER_COLONIES)}
                                                                {$lang.CAN_RESERVE_MELS}:<br>
                                                                {foreach from=$USER_COLONIES item=COLONY}
                                                                    {$lang.FOR_COLONY} <b>{$COLONY.combname} ({$COLONY.coords})</b> -
                                                                    {$lang.RING}: <b>4</b>&nbsp;&nbsp;&nbsp;
                                                                    {$lang.COMPLEX}: <input type="text" class="input_gen" size="4" name="coorld_mels[{$COLONY.id_comb}][complex]">&nbsp;&nbsp;&nbsp;
                                                                    {$lang.COMB}: <input type="text" class="input_gen" size="2" name="coorld_mels[{$COLONY.id_comb}][comb]"><br><br>
                                                                {/foreach}                                                                                                                          
                                                                {/if}
                                                                
                                                                {if $TEMP_COLONIES != 0}
                                                                <font color="Red">{$lang.CHOOSE_TEMP_COLONY}:</font><br>
                                                                <select name="temp_colony" class="select_gen">
                                                                {foreach from=$USER_COLONIES item=COLONY}
                                                                    <option value="{$COLONY.id_comb}">{$COLONY.combname}({$COLONY.coords})
                                                                {/foreach}
                                                                </select>
                                                                <br><br>
                                                                {/if}
                                                                
                                                                <input class="input_button" type="submit" value="{$lang.ORDER_TRANSFER}" onclick="alertIfChoosenHell()">
                                                            </form>
                                                            <br><br><br>
                                                            <form action="RingView.php" method="POST" target="_blank">
                                                                <input type="hidden" name="ring" value="{$RING_NUMBER}">
                                                                {$lang.CHOOSE_HOME_COMB_WORLD}:<br>
                                                                <select name="world" class="select_gen">
                                                                {foreach from=$WORLDS item=WORLD}
                                                                    <option value="{$WORLD.dbId}_{$WORLD.id_world}">{$WORLD.worldname}
                                                                {/foreach}
                                                                </select>
                                                                <input class="input_button" type="submit" value="{$lang.OVERVIEW}">
                                                            </form>
                                                            {if $CAN_RESERVE_MELS != 0}
                                                            <form action="RingView.php" method="POST" target="_blank">
                                                                <input type="hidden" name="ring" value="4">
                                                                {$lang.CHOOSE_MELS_COMB_WORLD}:<br>
                                                                <select name="world" class="select_gen">
                                                                {foreach from=$WORLDS item=WORLD}
                                                                    <option value="{$WORLD.dbId}_{$WORLD.id_world}">{$WORLD.worldname}
                                                                {/foreach}
                                                                </select>
                                                                <input class="input_button" type="submit" value="{$lang.OVERVIEW}">
                                                            </form>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                    {else}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            {if empty($WORLD_TO)}
                                                            <font style="font-size: 10pt;"><b>{$lang.NOT_ENABLED_WORLDS}</b></font>
                                                            {else}
                                                            <font style="font-size: 10pt;"><b>{$lang.TRANSFER_USED}</b></font>
                                                            <br>({$WORLD_TO})
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                        {if $CAN_CANCEL && !empty($WORLD_TO)}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <form action="" method="POST">
                                                                <input type="hidden" name="cancel" value="1">
                                                                <input type="hidden" name="world_from" value="{$FROM_WORLD}">
                                                                <input type="submit" class="input_button" value="{$lang.CANCEL_TRANSFER}">
                                                            </form>
                                                        </td>
                                                    </tr>
                                                        {/if}
                                                    {/if}
                                                </table>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        {/if}
                                        {/if}
                                        <tr>
                                            <td colspan=3 height=25 align=center valign=bottom>
                                                <!--img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><!--b>{$lang.EXISTING_WORLDS}</b--></span></td>
                                        </tr>
                                        <!--Description world-->
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% class=wnd_inner>
                                                <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                                    {foreach from=$WORLDS item=WORLD}                                                   
                                                    <tr>
                                                        <td class="row1" width="35%">
                                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                                <tr>
                                                                    <td width="25%" align="center" class="wnd_gen_txt">
                                                                        <img src="images/world_logo/{$WORLD.dbname}_{$WORLD.id_world}.gif" border="0">
                                                                    </td>
                                                                    <td width="5%"></td>
                                                                    <td width="70%" class="wnd_gen_txt">
                                                                        <span class="{if $WORLD.profi == 'f'}ind_st1{else}ind_st2{/if}" style="font-size: 12px;"><b>{$WORLD.worldname}</b></span><br>
                                                                        <span class="wnd_gen_txt">{$WORLD.type}</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="55%" class="wnd_gen_txt">
                                                            <span class="{if $WORLD.profi == 'f'}ind_st1{else}ind_st2{/if}">{$WORLD.description}</span><br>
                                                            <span class="wnd_header_txt_small">{$lang.LEFT} {$WORLD.max_users} {$lang.PLACE_FOR_REG}</span><br>
                                                            <span class="wnd_gen_txt">{$WORLD.worldtype_descr}</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" hieght="5" class="row2">&nbsp;</td>
                                                    </tr>                               
                                                    {/foreach}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <a href="javascript:window.close();" class='wnd_header_txt_large'>{$lang.CLOSE_WINDOW}</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width=4 background="../img/border/02/r.gif"></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/b.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                    <!-- end world collection -->
                                    </table>
                                </td>
                                <td width=5 background="../img/border/01/r.gif"></td>
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
        </td>
    </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align=center class=copyright_txt>{$lang.COPYRIGHT}</td>
    </tr>
    <tr>
        <td align="center" class="copyright_txt"><a href="{$lang.ABOUT_HREF}" target="_blank" class=copyright_txt>{$lang.ABOUT}</a></td>
    </tr>
    <tr> 
        <td align=center>
            {include file='counters.php'}
        </td>
    </tr>
</table>
</body>
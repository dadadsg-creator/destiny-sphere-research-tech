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
                                        {if !empty($MSG)}
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% align="center" class=wnd_inner>
                                                <div class="wnd_gen_txt_large" style="font-size: 10pt;"><b><font color='#993333'><li>ОШИБКА! {$lang.$MSG}</li></font></b></div>
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
                                                    {if !$WORLD_SELECTED}
                                                    <form action="FreeTransfer.php" method="POST">
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <font style="font-size: 10pt;"><b>Выберите мир, в котором вы хотите переехать</b></font>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width="45%" align="right" class="wnd_gen_txt_large">
                                                            <b>Мир:</b>
                                                        </td>
                                                        <td width="55%" align="left" class="wnd_gen_txt_large">
                                                            <select name='world' class=select_gen>
                                                                {foreach from=$WORLDS item=WORLD}
                                                                    <option value="{$WORLD.dbId}_{$WORLD.id_world}">{$WORLD.worldname}
                                                                {/foreach}
                                                            </select>
                                                        </td>
                                                    </tr>                       
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <input type="submit" class="input_button" value="выбрать">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <a href="javascript:window.close();" class='wnd_header_txt_large'>закрыть окно</a>
                                                        </td>
                                                    </tr>
                                                    </form>
                                                    {elseif !$TRANSFER_ALLOWED}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <font style="font-size: 10pt;"><b>Бесплатный переезд в выбранном мире запрещен</b></font>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <a href="javascript:window.close();" class='wnd_header_txt_large'>закрыть окно</a>
                                                        </td>
                                                    </tr>
                                                    
                                                    {elseif $USED_TRANSFER || $TRANSFER_ORDERED}
                                                        {if $TRANSFER_ORDERED}
                                                        <tr>
                                                            <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                                <font style="font-size: 10pt;"><b>Вы заказали переезд вашей соты, переезды осуществляются автоматически каждый вечер.</font>
                                                            </td>
                                                        </tr>
                                                        {else}
                                                            <tr>
                                                            <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                                <font style="font-size: 10pt;"><b>Вы уже использовали бесплатный переезд домашней соты.<br>Вы можете использовать бесплатный переезд не чаще, чем раз в {$TRANSFER_LIMIT} дней.</b></font>
                                                            </td>
                                                        </tr>
                                                        {/if}
                                                    {if isset($INFO)}
                                                    <tr>
                                                        <td width="50%" align="right" class="wnd_gen_txt_large">
                                                            <b>Тип переезда:</b>
                                                        </td>
                                                        <td class="wnd_gen_txt_large">
                                                            {if $INFO.exchange_with}обмен с игроком {$INFO.username}{else}переезд на свободную соту{/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="wnd_gen_txt_large" align="right">
                                                            <b>{if $INFO.transfer_complite == 't'}Старые координаты:{else}Новые координаты:{/if}</b>
                                                        </td>
                                                        <td class="wnd_gen_txt_large">
                                                            {$INFO.ringnumber}.{$INFO.complexnumber}.{$INFO.combnumber}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="wnd_gen_txt_large" align="right">
                                                            <b>Завершен:</b>
                                                        </td>
                                                        <td class="wnd_gen_txt_large">
                                                            {if $INFO.transfer_complite == 't'}да{else}нет{/if}
                                                        </td>
                                                    </tr>
                                                            {if $INFO.transfer_complite != 't'}
                                                            <tr>
                                                                <td align="center" colspan="2">
                                                                    <form action="" method="POST">
                                                                        <input type="hidden" name="action" value="cancel">
                                                                        <input type="submit" class="input_button" value="отменить переезд">
                                                                        <input type="hidden" name='world' value="{$WORLD}">
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                            {/if}
                                                        {/if}
                                                        
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <a href="javascript:window.close();" class='wnd_header_txt_large'>закрыть окно</a>
                                                        </td>
                                                    </tr>
                                                    {else}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <b>Вы можете перевезти соту на любое свободное место или обменяться местами с любым игроком.</b><br>
                                                            <font color="Red"><b>Внимание! При переезде с соты игрока автоматически удаляется Гондола-транспортёр.</b></font>                                                           
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width="50%" style="border-right: 1px solid black;" valign="top" align="center" class="wnd_gen_txt_large">
                                                            <br>
                                                            <div align="center" class='wnd_header_txt_large'><b>Переезд</b></div><br>
                                                            Укажите координаты, на которые вы бы хотели перевезти свою домашнюю соту <br><b>{$COMB.combname}
                                                            ({$COMB.ringnumber}.{$COMB.complexnumber}.{$COMB.combnumber})</b><br>
                                                            <form action="" method="POST">
                                                                кольцо:&nbsp;<b>{$COMB.ringnumber}</b>&nbsp;&nbsp;
                                                                комплекс:&nbsp;<input class=select_gen type="text" size="4" maxlength="4" name="comb_coords[complex]">&nbsp;&nbsp;
                                                                сота:&nbsp;<input class=select_gen type="text" size="1" maxlength="1" name="comb_coords[comb]">&nbsp;&nbsp;<br><br>
                                                                <input type="hidden" name="action" value="transfer">
                                                                <input type="submit" class="input_button" value="заказать переезд">
                                                                <input type="hidden" name='world' value="{$WORLD}">
                                                                <input type="hidden" name='comb_coords[ring]' value="{$COMB.ringnumber}">
                                                            </form>
                                                        </td>
                                                        <td width="50%" valign="top" class="wnd_gen_txt_large" align="center">
                                                            <br>
                                                            <div align="center" class='wnd_header_txt_large'><b>Обмен</b></div><br>
                                                            Укажите имя игрока, с которым вы хотите обменяться местами <br>
                                                            (выбранный игрок должен заказать обмен с вами)<br><b>{$COMB.combname}
                                                            ({$COMB.ringnumber}.{$COMB.complexnumber}.{$COMB.combnumber})</b><br>
                                                            <form action="" method="POST">
                                                                имя игрока:&nbsp;<input class="input_login_wnd" type="text" maxlength="20" name="exchange_with">&nbsp;&nbsp;<br><br>
                                                                <input type="hidden" name="action" value="exchange">
                                                                <input type="submit" class="input_button" value="заказать обмен">
                                                                <input type="hidden" name='world' value="{$WORLD}">
                                                            </form>
                                                            {if count($EXCHANGE) > 0}
                                                                Игрок{if count($EXCHANGE)>1}и{/if} заказавш{if count($EXCHANGE)>1}ие{else}ий{/if} обмен с вами:<br><b>
                                                                {section name=i loop=$EXCHANGE}
                                                                    {$EXCHANGE[i].username}<br> 
                                                                {/section}</b>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large" style="height: 20px;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" align="center" class="wnd_gen_txt_large">
                                                            <form action="RingView.php" method="POST" target="_blank">
                                                                <input type="hidden" name="ring" value="{$COMB.ringnumber}">
                                                                <input type="hidden" name='world' value="{$WORLD}">
                                                                <input type="submit" class="input_button" value="карта кольца">
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <a href="javascript:window.close();" class='wnd_header_txt_large'>закрыть окно</a>
                                                        </td>
                                                    </tr>
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
                                        {if $CAN_SWAP}
                                        <tr height=25>
                                            <td colspan=3 align=center valign="bottom">
                                                <img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>{$lang.CAPTION_SWAP}</b></span></td>
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
                                                    {if $USED_TRANSFER_MELS}
                                                        <tr>
                                                            <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                                <font style="font-size: 10pt;"><b>Вы только что заказали переезд по мельсиону, переезды осуществляются автоматически каждый вечер.</font>
                                                            </td>
                                                        </tr>   
                                                    {if isset($INFO)}
                                                    <tr>
                                                        <td width="50%" align="right" class="wnd_gen_txt_large">
                                                            <b>Тип переезда:</b>
                                                        </td>
                                                        <td class="wnd_gen_txt_large">
                                                            {if $INFO.exchange_with}обмен с игроком {$INFO.username}{else}переезд на свободную соту{/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="wnd_gen_txt_large" align="right">
                                                            <b>{if $INFO.transfer_complite == 't'}Старые координаты:{else}Новые координаты:{/if}</b>
                                                        </td>
                                                        <td class="wnd_gen_txt_large">
                                                            {$INFO.ringnumber}.{$INFO.complexnumber}.{$INFO.combnumber}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="wnd_gen_txt_large" align="right">
                                                            <b>Завершен:</b>
                                                        </td>
                                                        <td class="wnd_gen_txt_large">
                                                            {if $INFO.transfer_complite == 't'}да{else}нет{/if}
                                                        </td>
                                                    </tr>
                                                    {/if}
                                                    {else}
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large">
                                                            <span class='wnd_gen_txt_large'>
                                                            Вы можете заказать переезд колонии по кольцу или обменяться колониями с любым игроком.<br><br>
                                                            <b>Внимание!</b> При переезде колонии на ней будет удалено здание гондолы-транспортера.<br>
                                                            <b>Внимание!</b> Переезд можно заказать только один раз.<br>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width="50%" style="border-right: 1px solid black;" valign="top" align="center" class="wnd_gen_txt_large">
                                                            
                                                                <br>
                                                                <div align="center" class='wnd_header_txt_large'><b>Переезд</b></div><br>
                                                                Укажите координаты, на которые вы бы хотели перевезти свою колонию <br>
                                                                <form action="" method="POST">
                                                                {if count($COLONIES) > 1}
                                                                    <select name="id_comb">
                                                                    {foreach from=$COLONIES item=COLONY}
                                                                        <option value="{$COLONY.id_comb}"><b>{$COLONY.combname}({$COLONY.ringnumber}.{$COLONY.complexnumber}.{$COLONY.combnumber})</b><br>
                                                                    {/foreach}
                                                                    </select>
                                                                {else}
                                                                    <input type="hidden" name="id_comb" value="{$COLONIES.0.id_comb}">
                                                                    <b>{$COLONIES.0.combname}({$COLONIES.0.ringnumber}.{$COLONIES.0.complexnumber}.{$COLONIES.0.combnumber})</b><br>
                                                                {/if}
                                                                <br>
    
                                                                    кольцо:&nbsp;<b>4</b>&nbsp;&nbsp;
                                                                    комплекс:&nbsp;<input class=select_gen type="text" size="4" maxlength="4" name="comb_coords[complex]">&nbsp;&nbsp;
                                                                    сота:&nbsp;<input class=select_gen type="text" size="1" maxlength="1" name="comb_coords[comb]">&nbsp;&nbsp;<br><br>
                                                                    <input type="hidden" name="action" value="swap">
                                                                    <input type="submit" class="input_button" value="заказать переезд">
                                                                    <input type="hidden" name='world' value="{$WORLD}">
                                                                    <input type="hidden" name='comb_coords[ring]' value="4">
                                                                </form>
                                                                </td>
                                                                <td width="50%" valign="top" class="wnd_gen_txt_large" align="center">
                                                                <br>
                                                                <div align="center" class='wnd_header_txt_large'><b>Обмен</b></div><br>
                                                                Укажите имя игрока, с которым вы хотите обменяться колониями <br>
                                                                (выбранный игрок должен заказать обмен с вами)<br>
                                                                <form action="" method="POST">
                                                                {if count($COLONIES) > 1}
                                                                    <select name="id_comb">
                                                                    {foreach from=$COLONIES item=COLONY}
                                                                        <option value="{$COLONY.id_comb}"><b>{$COLONY.combname}({$COLONY.ringnumber}.{$COLONY.complexnumber}.{$COLONY.combnumber})</b><br>
                                                                    {/foreach}
                                                                    </select>
                                                                {else}
                                                                    <input type="hidden" name="id_comb" value="{$COLONIES.0.id_comb}">
                                                                    <b>{$COLONIES.0.combname}({$COLONIES.0.ringnumber}.{$COLONIES.0.complexnumber}.{$COLONIES.0.combnumber})</b><br>
                                                                {/if}
                                                                <br>
                                                                    имя игрока:&nbsp;<input class="input_login_wnd" type="text" maxlength="20" name="exchange_with">&nbsp;&nbsp;<br><br>
                                                                    кольцо:&nbsp;<b>4</b>&nbsp;&nbsp;
                                                                    комплекс:&nbsp;<input class=select_gen type="text" size="4" maxlength="4" name="exchange_comb_coords[complex]">&nbsp;&nbsp;
                                                                    сота:&nbsp;<input class=select_gen type="text" size="1" maxlength="1" name="exchange_comb_coords[comb]">&nbsp;&nbsp;<br><br>
                                                                    <input type="hidden" name="action" value="exchange_swap">
                                                                    <input type="submit" class="input_button" value="заказать переезд">
                                                                    <input type="hidden" name='world' value="{$WORLD}">
                                                                    <input type="hidden" name='exchange_comb_coords[ring]' value="4">
                                                                </form>
                                                                {if count($EXCHANGE_MELS) > 0}
                                                                    Игрок{if count($EXCHANGE_MELS)>1}и{/if} заказавш{if count($EXCHANGE_MELS)>1}ие{else}ий{/if} обмен с вами:<br><b>
                                                                    {section name=i loop=$EXCHANGE_MELS}
                                                                        {$EXCHANGE_MELS[i].username}<br>    
                                                                    {/section}</b>
                                                                {/if}
                                                            
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=2 align="center" class="wnd_gen_txt_large" style="height: 20px;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" align="center" class="wnd_gen_txt_large">
                                                            <form action="RingView.php" method="POST" target="_blank">
                                                                <input type="hidden" name="ring" value="4">
                                                                <input type="hidden" name='world' value="{$WORLD}">
                                                                <input type="submit" class="input_button" value="карта кольца">
                                                            </form>
                                                        </td>
                                                    </tr>
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
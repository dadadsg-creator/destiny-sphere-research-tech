<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.CHARSET}">
    <meta http-equiv="Content-Style-Type" content="text/css">

    <title>{$lang.title}</title>
    <link rel="stylesheet" href="../dsstyle.css" type="text/css">
</head>

<body>

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
        <td height=100% valign=top>
            <table width="100%" height=100% border="0" cellpadding="0" cellspacing="10">
                <tr>
                <td height=100% valign=top>
                        <table width="100%" height=100% border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td width=5 height=5><img src="../img/border/01/t_l.gif" border=0 width=5 height=5></td>
                                <td height=5 background="../img/border/01/t.gif"></td>
                                <td width=10 height=5><img src="../img/border/01/t_r.gif" border=0 width=10 height=5></td>
                            </tr>
                            <tr>
                                <td width=5 background="../img/border/01/l.gif"></td>
                                <td width=100% height=100% class=wnd_outer valign=top>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td colspan=3 align=center>
                                                <img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>{$lang.caption}</b></span></td>
                                        </tr>
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% height=100% class=wnd_inner align=center>
                                                <table width=100% height=100% cellpadding='2' cellspacing='1' border='0'>
                                                    <tr>
                                                        <td width=100% align="left" valign=top class=wnd_gen_txt_large>
                                                            <table width=100% cellpadding='2' cellspacing='1' border='0'>
                                                                <tr>
                                                                    <td colspan=3 class=wnd_header_txt_large align=center>
                                                                        {$lang.efficiency} {$UNIT.name} {$lang.vs_race} {$RACE_NAME}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class=wnd_gen_txt_large align=left valign=top>
                                                                        <table cellpadding=0 cellspacing=1 border=0 style="border: solid 1px #444444;">
                                                                            <form action="" method="GET">
                                                                            <tr>
                                                                                <td colspan=2>
                                                                                    <!--input type="hidden" name="race" value="{$OPONENT_RACE}"-->
                                                                                    <select name="unit" class="select_gen">
                                                                                        {foreach key=unit_id item=unit from=$USER_RACE_UNITS}
                                                                                            {if $unit.id_unit}
                                                                                                <option value="{$unit.id_unit}" {if $unit.id_unit == $UNIT_ID}selected{/if}>{$unit.text}
                                                                                            {/if}
                                                                                        {/foreach}
                                                                                    </select><input type="submit" class=input_button value="{$lang.compare}">
                                                                                </td>
                                                                            </tr>
                                                                            <!--/form-->
                                                                            <tr>
                                                                                <td class=wnd_gen_txt_large align=center valign=top style="border: solid 1px #666666; padding:3px;">
                                                                                    <img src="../img/support/units/{$UNIT.img}.gif" border=0 alt="{$UNIT.name}"><br><b>{$UNIT.name}</b>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class=wnd_gen_txt_large align=center valign=top>
                                                                        <table cellpadding=0 cellspacing=1 border=0>
                                                                            <tr>
                                                                                <td class=wnd_gen_txt_large align=center valign=top style="padding:3px;">
                                                                                    <img src="../img/graph/vs.gif" border=0 alt="vs"><br>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class=wnd_gen_txt_large align=left rowspan=2>
                                                                        <table cellpadding=0 cellspacing=1 border=0 style="border: solid 1px #444444;">
                                                                            <!--form action="" method="GET"-->
                                                                            <tr>
                                                                                <td colspan=6>
                                                                                    <!--input type="hidden" name="unit" value="{$UNIT_ID}"-->
                                                                                    <select name="race" class="select_gen">
                                                                                        {foreach key=race_id item=race from=$RACES}
                                                                                            {if $race_id}
                                                                                                <option value="{$race_id}" {if $race_id == $OPONENT_RACE}selected{/if}>{$race}
                                                                                            {/if}
                                                                                        {/foreach}
                                                                                    </select><input type="submit" class=input_button value="{$lang.compare}">
                                                                                </td>
                                                                            </tr></form>
                                                                            <tr>
                                                                                <td colspan="6" class="wnd_gen_txt_large" style="padding: 2px;"">{$lang.legend}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" class=wnd_gen_txt_large colspan="2"><b>атакующий</b></td>
                                                                                <td>&nbsp;</td>
                                                                                <td>&nbsp;</td>
                                                                                <td align="center" class=wnd_gen_txt_large colspan="2"><b>защитник</b></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" class=wnd_gen_txt_large>
                                                                                    <Img src="../img/support/resource/uron.gif">
                                                                                </td>
                                                                                <td align="center" class=wnd_gen_txt_large>
                                                                                    <Img src="../img/support/resource/br.gif">
                                                                                </td>
                                                                                <td align="center" class=wnd_gen_txt_large>&nbsp;</td>
                                                                                <td align="center" class=wnd_gen_txt_large><b>результат</b></td>
                                                                                <td align="center" class=wnd_gen_txt_large>
                                                                                    <Img src="../img/support/resource/uron.gif">
                                                                                </td>
                                                                                <td align="center" class=wnd_gen_txt_large>
                                                                                    <Img src="../img/support/resource/br.gif">
                                                                                </td>
                                                                            </tr>
                                                                            {section name=i loop=$UNITS}
                                                                                <tr>
                                                                                    <td align="center" style="border: solid 1px #666666; padding:3px; font-size: 10pt;" class=wnd_gen_txt_large><b>{$UNITS[i].k_attack}</b>%</td>
                                                                                    <td align="center" style="border: solid 1px #666666; padding:1px;">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0px;">
                                                                                            <tr style="height: 100px">
                                                                                                <td valign="middle" align="center" class=wnd_gen_txt_large style="font-size: 10pt; width: 50px">
                                                                                                    <b>{$UNITS[i].fate_attack}</b>%
                                                                                                </td>
                                                                                                <td valign="bottom" align="right" width="9px">
                                                                                                    <Img src="./scaleImg.php?s={$UNITS[i].fate_attack}" width="9px" height="100px">
                                                                                                </td>
                                                                                            <tr>
                                                                                        </table>
                                                                                    </td>
                                                                                    <td align="center" valign=middle class=wnd_gen_txt_large style="border: solid 1px #666666; padding:3px;"><!--b>{$UNITS[i].name}</b--><IMG alt="{$UNITS[i].name}" src="../img/support/units/{$UNITS[i].img}.gif" border="0"><br><b>{$UNITS[i].name}</b></td>
                                                                                    <td align="center" style="border: solid 1px #666666; padding:3px; font-size: 10pt; background-color: {$UNITS[i].eff}" class=wnd_gen_txt_large><b>{$UNITS[i].num}</b></td>
                                                                                    <td align="center" style="border: solid 1px #666666; padding:3px; font-size: 10pt;" class=wnd_gen_txt_large><b>{$UNITS[i].k_defence}</b>%</td>
                                                                                    <td align="center" style="border: solid 1px #666666; padding:3px; font-size: 10pt;" class=wnd_gen_txt_large>
                                                                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0px;">
                                                                                            <tr style="height: 100px">
                                                                                                <td valign="middle" align="center" class=wnd_gen_txt_large style="font-size: 10pt; width: 50px">
                                                                                                    <b>{$UNITS[i].fate_defence}</b>%
                                                                                                </td>
                                                                                                <td valign="bottom" align="right" width="9px">
                                                                                                    <Img src="./scaleImg.php?s={$UNITS[i].fate_defence}" width="9px" height="100px">
                                                                                                </td>
                                                                                            <tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            {/section}
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width="50%" colspan=2 class=wnd_gen_txt_large align=left valign=top>
                                                                        <form action="" method="GET">   
                                                                            <input type="hidden" name="unit" value="{$UNIT_ID}">
                                                                            <input type="hidden" name="race" value="{$RACE}">
                                                                            <table cellpadding="0" cellspacing="0" border=0 style="padding:3px; border: solid 1px #444444;">
                                                                                <tr>
                                                                                    <td align=right class=wnd_gen_txt_large><b>{$lang.your_race}</b></td>
                                                                                    <td align=left class=wnd_gen_txt_large><b>{$lang.oponent_race}</b></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align=right class=wnd_gen_txt_large>
                                                                                        <select name="user_race" class="select_gen">
                                                                                            {html_options options=$RACES selected=$USER_RACE}
                                                                                        </select>
                                                                                    </td>
                                                                                    <td align=left class=wnd_gen_txt_large>
                                                                                        <select name="oponent_race" class="select_gen">
                                                                                            {html_options options=$RACES selected=$OPONENT_RACE}
                                                                                        </select>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align=right class=wnd_gen_txt_large><b>{$lang.your_religion}</b></td>
                                                                                    <td align=left class=wnd_gen_txt_large><b>{$lang.oponent_religion}</b></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align=right class=wnd_gen_txt_large>
                                                                                        <select name="user_religion" class="select_gen">
                                                                                            {html_options options=$RELIGIONS selected=$USER_RELIGION}
                                                                                        </select>
                                                                                    </td>
                                                                                    <td align=left class=wnd_gen_txt_large>
                                                                                        <select name="oponent_religion" class="select_gen">
                                                                                            {html_options options=$RELIGIONS selected=$OPONENT_RELIGION}
                                                                                        </select>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td colspan=2 align="center" vAlign="center"><input type="submit" value="{$lang.compare}" class=input_button></td>
                                                                                </tr>
                                                                            </table>
                                                                        </form>
                                                                            <table cellpadding="0" cellspacing="0" border=0 style="padding:10px; border: solid 1px #444444;">
                                                                                <tr>
                                                                                    <td align=left class=wnd_gen_txt_large>
                                                                                        {$lang.text01}
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
        </td>
    </tr>
    <tr>
        <td align=center class=copyright_txt valign=bottom>
            {$lang.copyright}
        </td>
    </tr>
    <tr>
        <td align=center>
            {include file='counters.php'}
        </td>
    </tr>
</table>
</body>
</html>
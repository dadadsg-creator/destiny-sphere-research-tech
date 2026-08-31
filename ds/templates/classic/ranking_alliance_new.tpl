<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.CHARSET}">
    <meta http-equiv="Content-Style-Type" content="text/css">

    <title>{$lang.title}</title>
    <link rel="stylesheet" href="../dsstyle.css" type="text/css">
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>

<body>
<table width=100% border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td valign=top>
            <table width=100% border=0 cellpadding=0 cellspacing=0>
                <!-- header begin -->
                    {php}
                        require_once  $_SERVER['DOCUMENT_ROOT']."/ds/templates/header2.tpl";
                    {/php}
                <!-- header end -->
            </table>
        </td>
    </tr>
    <tr>
        <td height=100% valign=top>
            <table width="100%" height=100% border="0" cellpadding="0" cellspacing="5">
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
                                                <img src="../img/elements/cap_div.gif" border=0><span class=wnd_header_txt style="font-size:14px;">{$CURRENT_LTL_WORLDNAME}</td>
                                        </tr>
                                        <!---------------->
                                        <tr><td height=5 colspan=3></td></tr>
                                        <!---------------->
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% class=wnd_inner align=center>
                                                <table width="100%" cellpadding='0' cellspacing='0' border='0'>
                                                    <tr>
                                                        <td width="100%" align="left" valign=middle class=wnd_gen_txt_large>
                                                        <table width="100%" cellpadding="0" cellspacing="3" border="0">
                                                        <form>
                                                            <tr>
                                                                <td align="right" nowrap>
                                                                        <select name="world_list" style="width: 200px;" class=select_gen>
                                                                            {html_options options=$WORLD_SELECT_OPTIONS selected=$WORLD_SELECT_OPTIONS_CURRENT}
                                                                        </select>
                                                                </td>
                                                                <td align="left" nowrap>
                                                                    <input type="button" value="{$lang.move}" ONCLICK="top.location.href = this.form.world_list.options[this.form.world_list.selectedIndex].value" class="input_button">
                                                                </td>
                                                                <td width=100%></td>
                                                            <td align="center" nowrap>
                                                                        <a href="ranking_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&offset={$CURRENT_OFFSET}" class="wnd_header_txt_large">{$lang.cap_btn_ranking}</a> | <a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}" class="wnd_header_txt_large">{$lang.cap_btn_ranking_alliance}</a>
                                                                    </td>
                                                        </tr>
                                                            </form>
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
                                        <!---------------->
                                        <tr><td colspan=3 height=10></td></tr>
                                        <!---------------->
                                        <tr>
                                            <td colspan=3>
        <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
        <tr>
            <td>
                <table width="100%" border="0" cellpadding="0" cellspacing="1" class=ranking_table>
                <tr>
                            <td class="ranking_header" valign="middle" widtd="9%">{$lang.number}</td>
                            <td class="ranking_header" valign="middle" widtd="13%"><a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&sort=alliancename&dir=asc" class="ranking_header_text">{$lang.name}</a></td>
                            <td class="ranking_header" valign="middle" widtd="13%"><a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&sort=alliancetag&dir=asc" class="ranking_header_text">{$lang.small_name}</a></td>
                            <td class="ranking_header" valign="middle" widtd="13%"><a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&sort=members&dir=desc" class="ranking_header_text">{$lang.count}</a></td>
                            <td class="ranking_header" valign="middle" widtd="13%"><a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&sort=totalpoints&dir=desc" class="ranking_header_text">{$lang.exp}</a></td>
                            <td class="ranking_header" valign="middle" widtd="13%"><a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&sort=averagepoints&dir=desc" class="ranking_header_text">{$lang.middle_exp}</a></td>
                            <td class="ranking_header" valign="middle" widtd="13%"><a href="ranking_alliance_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&sort=sum_exp&dir=desc" class="ranking_header_text">{$lang.sum_exp}</a></td>
                        </tr>

                        {section name=row_elements loop=$ROW_ELEMENT}

                        <tr {if $ROW_ELEMENT[row_elements].row % 2}class="ranking_table_01" {else}class="ranking_table_02" {/if}>

                            {if count($ROW_ELEMENT) > 0}
                            <td align=center class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].row} </td>
                            <td class=wnd_gen_ranking_large><a href="ranking_new.php?world={$CURRENT_WORLDID}&dbid={$CURRENT_DBID}&alliance={$ROW_ELEMENT[row_elements].id_alliance}" class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].alliancename} </a></td>
                            <td align=center class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].alliancetag} </td>
                            <td align=center class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].members} </td>
                            <td align=right class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].totalpoints} </td>
                            <td align=right class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].averagepoints} </td>
                            <td align=right class=wnd_gen_ranking_large> {$ROW_ELEMENT[row_elements].sum_exp} </td>
                            {/if}

                        </tr>
                        {/section}
                    </table>
                </td>
            </tr>
        </table>
                                            </td>
                                        </tr>
                                        <!---------------->
                                        <tr><td colspan=3 height=10></td></tr>
                                        <!---------------->
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% height=100% class=wnd_inner align=center valign=top>
                                                <table width="100%" height=100% cellpadding='0' cellspacing='1' border='0'>
                                                    <tr>
                                                        <td width="100%" align="left" valign=top class=wnd_gen_txt_large>
                                                            <table width='100%' border=0 cellpadding=0 cellspacing=0>
                                                                <tr>
                                                                    <td>
                                                                        {$NAVIGATION_ARRAY.button_left}</td>
                                                                    <td align=center class=wnd_gen_txt_large>
                                                                        {$NAVIGATION_ARRAY.fist_page}</td>
                                                                    <td align=center class=wnd_gen_txt_large>
                                                                        <b>{$NAVIGATION_ARRAY.current_page}</b></td>
                                                                    <td align=center class=wnd_gen_txt_large>
                                                                        {$NAVIGATION_ARRAY.last_page}</td>
                                                                    <td align=right>
                                                                        {$NAVIGATION_ARRAY.button_right}</td>
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
                                        <!---------------->
                                        <tr><td colspan=3 height=10></td></tr>
                                        <!---------------->
                                        <tr>
                                            <td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
                                            <td width=100% height=4 background="../img/border/02/t.gif"></td>
                                            <td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
                                        </tr>
                                        <tr>
                                            <td width=4 background="../img/border/02/l.gif"></td>
                                            <td width=100% height=100% class=wnd_inner align=center valign=top>
                                                <table width="100%" height=100% cellpadding='0' cellspacing='1' border='0'>
                                                    <tr>
                                                        <td width="100%" align="left" valign=top class=wnd_gen_txt_large>
                                                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                                                <form action="{$SCRIPT_NAME}" method="get" name="searchPlayer">
                                                                <tr>
                                                                    <td width="50%" align="right" valign="middle">
                                                                        <input name="world" type="hidden" value="{$CURRENT_WORLDID}">
                                                                        <input name="dbid" type="hidden" value="{$CURRENT_DBID}">
                                                                        <span class="wnd_gen_txt_large"><b>{$lang.alliance_search}</b></span>&nbsp;<input name="player" class="input_gen" type="text" size="15"></td>
                                                                <td width="50%" align="left" >
                                                                    &nbsp;<input NAME="submit" TYPE="image" src="../img/buttons/btn_search.gif" border="0"></td>
                                                                </tr>
                                                                </form>
                                                            </table></td>
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
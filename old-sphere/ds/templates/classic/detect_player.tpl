<html>
<head>
    <meta http-equiv="Refresh" content="{$REFRESH}; URL={$URL_INDEX}">

    <meta http-equiv="Content-Type" content="text/html; charset={$lang.CHARSET}">
    <meta http-equiv="Content-Style-Type" content="text/css">

    <title>{$lang.title}</title>
    <link rel="stylesheet" href="../dsstyle.css" type="text/css">
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
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
                                                <table height=100% cellpadding='2' cellspacing='1' border='0'>
                                                    <tr>
                                                        <td align="left" valign=middle class=wnd_gen_txt_large>
                                                            <table cellpadding='2' cellspacing='1' border='0'>
                                                        <tr>
                                                                    {if count($CONTENT) > 0}
                                                                        <td align="center" valign="middle" class=wnd_gen_txt_large>
                                                                    <strong>{$lang.browser}: </strong>{$CONTENT[0]}<br>
                                                                            <strong>{$lang.browser_version}: </strong>{$CONTENT[1]}<br>
                                                                            <strong>{$lang.OS}: </strong>{$CONTENT[2]}<br>
                                                                            <strong>{$lang.Flash_Version}: </strong>{$CONTENT[3]}<br>
                                                                            <strong>{$lang.html}: </strong>{$CONTENT[4]}<br>
                                                                            <strong>{$lang.java}: </strong>{$CONTENT[5]}<br>
                                                                            <strong>{$lang.iframe}: </strong>{$CONTENT[6]}<br>
                                                                            <strong>{$lang.cookies}Cookies: </strong>{$CONTENT[7]}<br>
                                                                            <br><br>
                                                                            <a href="{$URL_INDEX}" class="wnd_gen_txt_large"><b>{$lang.to_game} >></b></a>
                                                                    {else}
                                                                <td align="center" valign="middle" class=wnd_gen_txt_large>
                                                                    <b>{$lang.CheckFlashPL}</b><br><br><br>
                                                                    <a href="{$URL_INDEX}" class="wnd_gen_txt_large">{$lang.skip}</a><br><br><br>
                                                                    <OBJECT classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0' WIDTH='0' HEIGHT='0' id='detect_player' ALIGN=''>
                                                                                <PARAM NAME=movie VALUE='{$URL_SWF}'>
                                                                                <PARAM NAME=quality VALUE=high>
                                                                                <PARAM NAME=bgcolor VALUE=#FFFFFF>
                                                                                <EMBED src='{$URL_SWF}' quality=high bgcolor=#FFFFFF  WIDTH='0' HEIGHT='0' NAME='detect_player' ALIGN='' TYPE='application/x-shockwave-flash' PLUGINSPAGE='http://www.macromedia.com/go/getflashplayer'></EMBED>
                                                                            </OBJECT>
                                                            </td>
                                                                    {/if}
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
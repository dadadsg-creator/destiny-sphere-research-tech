<html>
<head>
    <meta http-equiv="Content-Style-Type" content="text/css">
    <title>{$lang.TITLE}</title>
    <link rel="stylesheet" href="cabinetstyle.css" type="text/css">
</head>

<body>
<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td align=center>
            <table border=0 cellpadding=0 cellspacing=0>
                <tr>
                    <td valign=top>
                        <table border=0 cellpadding=0 cellspacing=0>
                            <tr>
                                <td>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td><img src="img/cabinet/border/03/t_l.gif" border=0></td>
                                            <td width=100% background="img/cabinet/border/03/t.gif"></td>
                                            <td><img src="img/cabinet/border/03/t_r.gif" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td background="img/cabinet/border/03/l.gif"></td>
                                            <td bgcolor="#ffffff" style="padding:6px;">
<!-- BODY-->
    <div align="center">
    <div align="center" style="color: #DD1010">{$PARAMS.MSG}</div><br>
        {if $PARAMS.from_login}
        <form action="user_cabinet.php?action=login_page" method="GET">
            <input type="hidden" name="action" value="login_page">
        {else}
        <form action="index_start.php" method="GET">
            <input type="hidden" name="ck" value="{$PARAMS.CK}">
            <input type="hidden" name="{$PARAMS.S_NAME}" value="{$PARAMS.S_ID}">
        {/if}
            <input type="submit" value="{$lang.BACK}" class=submit01>
        </form>
        
    </div>
<!-- /BODY-->
                                            </td>
                                            <td background="img/cabinet/border/03/r.gif"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
                                        <tr>
                                            <td><img src="img/cabinet/border/03/b_l.gif" border=0></td>
                                            <td width=100% background="img/cabinet/border/03/b.gif"></td>
                                            <td><img src="img/cabinet/border/03/b_r.gif" border=0></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            
        </td>
    </tr>
    <tr>
        <td align=center class=copyrights>
            {$lang.COPYRIGHT}</td>
    </tr>
</table>

</body>
</html>
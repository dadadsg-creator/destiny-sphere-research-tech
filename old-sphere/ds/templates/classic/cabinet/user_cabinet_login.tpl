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
                                            
                                            <b>{$lang.ENTER_INFO}</b>
                                            
                                            <form action="user_cabinet.php" method="POST">
                                            <input type="hidden" name="action" value="login">
                                            <input type="hidden" name="from_login" value="1">
                                            <table width="100%">
                                                <tr>
                                                    <td width="45%" align="right">
                                                        {$lang.LOGIN}:&nbsp;
                                                    </td>
                                                    <td width="55%">
                                                        <input type="text" name="login" maxlength="30">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="45%" align="right">
                                                        {$lang.PASSWORD}:&nbsp;
                                                    </td>
                                                    <td width="55%">
                                                        <input type="password" name="password" maxlength="30">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td>
                                                        <input type="submit" value="{$lang.CABINET_SUBMIT}" class="submit01">
                                                    </td>
                                                </tr>
                                            </table>                                            
                                            </form>
                                            
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
{$lang.COUNTERS}
</body>
</html>
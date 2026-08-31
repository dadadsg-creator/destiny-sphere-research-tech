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
{if key_exists('accounts', $PARAMS)}
<form action="user_cabinet.php" method="post">
    <input type="hidden" name="action" value="account_stat">
    <select name="acÒount">
    {foreach from=$PARAMS.accounts key=NAME item=V}
        <option value="{$NAME}">{$NAME}
    {/foreach}
    </select>&nbsp;<input type="submit" value="{$lang.STAT}" class=submit01><br><br>
    </form>
    
    <form action="user_cabinet.php" method="POST">
        <input type="hidden" name="action" value="index">
<!--        <input type="submit" value="{$lang.BACK}" class=submit01> -->
    </form>
    <div align="center"><a href="javascript:window.close();">«¿ –€“‹</a></div>
{else}
<table width="100%" border="0" style="border: 1px solid black; border-collapse:collapse;" cellspacing="0" cellpadding="2">
    <tr>
        <td align="left"><a href="user_cabinet.php?action=account_stat">Õ¿«¿ƒ</a></td>
        <td align="right"><a href="javascript:window.close();">«¿ –€“‹</a></td>
    </tr>
    {foreach from=$PARAMS.stat item=STAT}
    <tr>
        <td style="border: 1px solid black;">{$STAT.DATE}</td>
        <td style="border: 1px solid black;">{$STAT.ACTION}</td>
    </tr>
    <tr>
        <td colspan="2" style="border: 1px solid black;">{if empty($STAT.INFO)}&nbsp;{else}{$STAT.INFO}{/if}</td>
    </tr>
    {/foreach}
</table>
{/if}
                                            
                                            
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
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
{if $CONFIRM == 'EXCHANGE'}
    {if isset($PARAMS.valid)}
    {$lang.EXCHANGE_CONFIRM} <b>{$PARAMS.gc}</b> {$lang.GOLD_CREDITS} {$lang.ON_SILVER} <b>{$EXCHANGE_COURSE*$PARAMS.gc}</b> {$lang.SILVER_CREDITS}?<br><br>
    <table width="100%">
        <tr>
            <td align="center" valign="bottom" colspan="2">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="exchange">
                    <input type="hidden" name="gc" value="{$PARAMS.gc}">
                    {if $PARAMS.login_protection}
                    <table width="100%">
                        <tr>
                            <td align="right" width="40%">
                                {$lang.LOGIN}: 
                            </td>
                            <td align="left" width="60%">
                                <input type="text" name="login" size="20" maxlength="30">
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                {$lang.PASSWORD}: 
                            </td>
                            <td align="left">
                                <input type="password" name="password" size="20" maxlength="30">
                            </td>
                        </tr>
                    </table>
                    {/if}
            </td>
        </tr>
        <tr>
            <td width="50%" align="right">
                    <input type="submit" value="{$lang.YES}" class=submit_yes>
                </form>
            </td>
            <td width="50%" align="left">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="index">
                    <input type="submit" value="{$lang.NO}" class=submit_no>
                </form>
            </td>
        </tr>
    </table>
    {else}
    <div align="center">
    <div align="center" style="color: #DD1010">{$PARAMS.MSG}</div><br>
        <form action="" method="POST">
            <input type="hidden" name="action" value="index">
            <input type="submit" value="{$lang.BACK}" class=submit01>
        </form>
    </div>
    {/if}
{elseif $CONFIRM == 'TRANSFER_TO_INSTANCE'}
    {if isset($PARAMS.valid)}
    <div align="center">{$lang.TRANSFER_CONFIRM} <b>{$PARAMS.gc}</b> {$lang.GOLD_CREDITS} {$lang.AND} <b>{$PARAMS.sc}</b> {$lang.SILVER_CREDITS}?<br>
    {$lang.TO_WORLD} "<b>{$PARAMS.WORLD}</b>"</div><br>
    <table width="100%">
        <tr>
            <td align="center" valign="bottom" colspan="2">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="transfer_to_instance">
                    <input type="hidden" name="gc" value="{$PARAMS.gc}">
                    <input type="hidden" name="sc" value="{$PARAMS.sc}">
                    <input type="hidden" name="world" value="{$PARAMS.world}">
                    {if $PARAMS.login_protection}
                    <table width="100%">
                        <tr>
                            <td align="right" width="40%">
                                {$lang.LOGIN}: 
                            </td>
                            <td align="left" width="60%">
                                <input type="text" name="login" size="20" maxlength="30">
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                {$lang.PASSWORD}: 
                            </td>
                            <td align="left">
                                <input type="password" name="password" size="20" maxlength="30">
                            </td>
                        </tr>
                    </table>
                    {/if}
            </td>
        </tr>
        <tr>
            <td width="50%" align="right">
                    <input type="submit" value="{$lang.YES}" class=submit_yes>
                </form>
            </td>
            <td width="50%" align="left">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="index">
                    <input type="submit" value="{$lang.NO}" class=submit_no>
                </form>
            </td>
        </tr>
    </table>
    {else}
    <div align="center">
    <div align="center" style="color: #DD1010">{$PARAMS.MSG}</div><br>
        <form action="" method="POST">
            <input type="hidden" name="action" value="index">
            <input type="submit" value="{$lang.BACK}" class=submit01>
        </form>
    </div>
    {/if}
{elseif $CONFIRM == 'TRANSFER_TO_USER'}
    {if isset($PARAMS.valid)}
    <div align="center">{$lang.TRANSFER_CONFIRM} <b>{$PARAMS.gc}</b> {$lang.GOLD_CREDITS} {$lang.AND} <b>{$PARAMS.sc}</b> {$lang.SILVER_CREDITS}?<br>
    {$lang.FROM_WORLD} "<b>{$PARAMS.WORLD}</b>"<br>
    {if $PARAMS.TRANSFER_TAX > 0}
    <font color="red">{$lang.TRANSFER_TAX} <b>{$PARAMS.TRANSFER_TAX}</b> {$lang.SILVER_CREDITS}</font></div><br></div><br>
    {/if}
    <table width="100%">
        <tr>
            <td align="center" valign="bottom" colspan="2">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="transfer_to_user">
                    <input type="hidden" name="gc" value="{$PARAMS.gc}">
                    <input type="hidden" name="sc" value="{$PARAMS.sc}">
                    <input type="hidden" name="world" value="{$PARAMS.world}">
                    {if $PARAMS.login_protection}
                    <table width="100%">
                        <tr>
                            <td align="right" width="40%">
                                {$lang.LOGIN}: 
                            </td>
                            <td align="left" width="60%">
                                <input type="text" name="login" size="20" maxlength="30">
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                {$lang.PASSWORD}: 
                            </td>
                            <td align="left">
                                <input type="password" name="password" size="20" maxlength="30">
                            </td>
                        </tr>
                    </table>
                    {/if}
            </td>
        </tr>
        <tr>
            <td width="50%" align="right">
                    <input type="submit" value="{$lang.YES}" class=submit_yes>
                </form>
            </td>
            <td width="50%" align="left">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="index">
                    <input type="submit" value="{$lang.NO}" class=submit_no>
                </form>
            </td>
        </tr>
    </table>
    {else}
    <div align="center">
    <div align="center" style="color: #DD1010">{$PARAMS.MSG}</div><br>
        <form action="" method="POST">
            <input type="hidden" name="action" value="index">
            <input type="submit" value="{$lang.BACK}" class=submit01>
        </form>
    </div>
    {/if}
{elseif $CONFIRM == 'DONATE'}
    {if isset($PARAMS.valid)}
    <div align="center">{$lang.DONATE} {if $PARAMS.gc > 0}<b>{$PARAMS.gc}</b> {$lang.GOLD_CREDITS}{/if} {if $PARAMS.gc > 0 && $PARAMS.sc > 0}{$lang.AND}{/if} {if $PARAMS.sc > 0}<b>{$PARAMS.sc}</b> {$lang.SILVER_CREDITS}{/if}?<br></div><br>
    <table width="100%">
        <tr>
            <td align="center" valign="bottom" colspan="2">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="donate">
                    <input type="hidden" name="gc" value="{$PARAMS.gc}">
                    <input type="hidden" name="sc" value="{$PARAMS.sc}">
                    {if $PARAMS.login_protection}
                    <table width="100%">
                        <tr>
                            <td align="right" width="40%">
                                {$lang.LOGIN}: 
                            </td>
                            <td align="left" width="60%">
                                <input type="text" name="login" size="20" maxlength="30">
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                {$lang.PASSWORD}: 
                            </td>
                            <td align="left">
                                <input type="password" name="password" size="20" maxlength="30">
                            </td>
                        </tr>
                    </table>
                    {/if}
            </td>
        </tr>
        <tr>
            <td width="50%" align="right">
                    <input type="submit" value="{$lang.YES}" class=submit_yes>
                </form>
            </td>
            <td width="50%" align="left">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="index">
                    <input type="submit" value="{$lang.NO}" class=submit_no>
                </form>
            </td>
        </tr>
    </table>
    {else}
    <div align="center">
    <div align="center" style="color: #DD1010">{$PARAMS.MSG}</div><br>
        <form action="" method="POST">
            <input type="hidden" name="action" value="index">
            <input type="submit" value="{$lang.BACK}" class=submit01>
        </form>
    </div>
    {/if}
{/if}
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
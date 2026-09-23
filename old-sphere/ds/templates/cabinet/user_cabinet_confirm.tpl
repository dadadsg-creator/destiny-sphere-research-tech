<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$lang.ENCODING}">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <title>{$lang.TITLE}</title>
    <link rel="stylesheet" href="cabinetstyle.css" type="text/css">
    <style>
        body{margin:0;background:#EBEDF1;font-family:Tahoma,Verdana,Arial,sans-serif;}
        .uc-confirm-wrap{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;}
        .uc-confirm-box{background:#ffffff;border:1px solid #A9B7D6;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);padding:20px 24px;max-width:420px;width:100%;text-align:center;font-size:12px;color:#2D2F60;line-height:1.5;box-sizing:border-box;}
        .uc-confirm-box table{margin:0 auto;}
        .uc-confirm-box .submit_yes,.uc-confirm-box .submit_no,.uc-confirm-box .submit01{width:auto;min-width:110px;height:30px;padding:0 18px;background-image:none;border:0;border-radius:5px;font-family:Tahoma,Verdana,Arial,sans-serif;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:.4px;color:#fff;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.25);}
        .uc-confirm-box .submit_yes{background:linear-gradient(180deg,#1c9a1e 0%,#0e6b10 100%);}
        .uc-confirm-box .submit_yes:hover{background:linear-gradient(180deg,#22b024 0%,#126b13 100%);}
        .uc-confirm-box .submit_no{background:linear-gradient(180deg,#d92e2e 0%,#970000 100%);}
        .uc-confirm-box .submit_no:hover{background:linear-gradient(180deg,#e24444 0%,#af0d0d 100%);}
        .uc-confirm-box .submit01{background:#2D447E;}
        .uc-confirm-box input[type=text],.uc-confirm-box input[type=password]{height:24px;padding:2px 8px;border-radius:4px;border:1px solid #A1AFC6;font-size:12px;box-sizing:border-box;}
        .uc-confirm-copy{margin-top:10px;color:#8892A6;font-size:10px;}
    </style>
</head>

<body>
<div class="uc-confirm-wrap">
    <div class="uc-confirm-box">
<!-- BODY-->
{if $CONFIRM == 'EXCHANGE'}
    {if isset($PARAMS.valid)}
    {$lang.EXCHANGE_CONFIRM} <b>{$PARAMS.gc}</b> {$lang.GOLD_CREDITS} {$lang.ON_SILVER} <b>{$EXCHANGE_COURSE*$PARAMS.gc}</b> {$lang.SILVER_CREDITS}?<br><br>
    <table width="100%">
        <tr>
            <td align="center" valign="bottom" colspan="2">
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
                <form action="" method="POST">
                    <input type="hidden" name="action" value="exchange">
                    <input type="hidden" name="gc" value="{$PARAMS.gc}">
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
                <form action="" method="POST">
                    <input type="hidden" name="action" value="transfer_to_instance">
                    <input type="hidden" name="gc" value="{$PARAMS.gc}">
                    <input type="hidden" name="sc" value="{$PARAMS.sc}">
                    <input type="hidden" name="world" value="{$PARAMS.world}">
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
    </div>
    <div class="uc-confirm-copy">{$lang.COPYRIGHT}</div>
</div>
</body>
</html>
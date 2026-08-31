<tr><td><table>
{if $RAMBLER_EXISTS}
<tr><td style="vertical-align:top"><table>
{if $RAMBLER_LOGIN}
<tr>
    <td colspan="2" align="center">
        <table width=100% height=100% border=0 cellpadding=0 cellspacing=4>
            <form method='post' action='index_login.php'>
                <input type="hidden" name="rambler_id_login" value="1">
                <tr>
                    <td height=100% class=header_txt align="center">
                        <span>{$lang.ALREADY_REGISTRED}</span>
                    </td>
                </tr>
                <!-- tr>
                    <td height=1 class=delimetr01></td>
                </tr -->
                <tr>
                    <td height=100% class=wnd_gen_txt align="center">
                        <br><b>{$USER_NAME}</b>
                    </td>
                </tr>
                <tr>
                    <td height=100% class=wnd_gen_txt align="center">
                        <input type="submit" value="{$lang.R_ENTER}" class=submit01>
                    </td>
                </tr>
            </form>
            <!-- tr>
                <td height=1 class=delimetr01></td>
            </tr -->
        </table>
    </td>
</tr>
{elseif $RAMBLER_REGISTRATION}
<tr>
<td colspan="2" align="center">
    <form action="index_registration.php" method="POST">
        <input type="hidden" name="user" value="{$USERNAME}">
        <input type="hidden" name="pass" value="{$PASSWORD}">
        <input type="hidden" name="conf" value="{$PASSWORD}">
        <input type="hidden" name="lang" value="3">
        <input type="hidden" name="checkbox" value="on">
        <input type="hidden" name="email" value="{$EMAIL}">
        <input type="hidden" name="register" value="1">
        <input type="hidden" name="rambler" value="1">
        <input type="hidden" name="with_rambler_id" value="1">
        <input type="hidden" name="rsid" value="{$RSID}">
        <input type="hidden" name="pid" value="{$PID}">

        <table width=100% height=100% border=0 cellpadding=0 cellspacing=4>
        <tr>
            <td align="center" class=header_txt>
                <b>Rambler ID</b>
            </td>
        </tr>
        <tr>
            <td align="center">
                <b>{$EMAIL}</b>
            </td>
        </tr>
        <!--tr>
            <td height=1 class=delimetr01></td>
        </tr -->
        <tr>
            <td height=100% class=wnd_gen_txt_large align="center">
                <br><b>{$lang.GAME_LOGIN}:</b>&nbsp;
                <input type="text" class="input_login_wnd" name="user" value="{$USERNAME}">
            </td>
        </tr>
        <tr>
            <td align="center" class=header_txt>
                <input type="submit" value="{$lang.BTN_REGISTER}" class="submit02">
            </td>
        </tr>
        <tr>
            <td height=100% class=wnd_gen_txt_large align="center">
                Я регистрируюсь по рекомендации игрока:&nbsp;
                <input type="text" class="input_login_wnd" name="ref_user" value="{$R_REF_USER}">
                <br>(указывать необязательно, но если вы укажете ник игрока, то сделаете доброе дело)
            </td>
        </tr>
    </form>
    <!-- tr>
        <td height=1 class=delimetr01></td>
    </tr -->
    </table>
</td>
</tr>
{else}
<tr>
    <td colspan=2 align="center">
        {$lang.RAMBLER_ID_REG}
    </td>
</tr>
<!-- tr>
    <td colspan="2" height=1 class=delimetr01></td>
</tr -->
<tr>
    <td align="right" width="45%">
        <form action="http://id.rambler.ru/script/auth.cgi" method="POST">
        <input type="hidden" name="back" value="{$HOST}/ds/index_registration.php?rsid=$sid${if !empty($PID)}&pid={$PID}{/if}">
        <input type="hidden" name="url" value="1">
        <br><b>{$lang.R_ID_LOGIN}:</b><br>
    </td>
    <td width="55%"><input type="text" name="login"></td>
</tr>
<tr>
    <td align="right"><b>{$lang.PASS}:</b></td>
    <td><input type="password" name="passw"></td>
</tr>
<!-- tr>
    <td height=1 colspan="2" class=delimetr01></td>
</tr -->
<tr>
<td colspan="2" align="center">
    <br><br><input type="submit" class=submit_yes value="войти">
    </form>
</td>
</tr>
<!-- tr>
    <td colspan="2" height=1 class=delimetr01></td>
</tr -->
{/if}
</table></td><td style="vertical-align:top;"><table>
{/if}

<form action="index_registration.php" enctype="multipart/form-data" method="post">
    <input type="hidden" name="rambler" value="{$RAMBLER_ID}">
    <input type="hidden" name="mode" value="register">
    <input type="hidden" name="agreed" value="true">
    <input type="hidden" name="coppa" value="0">
    {if $ANY_ERROR || $ERROR_TEXT || $FIELDS_NEEDED}
    <tr>
        <td width=100% colspan=2 align=left>
            <font color='#DD0D1A'>{$ANY_ERROR}</font><br>
            <font color='#DD0D1A'>{$ERROR_TEXT}</font><br>
            <span style="font-size:11px;"><b><i>{$FIELDS_NEEDED}</i></b></span>
        </td>
    </tr>
    {/if}
    <tr>
        <td width=50% align=right>
        <b>{$lang.USERNAME}</b></td>
        <td width=50% align=left>
        <input type="text" name="user" value="{$R_USER}" maxlength="40"></td>
    </tr>
    <tr>
        <td width=50% align=right>
        <b>{$lang.EMAIL}</b></td>
        <td width=50% align=left>
        <input type="text" name="email" value="{$R_EMAIL}" maxlength="255"></td>
    </tr>
    <tr>
        <td width=50% align=right>
        <b>{$lang.EMAIL_CONF}</b></td>
        <td width=50% align=left>
        <input type="text" name="emailconf" value="{$R_EMAILCONF}" maxlength="255"></td>
    </tr>
    <tr>
        <td width=50% align=right>
        <b>{$lang.PASSWORD}</b></td>
        <td width=50% align=left>
        <input type="password" name="pass" maxlength="100"></td>
    </tr>
    <tr>
        <td width=50% align=right>
        <b>{$lang.CONFIRM_PASSWORD}</b></td>
        <td width=50% align=left>
        <input type="password" name="conf" maxlength="100"></td>
    </tr>
    <tr>
        <td width=50% align="right" valign="top">
            <b>Я регистрируюсь по рекомендации игрока:</b>
        </td>
        <td align="left">
            <input type="text" name="ref_user" value="{$R_REF_USER}" maxlength="60"><br>
            (указывать необязательно, но если вы укажете ник игрока, то сделаете доброе дело)
        </td>
    </tr>
    <tr>
        <td width=50% align=right>
        <b>{$lang.LANGUAGE}</b></td>
        <td width=50% align=left>
            <select name='lang'>
                {foreach from=$LANGUAGE item=L}<option value="{$L.id_language}" {if isset($R_LANG) && $R_LANG == $L.id_language}selected=1{/if}>{$L.language}{/foreach}
                                                   </select>
        </td>
    </tr>
    <tr>
        <td align=center colspan=2><br>
            <input name='checkbox' type='hidden' value='0'>
            <input name='checkbox' type="checkbox" value="agb" style="border: none; width: 15px; height: 15px">
            {$lang.LICENSE_AGREEMENT}
        </td>
    </tr>
    <!-- tr>
        <td width=100% colspan=2 class=delimetr01>
        </td>
    </tr -->
    <tr>
        <td align=right>
            <input type="submit" name="register" class=submit_yes value={$lang.BTN_REGISTER}>
        </td>
        <td align=left>
            <input type="reset" name="reset" class=submit_no value={$lang.BTN_RESET}>
        </td>
    </tr>
    <!-- tr>
        <td width=100% colspan=2 class=delimetr01>
        </td>
    </tr -->
    <tr>
        <td colspan="2"><br>
            {$lang.TEXT1}
        </td>
    </tr>
</form>

{if $RAMBLER_EXISTS}
</table></td></tr></table></td></tr>
{/if}
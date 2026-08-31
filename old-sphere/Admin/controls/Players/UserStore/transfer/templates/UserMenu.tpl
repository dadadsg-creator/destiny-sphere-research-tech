{include file="UserSearch.tpl"}
<script language="javascript" src="submit.js"></script>
<form action="" method="POST" name="storeForm" id="storeForm" onsubmit="return radio_button_checker()">
<input type="hidden" name="user_name" value="{$USER_NAME}">
{if $UIID}
<h1>Select user instance</h1>
<table cellspacing="0" cellpadding="0" border="1">
    <tr>
        <td class=th_2>&nbsp;user &laquo;<span class="dir">{$USER_NAME}</span>&raquo;{if $USER_BAN}&nbsp;&nbsp;&nbsp;<font color="#ff5555" style="font-weight: bold;">(<b>BANNED</b>)</font>{/if}</td>
        <td width="10%" class=th_2>&nbsp;</td>
    </tr>
        {section name=i loop=$UIID}     
    <tr>
        <td class=maintxt>&nbsp;<span class="mess_info">{$UIID[i].worldname}</span>&nbsp;&raquo;&nbsp;combs:&nbsp;"{$COMB_NAMES[i]}"&nbsp;&nbsp;</td>
        <td align="center"><input type="radio" style="border: 0; background-color: #444E6C; color: #cccccc" name="UIID" id="UIID" value="{$UIID[i].id_userinstance}:{$UIID[i].id_world}:{$UIID[i].dbId}"></td>
    </tr>
        {/section}
</table>
<br>
<SPAN class=mess_info>Select world to transfer user&nbsp;:&nbsp;</span><select name="worldId">
    {section name=i loop=$WORLD}
    <option value="{$WORLD[i].dbId}:{$WORLD[i].id}">{$WORLD[i].name}
    {/section}
</select><br><br>
<!--span class="mess_info"><b>action</b>: </span><select name="reason">
<!--option value="1">vacation-->
<!--option value="2" selected>transfer
<!--option value="3">delete-->
<!--/select><br><br-->
<input type="hidden" name="reason" value="2">
<input type="checkbox" style="border: 0; background-color: #444E6C; color: #cccccc" name="delete">&nbsp;<SPAN class=maintxt>delete</span><br>
<!--input type="checkbox" disabled style="border: 0; background-color: #444E6C; color: #cccccc" name="reserve">&nbsp;<SPAN class=maintxt>reserve</span><br--><br>
<SPAN class=mess_info>comments: </span><br>
<textarea name="comments" cols="60" rows="5" wrap="virtual"></textarea><br><br>
<input type="submit" value="execute">
</form>
{else}
<span class=mess_info style="font-weight: normal;">user &laquo;<b>{$USER_NAME}</b>&raquo; not found</span>
{/if}
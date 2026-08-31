{include file="UserSearch.tpl"}
<script language="javascript" src="submit.js"></script>
<form action="" method="POST" name="storeForm" id="storeForm" onsubmit="return radio_button_checker()">
<input type="hidden" name="user_name" value="{$USER_NAME}">
{if $UIID}
<h2>Select user instance to process</h2>
<table cellspacing="0" cellpadding="0" border="1">
	<tr>
		<td>&nbsp;user &laquo;<b>{$USER_NAME}</b>&raquo;</td><td width="10%">&nbsp;</td>
	</tr>
		{section name=i loop=$UIID}		
	<tr>
		<td>&nbsp;UIID:&nbsp;{$UIID[i]};&nbsp;combs:&nbsp;"{$COMB_NAMES[i]}"&nbsp;&nbsp;</td>
		<td align="center"><input type="radio" name="UIID" id="UIID" value="{$UIID[i]}"></td>
	</tr>
		{/section}
</table>
<br>
<b>action</b>: <select name="reason">
<option value="1">vacation
<option value="2">transfer
<option value="3">delete
</select><br><br>
<input type="checkbox" name="delete" checked="1">delete<br>
<input type="checkbox" name="reserve" checked="1">reserve<br><br>
comments: <br>
<textarea name="comments" cols="30" rows="5" wrap="virtual"></textarea><br><br>
<input type="submit" value="execute">
</form>
{else}
user &laquo;<b>{$USER_NAME}</b>&raquo; not found
{/if}
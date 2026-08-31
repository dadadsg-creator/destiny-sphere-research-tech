{if $USER_NAME}
<table cellspacing="0" cellpadding="0" border="1">
	<tr>
		<td class=th_2>&nbsp;user &laquo;<span class="dir">{$USER_NAME}</span>&raquo;</td>
		<td width="10%" class=th_2>&nbsp;{if $EXISTS}<A href="?action=delete&uid={$UID}">delete</A>{else}<A href="?action=add&uid={$UID}">add</A>{/if}&nbsp;</td>
	</tr>
</table>
{else}
<h1>USER NO FOUND</h1>
{/if}
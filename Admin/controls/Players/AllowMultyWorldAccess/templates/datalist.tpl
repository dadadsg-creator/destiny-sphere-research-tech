<H1>All Data</H1> <A href="index.php">Search User</A><br><br>
{if count($DATA_LIST)}
<table cellspacing="0" cellpadding="0" border="1">
	{section name=i loop=$DATA_LIST}
	<tr>
		<td class=th_2>&nbsp;{$DATA_LIST[i].id_multiworldaccess}&nbsp;</td>
		<td class=th_2>&nbsp;user &laquo;<span class="dir">{$DATA_LIST[i].username}</span>&raquo;&nbsp;</td>
		<td class=th_2>&nbsp;{$DATA_LIST[i].adddate}&nbsp;</td>
		<td class=th_2>&nbsp;<A href="?action=delete&uid={$DATA_LIST[i].id_user}">delete</A>&nbsp;</td>
	</tr>
	{/section}
</table>
{else}
<H1>NO DATA</h1>
{/if}
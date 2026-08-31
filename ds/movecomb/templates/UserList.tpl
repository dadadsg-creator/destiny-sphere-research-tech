<A href="index.php">Back To Main</A>
<FORM action="search.php" method="POST">
<INPUT type="hidden" name="dosrch" value="1">
<TABLE cellpadding="0" cellspacing="0" border="0">
	<TR>
		<TD>
			<SELECT name="reason">
			{html_options options=$REASON}
			</SELECT></TD>
		<TD>&nbsp;&nbsp;&nbsp;User name: <INPUT type="text" name="name" size="20">&nbsp;&nbsp;</TD>
		<TD>&nbsp;&nbsp;<INPUT type="text" name="restored" size="10">&nbsp;&nbsp;</TD>
		<TD>&nbsp;&nbsp;<INPUT type="submit" value="search"></TD>
	</TR>
</TABLE>
</FORM>
{if $SRCH_RESULTS}
<table cellpadding="3" cellspacing="0" border="1">
	<tr>
		<td align="center">reason</td>
		<td align="center">user name</td>
		<td align="center">store date</td>
		<td align="center">restore date</td>
		<td align="center">store admin</td>
		<td align="center">restore admin</td>
		<td>&nbsp;</td>
	</tr>
	{section name=i loop=$SRCH_RESULTS}
	<tr>	
		<td align="center">{$SRCH_RESULTS[i].reason_name}</td>
		<td align="center">{$SRCH_RESULTS[i].username}</td>
		<td align="center">{$SRCH_RESULTS[i].store_date}</td>
		<td align="center">&nbsp;{$SRCH_RESULTS[i].restore_date}</td>
		<td align="center">{$SRCH_RESULTS[i].store_admin_name}</td>
		<td align="center">&nbsp;{$SRCH_RESULTS[i].restore_admin_name}</td>
		<td align="center">{if !$SRCH_RESULTS[i].restore_date}<A href="index.php?id_storeuserdata={$SRCH_RESULTS[i].id_storeuserdata}&worldFrom={$SRCH_RESULTS[i].id_world_from}">restore</A>{else}&nbsp;{/if}</td>
	</tr>
	{/section}
</table>
{/if}

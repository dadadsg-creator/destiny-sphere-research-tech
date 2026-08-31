<!--A href="index.php">Back To Main</A-->
<FORM action="" method="GET">
<TABLE cellpadding="0" cellspacing="0" border="0">
	<TR>
		<TD>
			<SELECT name="reason">
			{html_options options=$REASON selected=2}
			</SELECT></TD>
		<TD>&nbsp;&nbsp;&nbsp;<SPAN class=mess_info>User name: </span><INPUT type="text" name="name" size="20" value="{$SRCH_NAME}">&nbsp;&nbsp;</TD>
		<!--TD>&nbsp;&nbsp;<INPUT type="text" name="restored" size="10">&nbsp;&nbsp;</TD-->
		<TD>&nbsp;&nbsp;<SPAN class=mess_info>World from: </span>
		<select name="world_from">
			{html_options options=$WORLD_FROM selected=$SRCH_WORLD_FROM}
		</select></TD>
		<TD>&nbsp;&nbsp;<SPAN class=mess_info>World to: </span>
		<select name="world_to">
			{html_options options=$WORLD_TO selected=$SRCH_WORLD_TO}
		</select></TD>
		<TD>&nbsp;&nbsp;<SPAN class=mess_info>World to: </span>
		<select name="type">
			{html_options options=$TYPES selected=$SRCH_TYPE}
		</select></TD>
		<TD>&nbsp;&nbsp;<INPUT type="submit" value="search"></TD>
	</TR>
</TABLE>
</FORM>
{if $SRCH_RESULTS}
<SPAN class=mess_info>page </span>
{foreach key=key item=item from=$PAGES}&nbsp;{if $OFFSET == $item}<span class="mess_info">{$key}</span>{else}<A href="?offset={$item}{$SRCH}" class="dir">{$key}</a>{/if}&nbsp;{/foreach}
<table cellpadding="3" cellspacing="0" border="1">
	<tr class=th_2>
		<td align="center">reason</td>
		<td align="center">user name</td>
		<td align="center">world from</td>
		<td align="center">world to</td>
		<td align="center">store date</td>
		<td align="center">restore date</td>
		<td align="center">store admin</td>
		<td align="center">restore admin</td>
		<td align="center">restore user</td>
		<td>&nbsp;</td>
	</tr>
	{section name=i loop=$SRCH_RESULTS}
	<tr class="maintxt">	
		<td align="center">{$SRCH_RESULTS[i].reason_name}</td>
		<td align="center" class="mess_info">{$SRCH_RESULTS[i].username}</td>
		<td align="center">{$SRCH_RESULTS[i].world_from_name}</td>
		<td align="center">{$SRCH_RESULTS[i].world_to_name}&nbsp;</td>
		<td align="center">{$SRCH_RESULTS[i].store_date}</td>
		<td align="center">&nbsp;{$SRCH_RESULTS[i].restore_date}</td>
		<td align="center">{$SRCH_RESULTS[i].store_admin_name}</td>
		<td align="center">&nbsp;{$SRCH_RESULTS[i].restore_admin_name}</td>
		<td align="center">{if !$SRCH_RESULTS[i].restore_date && $SRCH_RESULTS[i].reason_name != 'death' && $SRCH_RESULTS[i].id_storeuserdata > 0}<A href="../transfer/index.php?id_storeuserdata={$SRCH_RESULTS[i].id_storeuserdata}&worldFrom={$SRCH_RESULTS[i].dbId}:{$SRCH_RESULTS[i].id_world_from}">restore</A>{else}&nbsp;{/if}</td>
		<td align="center">{if $SRCH_RESULTS[i].id_storeuserdata > 0}<A href="./savetemplate.php?id_storedata={$SRCH_RESULTS[i].id_storeuserdata}">save</A>{/if}&nbsp;</td>
	</tr>
	{/section}
</table>
<SPAN class=mess_info>page </span>
{foreach key=key item=item from=$PAGES}&nbsp;{if $OFFSET == $item}<span class="mess_info">{$key}</span>{else}<A href="?offset={$item}{$SRCH}" class="dir">{$key}</a>{/if}&nbsp;{/foreach}
{/if}

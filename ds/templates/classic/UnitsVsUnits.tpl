<HTML>
<HEAD>
<TITLE></TITLE>
</HEAD>
<BODY>
<FORM action="" method="GET">
	<SELECT name="race_from">
	{html_options options=$RACES selected=$RACE_FROM}
	</SELECT> против: 
	<SELECT name="race_to">
	{html_options options=$RACES selected=$RACE_TO}
	<SELECT>&nbsp;&nbsp; 
	<INPUT type="submit" value="select">
</FORM>
{if $UNITS}
  {if $SELECTED_UNIT != -1}
  <FORM action="?race_from={$RACE_FROM}&race_to={$RACE_TO}&unit={$SELECTED_UNIT}" method="POST">
  	<INPUT type="hidden" name="change" value="1">
  {/if}
	<TABLE border="1" cellspacing="0">
		<TR>
			<TD><B>unit</B></TD>
			<TD><B>vs unit</B></TD>
			<TD align="center">K</TD></TR>
		<tr>
			<td>
				<table cellpadding="1" cellspacing="0">
				{section name=i loop=$UNITS}
					<tr  style="height: 22px;">
						<td>{if $UNITS[i].id_unit == $SELECTED_UNIT}<FONT color="red"><B>{$UNITS[i].text}</B></FONT>&nbsp;=>{else}<A href="?race_from={$RACE_FROM}&race_to={$RACE_TO}&unit={$UNITS[i].id_unit}">{$UNITS[i].text}</A>{/if}</td>	
					</tr>
				{/section}
				</table>
			</td>
			<td>
			{if $SELECTED_UNIT != -1}
				<table cellpadding="1" cellspacing="0">
				{section name=i loop=$VS_UNITS}
					<tr  style="height: 22px;"><td>{$VS_UNITS[i].text}</td></tr>
				{/section}
				</table>
			{else}&nbsp;{/if}</td>
			<td>
			{if $SELECTED_UNIT != -1}
				<table cellpadding="1" cellspacing="0">
				{section name=i loop=$K}
					<tr style="height: 22px;"><td><input style="margin: 0; padding: 0; height: 18px; line-height: 93%; font-size: 15px;" name="units[{$VS_UNITS[i].id_unit}]" value="{if $K[i]}{$K[i]}{else}0{/if}" size="7"></td></tr>
				{/section}
				</table>
			  	
			{else}&nbsp;{/if}
			</td>
		</tr>
		{if $SELECTED_UNIT != -1}
		<tr><td colspan=3 align="right"><INPUT type="submit" value="change"></td></tr>
		{/if}			
	</TABLE>
  {if $SELECTED_UNIT != -1}	
  </FORM>
  {/if}
{/if}
</BODY>
</HTML>
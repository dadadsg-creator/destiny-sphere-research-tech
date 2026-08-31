<br><hr><br>
<h1>TRANSFER USER</h1>
<span class="mess_info">{$USER_NAME}&nbsp;&nbsp;</span>
<span class="dir">[&nbsp;{$USER_WORLD}&nbsp;]</span>
<span class="mess_info">&nbsp;=>&nbsp;</span>
<span class="dir">[&nbsp;{$WORLD_TO_NAME}&nbsp;]</span>
<form action="" method="POST">
<input type="hidden" name="id_storeuserdata" value="{$ID_STORE_USER_DATA}">
<input type="hidden" name="user_name" value="{$USER_NAME}">
<input type="hidden" name="worldTo" value="{$WORLD_TO}">
<!--SPAN class=mess_info>Select world to transfer user&nbsp;:&nbsp;</span><select name="worldTo">
	{section name=i loop=$WORLD}
	<option value="{$WORLD[i].dbId}:{$WORLD[i].id}">{$WORLD[i].name}
	{/section}
</select><br--><br>
<span class="maintxt">Delete gondola-transporter: </span><input type="checkbox" checked="1" style="border: 0; background-color: #444E6C; color: #cccccc" name="deleteGondola"><br>
<br>
<table cellpadding="0" cellspacing="0" border="1">
	<tr class="th_2">
		<td>&nbsp;comb name&nbsp;</td>
		<td>&nbsp;new coords&nbsp;</td>
		<td>&nbsp;old coords&nbsp;</td>
		<td>&nbsp;</td></tr>
{foreach key=key item=item from=$COMB}
	<tr class=maintxt>
		<td>&nbsp;&nbsp;{$key}&nbsp;&nbsp;</td>
		<td><input style="text-align: center;" type="text" size="1" name="combCoord[{$key}][RING]" value="{$item.RING}">
			<input style="text-align: center;" type="text" size="2" name="combCoord[{$key}][COMPLEX]" value="{$item.COMPLEX}">
			<input style="text-align: center;" type="text" size="1" name="combCoord[{$key}][COMB]" value="{$item.COMB}"></td>
		<td align="center">{$OLD_COMB.$key}</td>
		<td align="center">{$item.info}</td></tr>
{/foreach}
</table>
<span class="maintxt">If selected comb coordinates are ocupied, min free coordinates will be used</span><br><br>
<input type="submit" value="transfer"><br>
</from>
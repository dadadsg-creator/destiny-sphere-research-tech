<br><hr><br>
<h1>TRANSFER USER</h1>
<b>{$USER_NAME}&nbsp;[{$USER_WORLD}]</b>
<form action="" method="POST">
<input type="hidden" name="id_storeuserdata" value="{$ID_STORE_USER_DATA}">
<input type="hidden" name="user_name" value="{$USER_NAME}">
move to world: <select name="worldTo">
	{section name=i loop=$WORLD}
	<option value="{$WORLD[i].dbId}:{$WORLD[i].id}">{$WORLD[i].name}
	{/section}
</select><br><br>
<table cellpadding="0" cellspacing="0" border="1">
	<tr><td>comb name</td><td>coordinates</td></tr>
{section name = i loop=$COMB}
	<tr><td>{$COMB[i].name}&nbsp;&nbsp;</td>
	<td><input align="right" type="text" size="8" name="combCoord[{$COMB[i].name}]" value="{$COMB[i].coord}"></td></tr>
{/section}
</table>
<input type="submit" value="transfer"><br>
</from>
Если соты на этих координатах заняты, то переедет на любые свободные соты.
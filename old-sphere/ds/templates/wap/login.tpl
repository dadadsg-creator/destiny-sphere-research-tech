<html>
<head>
</head>
<body>
	{if !empty($MSG)}
		{$MSG}
	{/if}
	<form action="login.php" method="POST">
		<input type="hidden" name="enter" value="1">
		Login: <input type="text" name="login"><br>
		Password: <input type="password" name="password"><br>
		World: <select name="world">{foreach from=$WORLDS item=WORLD key=ID}<option value={$ID}>{$WORLD}{/foreach}</select><br>
		<input type="submit" value="enter">
	</form>
</body>
</html>
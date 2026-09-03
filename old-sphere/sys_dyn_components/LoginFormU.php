<table cellpadding=0 cellspacing=0 border=0>
	<tr>
		<td colspan=3 align=left><img src="img/v4/borders/03/t.jpg" border=0></td>
	</tr>
	<tr>
		<td width=6 height=100% background="img/v4/borders/03/l.gif" valign=top align=left><img width=6 height=1 src="img/v4/borders/03/l.gif" border=0></td>
		<td width=188 class=bg03 style="padding:2px;">
			<table width=100% cellpadding=0 cellspacing=1 border=0>
				<tr>
					<td colspan=2>
						<table width=100% cellpadding=0 cellspacing=0 border=0>
							<tr>
								<td><img src="img/v4/graph/p01.gif" border=0></td>
								<td width=100% align=left class=header_txt><span style="padding-left:5px; position: relative; top: -3px;">ВХОД В ИГРУ</span></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td height=1 colspan=2 class=delimetr01></td>
				</tr>
				<?php
				//
if (stripos($_SERVER['HTTP_HOST'], 'ds.meta.ua')!==false)
{
	require $_SERVER['DOCUMENT_ROOT'].'/sys/auth.class.php';
	//ini_set('display_errors', 1);
	//error_reporting(E_ALL);
	$metaua_user = new GlobalClassAuth("REDACTED_API_SECRET","destinysphere");
	//var_export($metaua_user);
	//echo $u->sUserName;
	if ($metaua_user->sUserName)
	  $metaua_registered = 1;
  
}

				
					if ($metaua_registered)
					{
				?>
								<form action="ds/index_login.php" method="POST" name=login_form>
				<input type="hidden" name="enter" value="Login">
				<input type="hidden" name="from_main" value="1">
			
				<tr>

					<td width=100% colspan="2" align=center valign="middle" style="height: 20px;">
             meta.ua login: <b><?=$metaua_user->sUserName ?></b>
					</td>
				</tr>
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 35px;"><input type="submit" value="  Войти в игру  " class=submit01></td>
				</tr>
				</form>				
				<?php
					} else if(isset($_POST['rambler_login']))
					{
				?>
				<form action="ds/index_login.php" method="POST" name=login_form>
				<input type="hidden" name="rambler_id_login" value="1">
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 20px;">
						<script language="JavaScript" src="sys_dyn_components/RamblerID_JS.php" type="text/javascript"></script>
					</td>
				</tr>
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 35px;"><input type="submit" value="Войти в игру" class=submit01></td>
				</tr>
				</form>
				<form action="http://id.rambler.ru/script/auth.cgi" method="POST">
				<input type="hidden" name="mode" value="logout">
				<input type="hidden" name="back" value="http://dsgame.rambler.ru">
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 35px;"><input type="submit" value="Выход" class=submit01></td>
				</tr>
				</form>
				<?php		
					} elseif(isset($_POST['rambler_reg'])) {
				?>	
				<form action="ds/index_registration.php" method="POST" name=login_form>
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 20px;">
						<script language="JavaScript" src="sys_dyn_components/RamblerID_JS.php" type="text/javascript"></script>
					</td>
				</tr>
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 35px;"><input type="submit" value="Регистрация" class=submit01></td>
				</tr>
				</form>
				<form action="http://id.rambler.ru/script/auth.cgi" method="POST">
				<input type="hidden" name="mode" value="logout">
				<input type="hidden" name="back" value="http://dsgame.rambler.ru">
				<tr>
					<td width=100% colspan="2" align=center valign="middle" style="height: 35px;"><input type="submit" value="Выход" class=submit01></td>
				</tr>
				</form>	
				<?php
					} else {
				?>
				<form action="ds/index_login.php" method="POST" name=login_form>
				<input type="hidden" name="enter" value="Login">
				<tr>
					<td width=100% align=right><span style="padding-right:5px;"><b>Логин</b></span></td>
					<td align=left><input type="text" name="login" style="width:125px;"></td>
				</tr>
				<tr>
					<td width=100% align=right><span style="padding-right:5px;"><b>Пароль</b></span></td>
					<td align=left><input type="password" name="pass" style="width:125px;"></td>
				</tr>
				<tr>
					<td colspan=2 align=right>
						<input type='submit' class="submit01" value='  Вход  ' title="Обратите внимание, что при вводе данных написание прописных и строчных букв имеет значение.">
					</td>
				</tr>
				</form>
				<tr>
					<td height=1 colspan=2 class=delimetr01></td>
				</tr>
				<tr>
					<td colspan=2>
						<table width=100% cellpadding=0 cellspacing=0 border=0>
							<tr>
								<td><img src="img/v4/graph/c01.gif" border=0></td>
								<td width=100% align=left><a href="/ds/remaind_pass.php" target=_blank>Забыли Пароль?</a></td>
							</tr>
						</table>
					</td>
				</tr>
				<?php
					}
				?>
			</table>
		</td>
		<td width=5 height=100% background="img/v4/borders/03/r.gif" valign=top align=left><img width=5 height=1 src="img/v4/borders/03/r.gif" border=0></td>
	</tr>
	<tr>
		<td colspan=3 align=left><img src="img/v4/borders/03/b.jpg" border=0></td>
	</tr>
</table>
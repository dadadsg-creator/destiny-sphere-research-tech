<?php

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

$str = "";

$ramblerLogin = new GE_RamblerLogin();
if($ramblerLogin->hasRSID())
{
	$str .= "document.write(\"Рамблер ID: <b>".$ramblerLogin->getRamblerLogin()."</b><br>\");\n";
}

if($ramblerLogin->IsRSID())
{
	$user = $ramblerLogin->execLogin();
	if($user instanceof DE_User)
	{
		$str .= "document.write(\"Игровой логин: <b>".$user->getName()."</b>\");\n";
	}
}

echo $str;

?>
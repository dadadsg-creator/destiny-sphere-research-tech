<?php
require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

if(isset($version) && $version == 'OLD')
{
	$beautyGateURL = 'http://beauty.destinysphere.ru/frame_beauty.php';
	$defaultRes = '&nbsp;';
} else {
	$beautyGateURL = 'http://beauty.destinysphere.ru/frame_beauty2.php';
	$defaultRes = '<a href="http://chat.destinysphere.ru/irc.cgi" target="_blank"><img width=160 height=250 src="img/banners/160x250_01.jpg" border=0></a>';
}

$cacheMark = 'portal_beauty_frame'.$version;
$cache = DS_Cache::Singleton();
if($res = $cache->get($cacheMark))
{
	echo $res;
	return;
} 
	
$request = new SU_HTTPRequest();
$request->setOption('timeout', 2);
$res = $request->get($beautyGateURL);
if(($res === false) || (strpos($res, '<table') === false))
{
	// Что показываем, если конкурс красоты в очередной раз упал
	// пока стоит баннер IRC чата
	$res = $defaultRes;
}

$cache->set($cacheMark, 180, $res);
echo $res;
?>
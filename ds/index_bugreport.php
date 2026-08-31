<?
require_once( 'Main.php' );

require_once(constant('INCLUDE_PATH').'GameDown.inc.php');
if (GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}
$GLOBALS['user_mail'] = '';

function checkData($data)
{
	if ((!key_exists('User_Nick', $data) || empty($data['User_Nick'])))
		return false;
	if(!checkUser($data['User_Nick']))
		return false;
	
	/* НЕ СТИРАТЬ!*/
	return true;
}

function checkUser($login)
{
	global $user_mail;
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->setDbId(0) ) 
	{
		echo "DB-connection failed";
		exit;
	}
	
	$sql = sprintf("select 
							email 
						from 
							ds_user 
						where 
							username='%s'",
					$login);
	$res = $common->getResultArray ( "", $sql, "", "", "" );

	if ($common->errormsg)
	{
		echo "Error001";
		exit;
	}
	
	if(!isset($res[0]['email']))
	{
		$user_mail = 'none';
		return false;
	}
	
	$user_mail = $res[0]['email'];	
	return true;
}

function showForm()
{
	global $user_mail;
	$error = '';
	if(strcmp($user_mail,'none') === 0)
	{
		$error = "<br>
					<font size='-6' color='red'>
					Ошибка: неверно введен игровой ник. Сообщение не отправлено.
					</font>
					<br>";
	}
	
	echo $error.'
		<form action="'.$_SERVER["PHP_SELF"].'" method="post">
   	<B>Игровой ник:</B><br/>
   	<input class="input_gen" maxlength="30" size="50" name="User_Nick"/> <br/>
   	<br>      	
   	<B>Игровой мир:</B><br>
   	<select class="select_gen" size="1" name="World">
	    	<option value="1">Сфера Андромеды</option>
   	 	<option value="2">Сфера Водолея</option>
    		<option value="3">Сфера Змееносца</option>
    		<option value="4">Сфера Кассиопеи</option>
    		<option value="5">Сфера Ориона</option>
    		<option value="6">Сфера Пегаса</option>
    		<option value="7">Сфера Персея</option>
    		<option value="8">Сфера Скорпиона</option>
    		<option value="9">Сфера Феникса</option>
    		<option value="10">Сфера Эридана</option>
    		<option value="11" selected="selected">Сфера Стрельца</option>
   	</select><br/>
   	<br>
   	<B>Тема вопроса:</B><br/>
   	<input class="input_gen" maxlength="30" size="50" name="Theme_question"/> <br/>
   	<br/>
   	<B>Ваш вопрос:</B><br/>
   	<textarea class="textarea_gen" name="question" rows="10" cols="75">Напишите, пожалуйста, вопрос, который вы хотите задать службе поддержки.</textarea><br/>
   	<br>
   	<input class="input_button" type="submit" value="Отправить"/>&nbsp;&nbsp;<input class="input_button" type="reset" value="Очистить"/><br/>
	</form>';
}

function SendEmailMessage() {
	
	global $user_mail;
	$mailto = "support@ru.destinysphere.com";

	$from 	= "From: ".$user_mail;
	$reply	= "Reply-To: ".@$_POST['Email'];

	$mailHeaders =
			$from." \r\n".
			$reply." \r\n".
			"Content-Type: text/plain; charset=Windows-1251 \r\n".
			"X-Mailer: DestinySphere-Portal";

	$worlds = array(
			"1"	=> "Сфера Андромеды",
			"2" 	=> "Сфера Водолея",
			"3" 	=> "Сфера Змееносца",
			"4" 	=> "Сфера Кассиопеи",
			"5" 	=> "Сфера Ориона",
			"6" 	=> "Сфера Пегаса",
			"7" 	=> "Сфера Персея",
			"8" 	=> "Сфера Скорпиона",
			"9" 	=> "Сфера Феникса",
			"10" 	=> "Сфера Эридана",
			"11" 	=> "Сфера Стрельца"
		);
		
	$mailMess =
			"Игровой Nickname: ".@$_POST['User_Nick']."\n\n".
			"E-Mail: ".@$_POST['Email']."\n\n".
			"Мир: ".@$worlds[ $_POST['World'] ]."\n\n".
			"Тема вопроса: ".@$_POST['Theme_question']."\n\n".
			"Вопрос: ".@$_POST['question']."\n\n";

	$subj = "BugReport - ".@$_POST['question'];
	
	mail( $mailto, $subj, $mailMess, $mailHeaders);
	
}

function showMessage() {
	echo '<div align=center><span class=ind_st2 style="font-size:16px;">Сообщение отправлено!</span><br>
	Ответ на ваш вопрос придет на e-mail, указанный в вашем игровом профиле.
	<br>
	<a href="/ds/index_login.php" class=wnd_gen_txt_large><b>[ вернуться на страницу входа в игру ]</b></a>
	<br><br>
	<a href="/ds/index_bugreport.php" class=wnd_gen_txt_large>[ вернуться к форме ]</a></div>';	
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Windows-1251">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<title>DestinySphere : Ваш вопрос в службу технической поддержки...</title>
	<link rel="stylesheet" href="../dsstyle.css" type="text/css">
</head>
<body>
<table width=100% border=0 cellpadding=0 cellspacing=0>
	<tr>
		<td>
			<table width=100% border=0 cellpadding=0 cellspacing=0>
				<!-- header begin -->
				<tr height=86>
					<td valign=top>
						<table width=100% height=86 border=0 cellpadding=0 cellspacing=0>
							<tr>
								<td width=500>
									<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="500" height="86" id="header_1" align="middle">
										<param name="allowScriptAccess" value="sameDomain">
										<param name="movie" value="../img/flash/header.swf">
										<param name="quality" value="high">
										<param name="menu" value="false">
										<param name="settings" value="false">
										<param name="wmode" value="opaque">
										<param name="bgcolor" value="#ffffff">
										<embed src="../img/flash/header.swf" quality="high" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
									</object></td>
								<td width=265><img src="../img/background/header_bg.jpg" border=0></td>
								<td width=100% background="../img/background/header_bg2.gif"></td>	
							</tr>
						</table>	
					<td>	
				</tr>
				<!--begin-->
				<tr height=3>
					<td background="../img/background/buttons_bg.gif" align=center class=wnd_gen_txt></td>
				</tr>
				<!--end-->
				<tr height=5>
					<td background="../img/background/bg.gif" align=center class=wnd_gen_txt></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td height=100% valign=top>
			<table width="100%" border="0" cellpadding="0" cellspacing="10">
				<tr>
         		<td valign=top>
						<table width="100%" border=0 cellpadding=0 cellspacing=0>
							<tr>
								<td width=5 height=5><img src="../img/border/01/t_l.gif" border=0 width=5 height=5></td>
								<td height=5 background="../img/border/01/t.gif"></td>
								<td width=10 height=5><img src="../img/border/01/t_r.gif" border=0 width=10 height=5></td>
							</tr>
							<tr>
								<td width=5 background="../img/border/01/l.gif"></td>
								<td width=100% class=wnd_outer valign=top>
									<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
										<tr>
											<td colspan=3 align=center>
												<img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>Ваш вопрос в службу технической поддержки</b></span></td>
										</tr>
										<tr>
											<td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/t.gif"></td>
											<td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
										</tr>
										<tr>
											<td width=4 background="../img/border/02/l.gif"></td>
											<td width=100% class=wnd_inner align=center>
												<table cellpadding='2' cellspacing='1' border='0'>
													<tr>
														<td align="left" class=wnd_gen_txt_large>
															<?
															if (!checkData($_POST)) {
																showForm();
															} else {
																SendEmailMessage();
																showMessage();
															}
															?></td>
													</tr>
												</table>
   										</td>
											<td width=4 background="../img/border/02/r.gif"></td>
										</tr>
										<tr>
											<td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/b.gif"></td>
											<td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
										</tr>
									</table>
								</td>
								<td width=10 background="../img/border/01/r.gif"></td>
							</tr>
							<tr>
								<td width=5 height=10><img src="../img/border/01/b_l.gif" border=0 width=5 height=10></td>
								<td height=10 background="../img/border/01/b.gif"></td>
								<td width=10 height=10><img src="../img/border/01/b_r.gif" border=0 width=10 height=10></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td align=center class=copyright_txt valign=bottom>
			2003-2005©. DestinySphere GmbH, ООО Сфера Судьбы. All Rights Reserved.<br>
			Оптимизировано для разрешения экрана 1024 x 768 и броузера Microsoft Internet Explorer версии 6.0 и выше.</td>
	</tr>
	<tr>
		<td align=center>
 <div align="center"><!-- SpyLOG f:0211 -->
<script language="javascript"><!--
Mu="u5095.09.spylog.com";Md=document;Mnv=navigator;Mp=0;
Md.cookie="b=b";Mc=0;if(Md.cookie)Mc=1;Mrn=Math.random();
Mn=(Mnv.appName.substring(0,2)=="Mi")?0:1;Mt=(new Date()).getTimezoneOffset();
Mz="p="+Mp+"&rn="+Mrn+"&c="+Mc+"&t="+Mt;
if(self!=top){Mfr=1;}else{Mfr=0;}Msl="1.0";
//--></script><script language="javascript1.1"><!--
Mpl="";Msl="1.1";Mj = (Mnv.javaEnabled()?"Y":"N");Mz+='&j='+Mj;
//--></script><script language="javascript1.2"><!--
Msl="1.2";Ms=screen;Mpx=(Mn==0)?Ms.colorDepth:Ms.pixelDepth;
Mz+="&wh="+Ms.width+'x'+Ms.height+"&px="+Mpx;
//--></script><script language="javascript1.3"><!--
Msl="1.3";//--></script><script language="javascript"><!--
My="";My+="<a href='http://"+Mu+"/cnt?cid=509509&f=3&p="+Mp+"&rn="+Mrn+"' target='_blank'>";
My+="<img src='http://"+Mu+"/cnt?cid=509509&"+Mz+"&sl="+Msl+"&r="+escape(Md.referrer)+"&fr="+Mfr+"&pg="+escape(window.location.href);
My+="' border=0 width=1 height=1 alt='SpyLOG'>";
My+="</a>";Md.write(My);//--></script><noscript>
<a href="http://u5095.09.spylog.com/cnt?cid=509509&f=3&p=0" target="_blank">
<img src="http://u5095.09.spylog.com/cnt?cid=509509&p=0" alt='SpyLOG' border='0' width=1 height=1>
</a></noscript>
<!-- SpyLOG -->
<!--begin of Rambler's Top100 code -->
<a href="http://top100.rambler.ru/top100/" target=_blank>
<img src="http://counter.rambler.ru/top100.cnt?498652" alt="" width=1 height=1 border=0></a>
<!--end of Top100 code-->
<!--begin of Top100 logo-->
<a href="http://top100.rambler.ru/top100/" target=_blank>
<img src="http://top100-images.rambler.ru/top100/banner-88x31-rambler-black2.gif" alt="Rambler's Top100" width=1 height=1 border=0></a>
<!--end of Top100 logo -->
<!-- HotLog -->
<script language="javascript">
hotlog_js="1.0";
hotlog_r=""+Math.random()+"&s=139920&im=131&r="+escape(document.referrer)+"&pg="+
escape(window.location.href);
document.cookie="hotlog=1; path=/"; hotlog_r+="&c="+(document.cookie?"Y":"N");
</script><script language="javascript1.1">
hotlog_js="1.1";hotlog_r+="&j="+(navigator.javaEnabled()?"Y":"N")</script>
<script language="javascript1.2">
hotlog_js="1.2";
hotlog_r+="&wh="+screen.width+'x'+screen.height+"&px="+
(((navigator.appName.substring(0,3)=="Mic"))?
screen.colorDepth:screen.pixelDepth)</script>
<script language="javascript1.3">hotlog_js="1.3"</script>
<script language="javascript">hotlog_r+="&js="+hotlog_js;
document.write("<a href='http://click.hotlog.ru/?139920' target='_blank'><img "+
" src='http://hit6.hotlog.ru/cgi-bin/hotlog/count?"+
hotlog_r+"&' border=0 width=1 height=1 alt='HotLog'></a>")</script>
<noscript><a href=http://click.hotlog.ru/?139920 target=_blank><img
src="http://hit6.hotlog.ru/cgi-bin/hotlog/count?s=139920&im=131" border=0
width=1 height=1 alt='HotLog'></a></noscript>
<!-- /HotLog -->
<!--Rating@Mail.ru COUNTER--><script language="JavaScript" type="text/javascript"><!--
d=document;var a='';a+=';r='+escape(d.referrer)
js=10//--></script><script language="JavaScript1.1" type="text/javascript"><!--
a+=';j='+navigator.javaEnabled()
js=11//--></script><script language="JavaScript1.2" type="text/javascript"><!--
s=screen;a+=';s='+s.width+'*'+s.height
a+=';d='+(s.colorDepth?s.colorDepth:s.pixelDepth)
js=12//--></script><script language="JavaScript1.3" type="text/javascript"><!--
js=13//--></script><script language="JavaScript" type="text/javascript"><!--
d.write('<a href="http://top.mail.ru/jump?from=665247"'+
' target=_blank><img src="http://top.list.ru/counter'+
'?id=665247;t=50;js='+js+a+';rand='+Math.random()+
'" alt="Рейтинг@Mail.ru"'+' border=0 height=1 width=1/><\/a>')
if(11<js)d.write('<'+'!-- ')//--></script><noscript><a
target=_blank href="http://top.mail.ru/jump?from=665247"><img
src="http://top.list.ru/counter?js=na;id=665247;t=50"
border=0 height=1 width=1
alt="Рейтинг@Mail.ru"/></a></noscript><script language="JavaScript" type="text/javascript"><!--
if(11<js)d.write('--'+'>')//--></script><!--/COUNTER-->

<a href="http://www.yandex.ru/cy?base=0&host=www.destinysphere.ru" target=_blank>
<img src="http://www.yandex.ru/cycounter?мой_сайт" width=1 height=1 alt="Яндекс цитирования" border=0></a>

		</td>
	</tr>
</table>
</body>
</html>
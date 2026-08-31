<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<meta http-equiv="Content-Style-Type" content="text/css">
<link rel="top" href="index.php" title="" />
<link rel="search" href="search.php" title="" />
<link rel="help" href="faq.php" title="" />
<link rel="author" href="memberlist.php" title="" />
<title>DestinySphere :: Вход в игру</title>
<link rel="stylesheet" href="DestinySphere.css" type="text/css">
</head>
<body bgcolor="#000000" text="#DDDDDD" link="#8BC7FF" vlink="#5493B4">
<script language="JavaScript" src="../fade.js"></script>
<a name="top"></a>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="30" height="30" background="images/corner1.gif" >&nbsp;</td>
		<td height="30" background="images/corner_bg1.gif">&nbsp;</td>
		<td height="30" width="30" background="images/corner2.gif">&nbsp;</td>
	</tr>
	<tr>
		<td background="images/corner_bg2.gif" width="30">&nbsp;</td>
		<td>
			<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#000000">
				<tr>
					<td valign="bottom" class="bodyline">
						<table width="100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td align="center"><a href="../index.php"><span class="maintitle"><img src="images/logo_ds.gif" border="0" alt="Главная страница Destiny Sphere" vspace="1" width="196" height="60" /></span></a><a target="_blank" href="http://www.rambler.ru"><span class="maintitle"><img src="images/logorambler.gif" border="0" alt="Rambler" vspace="1" width="122" height="60" /></span></a></td>
							</tr>
						</table><br>
						<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
							<tr>
								<td valign="top" width="100%">
									<table width="100%" height="200" border="0" align="right" cellpadding="1" cellspacing="1">
										<tr>
											<td valign="top">
												<form method='post' action='{if $HAS_RSID}index_start.php{else}index_login.php{/if}'>
													<table width='100%' cellpadding='2' cellspacing='1' border='0' class='forumline'>
														<tr>
															<td height='25' align="center" class='catHead'>
																<span class='genblue'><b>Вход</b></span>
															</td>
														</tr>
														<tr>
															<td align="center" class='row1'>
															{if $HAS_RSID}
																<br/><br/><br/>
																<input type='submit' class='mainoption' value='ENTER GAME' />
																<br/><br/><br/>
															{else}
																<br>
																<p><span class='gen' style='line-height=150%'>Имя пользователя (ник):<br />
																	<input class='post' type='text' name='login' size='15' /><br />
																	Пароль:<br />
																	<input class='post' type='password' name='pass' size='15' /><br /><br/>
																	<input type='hidden' name="enter" value="Login">
																	<input type='submit' class='mainoption' value='LOGIN' /><br /><br /></span></p>
																	<a class='gen' href='http://portal.destinysphere.ru/forum/profile.php?mode=sendpassword'>Я забыл свой пароль!</a><br /><br />
																	<a class='gen' href='index_registration.php'>Регистрация</a><br /><br />
																<p>
																<span class='gensmall' style='line-height=150%'>Обратите внимание, что при вводе данных написание прописных и строчных букв имеет значение.<br>Игра требует, чтобы на вашем компьютере был установлен плагин Macromedia Flash версии не ниже чем <a href="http://www.macromedia.com/software/flash/about/" target="_blank">6.0.65.0</a>.<br />&nbsp;</span></p>
															{/if}
															</td>
														</tr>
													</table>
												</form>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>	
	    				<table width="100%" border="0" align="right" cellpadding="1" cellspacing="1">
              				<tr> 
                				<td valign="top"> <form method='post' action='index_recommend.php' target="_blank">
                    				<table width="100%" border="0" cellspacing="0" cellpadding="0">
                      					<tr> 
                        					<td align="center" class="copyright">&copy; Copyright 2003-2006 by DestinySphere GmbH</td>
                      					</tr>	
                      					<!--tr> 
                        					<td align="center" class="copyright"><a href="#">О создателях</a></td>
                      					</tr-->
                    				</table>
                    			</td>
              				</tr>
            			</table>
					</td>
				</tr>
			</table>
		</td>
		<td width="30" background="images/corner_bg3.gif">&nbsp;</td>
	</tr>
	<tr>
		<td height="30" width="30" background="images/corner3.gif">&nbsp;</td>
		<td height="30" background="images/corner_bg4.gif">&nbsp;</td>
		<td height="30" width="30" background="images/corner4.gif">&nbsp;</td>
	</tr>
</table>
</body>
</html>
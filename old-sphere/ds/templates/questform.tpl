<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<link rel="stylesheet" href="../dsstyle.css" type="text/css">
	<link rel="top" href="index.php" title="" />
	<link rel="search" href="search.php" title="" />
	<link rel="help" href="faq.php" title="" />
	<link rel="author" href="memberlist.php" title="" />
	
	<title>{$lang.TITLE}</title>
    <script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
    <script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
</head>
<body>
<table width=100% height=95% border=0 cellpadding=0 cellspacing=0>
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
										<param name="quality" value="low">
										<param name="menu" value="false">
										<param name="settings" value="false">
										<param name="wmode" value="opaque">
										<param name="bgcolor" value="#ffffff">
										<embed src="../img/flash/header.swf" quality="low" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
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
		<td valign=top height=100%>
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
										<!-- -->
										{if !empty($MSG)}
										<tr>
											<td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/t.gif"></td>
											<td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
										</tr>
										<tr>
											<td width=4 background="../img/border/02/l.gif"></td>
											<td width=100% align="center" class=wnd_inner>
												<div class="wnd_gen_txt_large"><b><font color='#993333'><li>{$lang.$MSG}</li></font></b></div>
											</td>
											<td width=4 background="../img/border/02/r.gif"></td>
										</tr>
										<tr>
											<td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/b.gif"></td>
											<td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
										</tr>
										{/if}
										<!-- -->
										
										<tr height=25>
											<td colspan=3 align=center valign="bottom">
												<img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><b>{$lang.CAPTION}</b></span></td>
										</tr>										
										<tr>
											<td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/t.gif"></td>
											<td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
										</tr>
										<tr>
											<td width=4 background="../img/border/02/l.gif"></td>
											<td width=100% class=wnd_inner align="center">
												<form action="" method="POST">
												<table width=100% border=0 cellpadding=3 cellspacing=3>
													<tr>
														<td width="50%" align="right" class="wnd_gen_txt_large">
															{if $MSG != 'ANSWER_SEND'}<input type="hidden" name="add" value="1">{/if}
															<span class="wnd_gen_txt_large"><b>{$lang.NAME}: </b></span>
														</td>
														<td>
															<input type="text" class="input_login_wnd" name="username" size="20" maxlength="30" value="{$USERNAME}">
														</td>
													</tr>
													<tr>
														<td align="right">
															<span class="wnd_gen_txt_large"><b>{$lang.WORLD}: </b></span>
														</td>
														<td>
															<select name="world" class="select_gen">
																{foreach from=$WORLDS item=WORLD}
																	<option value="{$WORLD.world_identifier}" {if $SEL_WORLD == $WORLD.world_identifier}selected{/if}>{$WORLD.worldname}
																{/foreach}
															</select>
														</td>
													</tr>
													<tr>
														<td align="right">
															<span class="wnd_gen_txt_large"><b>{$lang.KEY_WORD}: </b></span>
														</td>
														<td>
															<input type="text" class="input_login_wnd" size="20" name="key_word" maxlength="50" value="{$KEYWORD}">
														</td>
													</tr>
													<tr>
														<td colspan=2 align="center" class="wnd_gen_txt_large">
															<span class="wnd_gen_txt_large"><b>{$lang.INFO}: </b></span><br>
															<textarea cols="60" rows="15" name="info" class="wnd_gen_txt_large">{$INFO}</textarea><br>
														</td>
													</tr>
													<tr>
														<td colspan=2 align="center" class="wnd_gen_txt_large">
															<input type="submit" class="input_button" value="{$lang.SEND}" {if $MSG == 'ANSWER_SEND'}disabled{/if}>
														</td>
													</tr>
												</table>
												</form>											
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
								<td width=5 background="../img/border/01/r.gif"></td>
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
		<td align=center class=copyright_txt>{$lang.COPYRIGHT}</td>
	</tr>
	<tr>
		<td align="center" class="copyright_txt"><a href="{$lang.ABOUT_HREF}" target="_blank" class=copyright_txt>{$lang.ABOUT}</a></td>
	</tr>
	<tr> 
		<td align=center>
			{$lang.COUNTERS}
		</td>
	</tr>
</table>
</body>
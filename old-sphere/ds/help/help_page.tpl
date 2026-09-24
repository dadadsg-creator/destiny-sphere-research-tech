<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
		<title>DestinySphere - Unlimited :: Помощь - <?php echo $helpTitle;?></title>
		<link rel="stylesheet" href="../cabinetstyle.css" type="text/css">
		<script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script>
		<script src="https://unpkg.com/@ruffle-rs/ruffle"></script>
		<style>
			.help_content, .help_content font { font-family: Tahoma, Verdana, Arial, sans-serif !important; }
		</style>
	</head>
	<body>
		
<script language="javascript">
<!--
function openURL (url, target) {
	window.open(url, target);
}
//-->
</script>

<table width=100% height=100% border=0 cellpadding=0 cellspacing=0 class=bg_top>
	<tr>
		<td height=100% align=center valign=top>
			<table width=875 border=0 cellpadding=0 cellspacing=0>
				<tr>
					<td valign=top class=bg_left><img src="../img/cabinet/bg/bg_left.jpg" border=0></td>
					<td height=100% valign=top class=bg01>
						<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
							<!--begin top line-->
							<tr>
								<td height=10 background="../img/cabinet/bg/bg_head_02.gif"></td>
							</tr>
							<!--end top line-->
							
							<!--begin header-->
							<tr>
								<td>
									<table width=100% height=88 border=0 cellpadding=0 cellspacing=0>
										<tr>
											<td width=860>
												<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="860" height="88" id="header_1" align="middle">
													<param name="allowScriptAccess" value="sameDomain">
													<param name="movie" value="../../img/flash/header_01.swf">
													<param name="quality" value="high">
													<param name="menu" value="false">
													<param name="settings" value="false">
													<param name="wmode" value="opaque">
													<param name="bgcolor" value="#ffffff">
													<embed src="../../img/flash/header_01.swf" quality="high" wmode="opaque" bgcolor="#ffffff" width="860" height="88" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
												</object></td>
										</tr>
									</table>
								</td>
							</tr>
							<!--end header-->
							
							<!--begin menu top-->
							<tr>
								<td align=center class=bg_top_menu>
									<table height=34 border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td align=center valign="top">
												<img height=34 src="../img/cabinet/graph/del_but01.gif" border=0></td>
											<td width="110" height="22" align=center valign="middle" class="menu_txt">
												<div class=menu onclick="openURL('../../index.php');" valign=middle><span style="position: relative; top: 9px;">ПОРТАЛ</span></div></td>
											<td align=center valign="top">
												<img height=34 src="../img/cabinet/graph/del_but01.gif" border=0></td>
											<td width="110" height="22" align=center valign="middle" class="menu_txt">
												<div class=menu onclick="openURL('http://forum.ds-game.ru/');" valign=middle><span style="position: relative; top: 9px;">ФОРУМ</span></div></td>
											<td align=center valign="top">
												<img height=34 src="../img/cabinet/graph/del_but01.gif" border=0></td>
											<td width="110" height="22" align=center valign="middle" class="menu_txt">
												<div class=menu onclick="openURL('https://billing.destinysphere.ru/');" valign=middle><span style="position: relative; top: 9px;">БИЛЛИНГ</span></div></td>
											<td align=center valign="top">
												<img height=34 src="../img/cabinet/graph/del_but01.gif" border=0></td>
										</tr>
									</table>
								</td>
							</tr>
							<!--end menu top-->

							<?php 
							if(isset($buildName) && !empty($buildName))
							{
							?>
							<tr>
								<td align=center>
							
							<?php
								
								include($_SERVER['DOCUMENT_ROOT']."/sys_dyn_components/Buildings.php");
								
							?>
								</td>
							</tr>
							<?php
							} else {
							?>
							<tr>
								<td align=center>
									<table height=100% border=0 cellpadding=0 cellspacing=0>
										<tr>
											<td>
												<img src="../img/cabinet/graph/a_01.gif" border=0></td>
											<td align=left class=header_txt>
												 <?php echo $helpTitle;?>
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<!--begin center-->
							<tr>
								<td valign=top style="padding: 5px; padding-top:0px;">
									<table width=100% border=0 cellpadding=0 cellspacing=0>
										<tr>
											<td><img src="../img/cabinet/border/03/t_l.gif" border=0></td>
											<td background="../img/cabinet/border/03/t.gif"></td>
											<td><img src="../img/cabinet/border/03/t_r.gif" border=0></td>
										</tr>
										<tr>
											<td background="../img/cabinet/border/03/l.gif"></td>
											<td width=100% align=left bgcolor="#ffffff" style="padding:5px;" class="help_content">
												 <?php echo $helpContent;?>
											</td>
											<td background="../img/cabinet/border/03/r.gif"></td>
										</tr>
										<tr>
											<td><img src="../img/cabinet/border/03/b_l.gif" border=0></td>
											<td background="../img/cabinet/border/03/b.gif"></td>
											<td><img src="../img/cabinet/border/03/b_r.gif" border=0></td>
										</tr>
									</table>
								</td>
							</tr>
							<!--end center-->
							<?php
							}
							?>
							<tr>
								<td height=100%></td>
							</tr>
							
							<tr>
								<td height=3 class=f_d01></td>
							</tr>
							<!--end line-->
							
							<!--begin footer-->
							<tr>
								<td>
									<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
										<tr>
											<td width=100% class=f_bg01>
												<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
													<tr>
														<td align=center>
															<div valign=middle onclick="openURL('../../?p=press');" class=b_menu><span style="position: relative; top: 20px;">ПРЕССА</span></div></td>
														<td width=2><img src="../img/cabinet/btn/b_d01.gif" border=0></td>
														<td align=center>
															<div valign=middle onclick="openURL('../../?p=support_contact');" class=b_menu><span style="position: relative; top: 20px;">КОНТАКТЫ</span></div></td>
														<td width=2><img src="../img/cabinet/btn/b_d01.gif" border=0></td>
														<td align=center>
															<div valign=middle onclick="openURL('../../?p=about');" class=b_menu><span style="position: relative; top: 20px;">О НАС</span></div></td>
														<td width=2><img src="../img/cabinet/btn/b_d01.gif" border=0></td>
													</tr>
												</table>
											</td>
											<td height=100% valign=top>
												<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
													<tr>
														<td height=22 valign=top><img height=22 src="../img/cabinet/bg/bg_footer_t_l.gif" border=0></td>
													</tr>
													<tr>
														<td align=right valign=middle class=copyrights background="../img/cabinet/bg/bg_footer_b_l.gif">&nbsp;2003-<?php echo date('Y');?> © DestinySphere GmbH, ООО Сфера Судьбы. All Rights Reserved.</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<!--end footer-->
						</table>
					</td>
					<td valign=top class=bg_right><img src="../img/cabinet/bg/bg_right.jpg" border=0></td>
				</tr>
				<tr>
					<td><img src="../img/cabinet/bg/bg_b_l.gif" border=0></td>
					<td background="../img/cabinet/bg/bg_b_b.gif"></td>
					<td><img src="../img/cabinet/bg/bg_b_r.gif" border=0></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php include "../counters.php"; ?>
</body></html>
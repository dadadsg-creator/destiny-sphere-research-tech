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
	<script type="text/javascript" language="JavaScript" src="rotation.js"></script>
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
										{if !empty($ERROR)}
										<tr>
											<td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/t.gif"></td>
											<td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
										</tr>
										<tr>
											<td width=4 background="../img/border/02/l.gif"></td>
											<td width=100% align="center" class=wnd_inner>
												<div class="wnd_gen_txt_large" style="font-size: 10pt;"><b><font color='#993333'><li>{$lang.$ERROR}</li></font></b></div>
												<div align="center">
												<form action="Rotation.php" method="POST">
													<input type="hidden" name="action" value="index">
													<input type="submit" value="{$lang.BACK}" class="input_button">
												</form>
												</div>
											</td>
											<td width=4 background="../img/border/02/r.gif"></td>
										</tr>
										<tr>
											<td width=4 height=4><img src="../img/border/02/b_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/b.gif"></td>
											<td width=4 height=4><img src="../img/border/02/b_r.gif" border=0 width=4 height=4></td>
										</tr>
										{else}
										
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
											<td width=100% class=wnd_inner align="center" >
												<form action="Rotation.php" method="POST" id="ACTION_SELECT" name="ACTION_SELECT">
												<table width=100% border=0 cellpadding=3 cellspacing=3>
													{if $MODE == 'INDEX'}
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.COMB_SELECT}</b>:
														</td>
														<td align="left" width=55%>
															<select name="comb" class=select_gen>{foreach from=$COMBS item=CUR_COMB key=ID}<option value="{$CUR_COMB.id_comb}">{$CUR_COMB.combname} ({$CUR_COMB.comb_coords}){/foreach}</select>
														</td>
													</tr>
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.ACTION}</b>:
														</td>
														<td align="left" width=55%>
															<select name="action" class=select_gen onchange="setTargetCombBlock();">
																<option value="get_nearest_complex" {if $PREV_ACTION == 0}selected{/if}>{$lang.NEAREST_COMB_ACTION}
																<option value="get_min_time" {if $PREV_ACTION == 1}selected{/if}>{$lang.MIN_TIME_ACTION}
																<option value="times_to_complex" {if $PREV_ACTION == 2}selected{/if}>{$lang.TIME_TO_COMPLEX}
															</select>
														</td>
													</tr>
													<tr>
														<td colspan="2">
															<div name="COMB_BLOCK" id="COMB_BLOCK">
																<table width=100% border=0 cellpadding=3 cellspacing=3>
																	<tr>
																		<td align="right" width="45%" class="wnd_gen_txt_large">
																			<b>{$lang.TARGET_COMB}</b>:
																		</td>
																		<td align="left" width=55% class="wnd_gen_txt_large">
																			<input type="text" name="ring" size="1" maxlength="1" class=select_gen> <b>.</b>
																			<input type="text" class=select_gen name="complex" size="4" maxlength="4"> <b>.</b>
																			<input class=select_gen type="text" name="t_comb" size="1" maxlength="1">
																		</td>
																	</tr>
																</table>
															</div>
														</td>
													</tr>
													<tr>
														<td colspan="2">
															<div name="TIME_BLOCK" id="TIME_BLOCK">
																<table width=100% border=0 cellpadding=3 cellspacing=3>
																	<tr>
																		<td align="right" width="45%" class="wnd_gen_txt_large">
																			<b>{$lang.START_TIME}</b>:
																		</td>
																		<td align="left" width=55% class="wnd_gen_txt_large">
																			<select name="date_Y" class=select_gen>
																				{section name=Y start=$YEAR loop=$MAX_YEAR step=1}<option value="{$smarty.section.Y.index}">{$smarty.section.Y.index}{/section}
																			</select> <b>-</b>
																			<select name="date_m" class=select_gen>
																				{section name=M start=1 loop=12 step=1}
																					<option value="{if $smarty.section.M.index < 10}0{/if}{$smarty.section.M.index}" {if $smarty.section.M.index == $MONTH}selected{/if}>{if $smarty.section.M.index < 10}0{/if}{$smarty.section.M.index}
																				{/section}
																			</select> <b>-</b> 
																			<input type="text" class=select_gen name="date_d" size="1" maxlength="2" value="{$DAY}">
																			&nbsp;&nbsp;&nbsp;
																			<select name="date_H" class=select_gen>
																				{section name=H start=0 loop=24 step=1}
																					<option value="{if $smarty.section.H.index < 10}0{/if}{$smarty.section.H.index}" {if $smarty.section.H.index == $HOUR}selected{/if}>{if $smarty.section.H.index < 10}0{/if}{$smarty.section.H.index}
																				{/section}
																			</select> <b>:</b> 
																			<input type="text" class=select_gen name="date_i" size="1" maxlength="2" value="00">
																		</td>
																	</tr>
																	<tr>
																		<td align="right" width="45%" class="wnd_gen_txt_large">
																			<b>{$lang.END_TIME}</b>:
																		</td>
																		<td align="left" width=55% class="wnd_gen_txt_large">
																			<select name="end_date_Y" class=select_gen>
																				{section name=Y start=$YEAR loop=$MAX_YEAR step=1}<option value="{$smarty.section.Y.index}">{$smarty.section.Y.index}{/section}
																			</select> <b>-</b>
																			<select name="end_date_m" class=select_gen>
																				{section name=M start=1 loop=12 step=1}
																					<option value="{if $smarty.section.M.index < 10}0{/if}{$smarty.section.M.index}" {if $smarty.section.M.index == $MONTH}selected{/if}>{if $smarty.section.M.index < 10}0{/if}{$smarty.section.M.index}
																				{/section}
																			</select> <b>-</b> 
																			<input type="text" class=select_gen name="end_date_d" size="1" maxlength="2" value="{$DAY}">
																			&nbsp;&nbsp;&nbsp;
																			<select name="end_date_H" class=select_gen>
																				{section name=H start=0 loop=24 step=1}
																					<option value="{if $smarty.section.H.index < 10}0{/if}{$smarty.section.H.index}" {if $smarty.section.H.index == ($HOUR + 2)}selected{/if}>{if $smarty.section.H.index < 10}0{/if}{$smarty.section.H.index}
																				{/section}
																			</select> <b>:</b> 
																			<input type="text" class=select_gen name="end_date_i" size="1" maxlength="2" value="00">
																		</td>
																	</tr>
																</table>
															</div>
														</td>
													</tr>
													<tr>
														<td colspan="2" align="center"><input type="submit" value="{$lang.SUBMIT}" class="input_button"></td>
													</tr>
													<tr>
														<td colspan="2" align="center" class="wnd_gen_txt_large">
															<div name="HINT_1" id="HINT_1">
																{$lang.HINT_1}
															</div>
														</td>
													</tr>
													<tr>
														<td colspan="2" align="center" class="wnd_gen_txt_large">
															<div name="HINT_2" id="HINT_2">
																{$lang.HINT_2}
															</div>
														</td>
													</tr>
													<tr>
														<td colspan="2" align="center" class="wnd_gen_txt_large">
															<div name="HINT_3" id="HINT_3">
																{$lang.HINT_3}
															</div>
															<script language="JavaScript" type="text/javascript">
															<!--
																{if $PREV_ACTION == 0}
																document.getElementById("COMB_BLOCK").style.display = "none";
																{/if}
																{if $PREV_ACTION == 0 || $PREV_ACTION == 1}
																document.getElementById("TIME_BLOCK").style.display = "none";
																{/if}
																
																{if $PREV_ACTION == 0}
																document.getElementById("HINT_2").style.display = "none";
																document.getElementById("HINT_3").style.display = "none";
																{else}
																	{if $PREV_ACTION == 1}
																	document.getElementById("HINT_1").style.display = "none";
																	document.getElementById("HINT_3").style.display = "none";
																	{else}
																		{if $PREV_ACTION == 2}
																		document.getElementById("HINT_1").style.display = "none";
																		document.getElementById("HINT_2").style.display = "none";
																		{/if}
																	{/if}
																{/if}
															//-->
															</script>
														</td>
													</tr>
													{/if}
													{if $MODE == 'NEAR_COMPLEX'}
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.COMB_SELECT}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{$START_COMB}
														</td>
													</tr>
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.MIN_COMPLEX}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{foreach from=$COMPLEXES item=COMPLEX key=RING}
																<b>{$RING}.{$COMPLEX}.*</b><br>
															{/foreach}
														</td>
													</tr>
													<tr>
														<td  align="center" colspan="2">
															<form action="Rotation.php" method="POST">
																<input type="hidden" name="action" value="index">
																<input type="hidden" name="selected" value="0">
																<input type="submit" value="{$lang.BACK}" class="input_button">
															</form>
														</td>
													</tr>
													{/if}
													{if $MODE == 'MIN_TIME'}
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.COMB_SELECT}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{$START_COMB}
														</td>
													</tr>
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.TARGET_COMB}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{$END_COMB}
														</td>
													</tr>
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.FLY_TIME}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{$FLY_TIME}
														</td>
													</tr>
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large" valign="top">
															<b>{$lang.TIME}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{foreach from=$TIMES item=TIME}															
															<b>{$TIME}</b><br>
															{/foreach}
														</td>
													</tr>
													<tr>
														<td  align="center" colspan="2">
															<form action="Rotation.php" method="POST">
																<input type="hidden" name="action" value="index">
																<input type="hidden" name="selected" value="1">
																<input type="submit" value="{$lang.BACK}" class="input_button">
															</form>
															<!-- {$NOW} -->
														</td>
													</tr>
													{/if}
													{if $MODE == 'TIMES_TO_COMPLEX'}
													<tr>
														<td align="right" width="45%" class="wnd_gen_txt_large">
															<b>{$lang.COMB_SELECT}</b>:
														</td>
														<td align="left" width=55% class="wnd_gen_txt_large">
															{$START_COMB}
														</td>
													</tr>
													<tr>
														<td align="right" class="wnd_gen_txt_large">
															<b>{$lang.TARGET_COMB}</b>:
														</td>
														<td align="left" class="wnd_gen_txt_large">
															{$END_COMB}
														</td>
													</tr>
													<tr>
														<td align="right" class="wnd_gen_txt_large">
															<b>{$lang.TRAVEL_DATE}</b>
														</td>
														<td align="left" class="wnd_gen_txt_large">
															<b>{$lang.TRAVEL_TIME}</b>
														</td>
													</tr>
													{foreach from=$TIMES item=TIME}
													<tr>
														<td align="right" class="wnd_gen_txt_large">
															{$TIME.date}
														</td>
														<td align="left" class="wnd_gen_txt_large">
															 <b>{$TIME.time}</b>
														</td>
													</tr>
													{/foreach}
													<tr>
														<td  align="center" colspan="2">
															<form action="Rotation.php" method="POST">
																<input type="hidden" name="action" value="index">
																<input type="hidden" name="selected" value="2">
																<input type="submit" value="{$lang.BACK}" class="input_button">
															</form>
														</td>
													</tr>
													{/if}
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
										{/if}
										<tr>
											<td colspan=3 height=25 align=center valign=bottom>
												<!--img src="../img/elements/cap_div.gif" border=0><span class='wnd_header_txt_large'><!--b>{$lang.EXISTING_WORLDS}</b--></span></td>
										</tr>
										<!--Description world-->
										<tr>
											<td width=4 height=4><img src="../img/border/02/t_l.gif" border=0 width=4 height=4></td>
											<td width=100% height=4 background="../img/border/02/t.gif"></td>
											<td width=4 height=4><img src="../img/border/02/t_r.gif" border=0 width=4 height=4></td>
										</tr>
										<tr>
											<td width=4 background="../img/border/02/l.gif"></td>
											<td width=100% class=wnd_inner>
												<table width=100% height=100% border=0 cellpadding=0 cellspacing=0>
													<tr>
														<td colspan=2 align="center" class="wnd_gen_txt_large">
															<a href="javascript:window.close();" class='wnd_header_txt_large'>закрыть окно</a>
														</td>
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
									<!-- end world collection -->
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
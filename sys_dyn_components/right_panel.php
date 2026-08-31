	<? $movie_num = rand(1, 2); ?>
	document.write('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="160" height="116" id="header_1" align="middle">');
	document.write('<param name="allowScriptAccess" value="sameDomain">');
	document.write('<param name="movie" value="/img/banners/left_panel/ban'+<? echo $movie_num; ?>+'.swf">');
	document.write('<param name=quality value=high>');
	document.write('<param name="menu" value="false">');
	document.write('<param name="settings" value="false">');
	document.write('<param name="wmode" value="transparent">');
	document.write('<param name="bgcolor" value="#ffffff">');
	document.write('<embed src="/img/banners/left_panel/ban'+<? echo $movie_num; ?>+'.swf" quality="high" wmode="transparent" bgcolor="#ffffff" width="160" height="116" name="header_1" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">');
	document.write('</object>');


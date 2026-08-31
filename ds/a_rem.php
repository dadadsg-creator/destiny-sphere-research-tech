<STYLE>

/* This is the outline round the main forum tables */
.forumline	{
	border: 0px #000000 solid;
	background-image : url(images/background2.gif);
}

/* General font families for common tags */
td {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size : 12px;
}
.header {
	color:orange;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size:14px;
	font-weight : bold;
}
.lnk:link, .lnk:visited, .lnk:hover {
	color:#eeeeee;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size:12px;
	font-weight : bold;
}
</style>


<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
   	<td>
    		<table width='100%' height="100%" border='0' cellpadding='0' cellspacing='0'>
				<tr>
    				<td width="30" height="30"><img src="images/corner1.gif" border=0></td>
    				<td width=100% height="30" background="images/corner_bg1.gif">&nbsp;</td>
    				<td width="30" height="30"><img src="images/corner2.gif" border=0></td>
				</tr>
        		<tr> 
				   <td width="30" background="images/corner_bg2.gif" >&nbsp;</td>
          		<td width=100% height="100%" align="center" valign="middle"> 
		         	<table width="100%" align="center" border=0 cellpadding='0' cellspacing='0'>
							<tr>
								<td align="center" class=header>
									Пожалуйста, посетите наших спонсоров!
								</td>
							</tr>
							<tr>
								<td height=10 align="center">
								</td>
							</tr>
							<tr>
								<td align="center">
								<?php 
								$ads_rate = rand(1, 2); 
								switch( $ads_rate )
								{
									case '1':
										echo "<iframe src='./flash/600x400xpro_DSU_PREMIUM.swf?link1=/ds/sms.php%3Faction=PREMIUM' frameborder=0 width=600 height=400 marginwidth=0 marginheight=0 scrolling=no></iframe>";
										break;
									default:
										echo "<iframe src='./flash/600x400xpro_DSU_CREDIT.swf?link1=/ds/sms.php%3Faction=CREDIT' frameborder=0 width=600 height=400 marginwidth=0 marginheight=0 scrolling=no></iframe>";
										break;
								}
								?>
								</td>
							</tr>
							<tr>
								<td height=10 align="center">
								</td>
							</tr>
							<tr>
								<td align="center">
									<a href="index.php<?php echo "?ck=".$_SESSION['client_key'];?>" class=lnk>Перейти к игре >></a>
								</td>
							</tr>
						</table>
            	</td>
				   <td width="30" height=100% background="images/corner_bg3.gif">&nbsp;</td>
        		</tr>
				<tr>
    				<td width="30" height="30"><img src="images/corner3.gif" border=0></td>
    				<td width=100% height="30" background="images/corner_bg4.gif">&nbsp;</td>
    				<td width="30" height="30"><img src="images/corner4.gif" border=0></td>
    			</tr>
      	</table> 
    	</td>
	</tr>
</table>

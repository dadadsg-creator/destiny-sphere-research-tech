<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";

$VIS_ADMIN = 0;
$UserManager->InitMainMenu();
if ($UserManager->ErrMsg) $ErrMsg = $UserManager->ErrMsg;

if (!$UserManager->CheckMenuUrl($PHP_SELF)) {
	$RedirectUrl = $UserManager->GetFirstMainMenuUrl();
//	if ($RedirectUrl)  Header("Location: $RedirectUrl");
}

$MainMenu = $UserManager->GetMainMenu();
?>
<html>
<head>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=Windows-1251">
</head>
<SCRIPT language=JavaScript>
<!--
css = '"/Admin/styles/ie.css">';
nn4 = (document.layers);
ie4 = (document.all);
if (nn4) { css = '"/Admin/styles/nn.css">';}
document.write('<LINK REL="stylesheet" TYPE="text/css" HREF=' + css);
//-->
</SCRIPT>

<script src="/Admin/js/menu.js"></script>

<body margin=0 leftmargin=0 topmargin=0 bgcolor="FFFFFF" marginwidth=0 marginheight=0>
<table cellpadding="0" cellspacing="0" border="0" background="/Admin/img/publisher-menu-left-bg.gif" height="100%" width="100%">
	<tr>
		<td valign="top">
			<table cellpadding="0" cellspacing="0" border="0">
<?
				$MenuID = 0;
				while (list($title,$url)=each($MainMenu)) {
					$MenuID++;
					if ($url && substr_count($PHP_SELF,$url))  {
						$bg_color = "/Admin/img/publisher-menu-left-a-bg.gif";
						if (!$ActiveMenu) $ActiveMenu = $title;
						$ActiveMenuID = $MenuID;
					} else {
						$bg_color = "/Admin/img/publisher-menu-left-na-bg.gif";
					}
?>
					<tr>
						<td><img src="/Admin/img/publisher-menu-left-spc-bg.gif" width="170" height="2" alt="" border="0"></td>
					</tr>
					
					<tr>	
						<td background="<?=$bg_color?>" id="d<?=$MenuID?>" onmouseover="showTNav(<?=$MenuID?>)" onmouseout="hideTNav()" onclick="top.document.location.href='<?=$url?>'">
							<table cellpadding="0" cellspacing="0" border="0" width="100%" background="">
								<tr>
									<td><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="4"></td>
									<td width="100%">
										
										<img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" vspace="3"><br>
										<a target="_top" href="<?=$url?>"  class="MMenuLink"><?=$title?></a><br>
										<img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" vspace="3"><br>
									</td>
									<td><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="4"></td>											
								</tr>
							</table>
						</td>
					</tr>
<?
				}
?>
			
				<tr>
					<td bgcolor="#849AB5"><img src="/Admin/img/emp.gif" width="170" height="1" alt="" border="0"></td>
				</tr>	
																																								
			</table>
		</td>
	</tr>
</table>

<br>
<script>
	TimeOut = 300;
	InitMenu('/Admin/img/publisher-menu-left-a-bg.gif','/Admin/img/publisher-menu-left-na-bg.gif');
	showDefaultMenu(<?=$ActiveMenuID?>);
</script>
</body>
</html>
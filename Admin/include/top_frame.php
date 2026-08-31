<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";

$VIS_ADMIN = 0;
?>

<html>
<head>
	<title>Административная консоль</title>
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

<body margin=0 leftmargin=0 topmargin=0 bgcolor="FFFFFF" marginwidth=0 marginheight=0>

<table cellpadding="0" cellspacing="0" border="0" background="/Admin/img/header-bg.gif" width="100%">
	<tr>
		<td>
			<img src="/Admin/img/emp.gif" width="770" height="0" alt="" border="0"><br>
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="100%" align="right" colspan="4"><img src="/Admin/img/emp.gif" width="1" height="10" alt="" border="0">
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td width="325">
									<table cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td>
												<img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="10"><span class="MText"><b><?=$SERVER_NAME?></b></span><br>
											</td>
										</tr>
									</table>
								</td>
								<td width="100%" align="right">

								</td>
							</tr>
							<tr>
								<td><img src="/Admin/img/emp.gif" width="2" height="1" alt="" border="0" hspace="200"></td>
								<td></td>
							</tr>
							<tr>
								<td valign="top"><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="85">
									<?if ($UserManager->CheckSuperAdmin()) {?>
										<a href="/Admin/superadmin/" target="_top" class="topLink">Интерфейс супер-администратора</a>
									<?}?> 
								</td>
								<td align="right">
									<table cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td><img src="/Admin/img/both-top-indic-tbl.gif" width="9" height="24" alt="" border="0"></td>
											<td bgcolor="#6C7B8B"><img src="/Admin/img/both-top-indic-man.gif" width="16" height="24" alt="" border="0" hspace="3"></td>											
											<td bgcolor="#6C7B8B">
												<img src="/Admin/img/emp.gif" width="2" height="1" alt="" border="0"><br>
												<span class="SmallAText">&nbsp;<?=$UserManager->GetUserName()?>&nbsp;</span>
											</td>
											<td bgcolor="#6C7B8B"><img src="/Admin/img/emp.gif" width="3" height="1" alt="" border="0" hspace="0"></td>											
											<td bgcolor="#6C7B8B" valign="bottom"><a target="_top" href="/Admin/login.php?action=logout"><img src="/Admin/img/both-top-indic-lo-button.gif" width="58" height="20" alt="" border="0"></a></td>
											<td bgcolor="#6C7B8B"><img src="/Admin/img/emp.gif" width="1" height="1" alt="" border="0" hspace="10"></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<td bgcolor="#6C7B8B" width="100%" colspan="4"><img src="/Admin/img/emp.gif" width="1" height="4" alt="" border="0"></td></table>
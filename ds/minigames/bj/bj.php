<?
	require_once "../../Main.php";
  SU_GameSessionWork::SessionContinue();
  //SU_DebugTools::LogAll(var_export($_REQUEST, true),'bj.log');


  if (!$_SESSION['uid'])
  {
    echo "Session expired. Login at Destiny Sphere again.";
    exit;
  }

  if (!array_key_exists('cmd',$_REQUEST))
  {
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<title>BlackJack</title>
<script language="javascript">AC_FL_RunContent = 0;</script>
<script src="AC_RunActiveContent.js" language="javascript"></script>
</head>
<body bgcolor="#003300"  leftmargin="0"  topmargin="0">
<!--url's used in the movie-->
<!--text used in the movie-->
<!--
LOAD DATA...
LOAD DATA...
99
9999
9999999
<p align="center"><font face="Arno Pro" size="20" color="#ffff00" letterSpacing="0.000000" kerning="0"><b>9999</b></font></p>
99
99
-->
<!-- saved from url=(0013)about:internet -->
<script language="javascript">
	if (AC_FL_RunContent == 0) {
		alert("This page requires AC_RunActiveContent.js.");
	} else {
		AC_FL_RunContent(
			'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0',
			'width', '600',
			'height', '500',
			'src', 'layOut4',
			'quality', 'high',
			'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
			'align', 'middle',
			'play', 'true',
			'loop', 'true',
			'scale', 'showall',
			'wmode', 'window',
			'devicefont', 'false',
			'id', 'layOut4',
			'bgcolor', '#003300',
			'name', 'layOut4',
			'menu', 'true',
			'allowFullScreen', 'false',
			'allowScriptAccess','sameDomain',
			'movie', 'layOut4',
			'salign', ''
			); //end AC code
	}
</script>
<noscript>
	<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="600" height="500" id="layOut4" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="allowFullScreen" value="false" />
	<param name="movie" value="layOut4.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#003300" />	<embed src="layOut4.swf" quality="high" bgcolor="#003300" width="600" height="500" name="layOut4" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
</noscript>
</body>
</html>

<?  }
    else
    {
      //SU_DebugTools::LogAll(var_export($_REQUEST, true),'bj.log');
    }
?>



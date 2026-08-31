<?php $CVS_Version = '$Revision$ ; $Date$';


require_once 'Main.php';
require_once constant('CONFIG_PATH')."/settings.php";

include ("language/" . DEFAULT_LANGUAGE . "/lang_detect.php");


 if ( !SU_GameSessionWork::SessionContinue () ) {

	$str = "Location: index_login.php";

	Header ( $str );

	exit;

}

/*

if ( empty ( $_SESSION["uid"] ) ) {

	$str = "Location: index_login.php";

	Header ( $str );

	exit;

}



if ( empty ( $_SESSION["uiid"] ) ) {

	$str = "Location: index_start.php";

	Header ( $str );

	exit;

}

*/



class DetectSystem {



	public $errors;



	public $errormsg;

	public $browser;



	public $browserType;

	public $version;

	public $majorver;

	public $minorver;

	public $css;

	public $iframes;

	public $tables;

	public $cookies;

	public $javascript;

	public $activexcontrols;

	public $os;

	public $flashversion;



	public $answer;



	function DetectSystem () {

		$this->browser = @get_browser();

		$this->flashversion = preg_split ("/[\s,]+/", @$_GET['set_swf']);



		//echo ($this->list_array ($this->browser));

		if ( !$this->proceed () ) {

			echo $this->errormsg;

		}

	}



	function proceed () {



		global $lang;



		$this->errors = 0;



		$this->answer = array();



		switch ($this->browser["browser"]) {

		  case "IE":

		  		$this->answer[0] = "<font color='black'>".$lang['OK']."</font>";



		  		switch ($this->browser["majorver"]) {

		  			case "5":

		  			case "6":

		  			case "7":

		  				$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";

		  				break;

		  			default:

		  				$this->answer[1] = "<font color='orange'>".$lang['Bad_compatibility']."</font>";

		  				$this->errors = 1;

		  				break;

		  		}



		  		@$flashvers = (0+@$this->flashversion[1]);

		  		if ($flashvers < 6) {

				  $this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";

				  $this->errors = 1;

		  		}

		  		else if ($flashvers > 7) {

		  		  $this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  		  $this->errors = 1;

		  		}

		  		else {

		  		  $this->answer[3] = "<font color='black'>".$lang['OK']."</font>";

		  		}

		  		break;



		  case "Mozilla":

		  		$this->answer[0] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  		$this->errors = 1;

		  		switch ($this->browser["majorver"]) {

		  			case "1":

		 				$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";

		 				switch ($this->browser["minorver"]) {

				  			case "2":

				 				$this->answer[1] = "<font color='lightgreen'>".$lang['OK']."</font>";

				  				break;

				  			default:

				  				$this->answer[1] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

				  				break;

				  		}

		  				break;

		  			default:

		  				$this->answer[1] = "<font color='red'>".$lang['Bad_compatibility']."</font>";

		  				break;

		  		}



		  		$flashvers = (0+$this->flashversion[1]);

		  		$flashvers2 = (0+$this->flashversion[3]);



		  		if ($flashvers < 6)

				  $this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";

		  		else if ($flashvers >= 7)

		  		  $this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  		else

		  		  if ($flashvers2 != 79)

		  		  	$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";

		  		  else

		  		  	$this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";



		  		break;



		  case "Netscape":

		  		$this->errors = 1;

		  		$this->answer[0] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  		switch ($this->browser["majorver"]) {

		  			case "7":

		 				$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";

		  				break;

		  			default:

		  				$this->answer[1] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  				break;

		  		}



		  		$flashvers = (0+$this->flashversion[1]);

		  		$flashvers2 = (0+$this->flashversion[3]);



		  		if ($flashvers < 6)

				  $this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";

		  		else if ($flashvers >= 7)

		  		  $this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  		else

		  		  if ($flashvers2 != 79)

		  		  	$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";

		  		  else

		  		  	$this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";



		  		break;



		  case "Opera":

		  		$this->errors = 1;

		  		$this->answer[0] = "<font color='red'>".$lang['Bad_compatibility']."</font>";

		  		switch ($this->browser["majorver"]) {

		  			case "5":

		  			case "7":

		 				$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";

		 				switch ($this->browser["minorver"]) {

				  			case "02":

				 				$this->answer[1] = "<font color='lightgreen'>".$lang['OK']."</font>";

				  				break;

				  			default:

				  				$this->answer[1] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

				  				break;

				  		}

		  				break;

		  			default:

		  				$this->answer[1] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  				break;

		  		}



		  		$flashvers = (0+$this->flashversion[1]);

		  		$flashvers2 = (0+$this->flashversion[3]);



		  		if ($flashvers < 6)

				  $this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";

		  		else if ($flashvers >= 7)

		  		  $this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  		else

		  		  if ($flashvers2 != 79)

		  		  	$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";

		  		  else

		  		  	$this->answer[3] = "<font color='red'>".$lang['Bad_compatibility']."</font>";



		  		break;



		  default:

		  		$this->errors = 1;

		  		$this->answer[0] = "<font color='red'>".$lang['Bad_compatibility']."</font>";

		  		$this->answer[1] = "<font color='red'>".$lang['Bad_compatibility']."</font>";

		  		$this->answer[3] = "<font color='red'>".$lang['Bad_compatibility']."</font>";

		  		break;

		}



		if (strcmp ($this->flashversion[0],"WIN") != 0) {

		  $this->answer[2] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  $this->errors = 1;

		}

		else {

		  $this->answer[2] = "<font color='black'>".$lang['OK']."</font>";

		}



		if ($this->browser["tables"] == "1") {

		  $this->answer[4] = "<font color='black'>".$lang['OK']."</font>";

		}

		else {

		  $this->answer[4] = "<font color='red'>".$lang['Critical_error']."</font>";

		  $this->errors = 1;

		}



		if ($this->browser["javascript"] == "1") {

		  $this->answer[5] = "<font color='black'>".$lang['OK']."</font>";

		}

		else {

		  $this->answer[5] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  $this->errors = 1;

		}



		if ($this->browser["iframes"] == "1") {

		  $this->answer[6] = "<font color='black'>".$lang['OK']."</font>";

		}

		else {

		  $this->answer[6] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  $this->errors = 1;

		}



		if ($this->browser["cookies"] == "1") {

		  $this->answer[7] = "<font color='black'>".$lang['OK']."</font>";

		}

		else {

		  $this->answer[7] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

		  $this->errors = 1;

		}





	}





	function list_array ($array) {

    		while (list ($key, $value) = each ($array)) {

    			$str .= "<b>$key:</b> $value<br>\n";

    		}

    		return $str;

	}

}



  $syst = new DetectSystem;



?>



<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<head>

<?

	if ($syst->errors == 0) {

?>

		<meta http-equiv="Refresh" content="0; URL=index.php<? if(!empty($_GET['SIDIX'])) echo "?SIDIX=".$_GET['SIDIX'];?>">

<?

	}

    else {

?>

		<meta http-equiv="Refresh" content="10; URL=index.php<? if(!empty($_GET['SIDIX'])) echo "?SIDIX=".$_GET['SIDIX'];?>">



<?	}

?>





<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">

<meta http-equiv="Content-Style-Type" content="text/css">



<title>:: DestinySphere :: БЕСПЛАТНАЯ стратегическая веб-игра, основанная на технологии Macromedia Flash</title>

<link rel="stylesheet" href="DestinySphere.css" type="text/css">

</head><body bgcolor="#000000" text="#DDDDDD" link="#8BC7FF" vlink="#5493B4">





<script language="Javascript" type="text/javascript">

<!--

function MM_preloadImages() { //v3.0

  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();

    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)

    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}

}

//-->

</script>



<script language="Javascript" type="text/javascript">

<!--

function rollover(name, source)  {



  document.images[name].src = source;



}

//-->

</script>



<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">

<tr>

    <td width="1" height="1"></td>

    <td height="1"></td>

    <td height="1" width="1"></td>

</tr>



<tr valign="top" height="100%">

    <td width="1"></td>

    <td>



<table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#000000">

        <tr>

		  <td valign="top" class="bodyline">

            <table width="100%" cellspacing="0" cellpadding="0" border="0">
              <tr>
            		<td align="center"><a href="http://www.destinysphere.ru"><span class="maintitle"><img src="images/logo_ds.gif" border="0" alt="Главная страница Destiny Sphere" hspace="10" width="229" height="70" /></span></a><a href="http://www.rambler.ru" target="_blank"><span class="maintitle"><img src="images/logorambler.gif" border="0" alt="Rambler" hspace="10" width="108" height="70" /></span></a></td>
              </tr>
            </table>

            <table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td valign="top" width="100%">
                  <table width="100%" height="100%" border="0" align="right" cellpadding="0" cellspacing="0">
                    <tr>
                      <td valign="top">

	  								<table width="100%" cellpadding="0" cellspacing="0" border="0">
  										<tr>
			  						  		<td height="8" class="TopBr1" align="left"><img src="images/border/f_topic_left.gif" border="0"></td>
  											<td height="8" class="TopBr1" align="right"><img src="images/border/f_topic_right.gif" border="0"></td>
			  							</tr>
									</table>

                          <table width="100%" height="100%" border="0" cellpadding="2" cellspacing="1" class="forumline">
                            	<tr>
											<th class="thSides" height="23" valign="middle">Поздравляем!</th>
                            	</tr>

  										<tr>
			  								<td class="TopBr3"></td>
  										</tr>

                            	<tr>

<?



$str = "index.php";

if ( session_id() ) $str .= "?".session_name()."=".session_id();



if (!@$_GET['set_swf']){





?>

                            <td align="center" valign="middle" class='row1'>

                                <strong>Проверка плагина Flash...</strong><br><br><br>

                                <a href="index.php" class="gen">Пропустить проверку</a><br><br><br>

                                <OBJECT classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0' WIDTH='0' HEIGHT='0' id='detect_player' ALIGN=''>
 										  		<PARAM NAME=movie VALUE='detect_player.swf?<?echo session_name()."=".session_id();?>'> <PARAM NAME=quality VALUE=high> <PARAM NAME=bgcolor VALUE=#FFFFFF> <EMBED src='detect_player.swf?<? echo session_name()."=".session_id();?>' quality=high bgcolor=#FFFFFF  WIDTH='0' HEIGHT='0' NAME='detect_player' ALIGN='' TYPE='application/x-shockwave-flash' PLUGINSPAGE='http://www.macromedia.com/go/getflashplayer'></EMBED>
										  </OBJECT>

                               </td>

<?

}

else

{

?>

	                     <td align="center" valign="middle" class='row1'>

                        <strong>Броузер: </strong><?= $syst->answer[0] ?><br>

								<strong>Версия броузера: </strong><?= $syst->answer[1] ?><br>

								<strong>Операционная система: </strong><?= $syst->answer[2] ?><br>

								<strong>Версия Flash: </strong><?= $syst->answer[3] ?><br>

								<strong>HTML: </strong><?= $syst->answer[4] ?><br>

								<strong>Поддержка JavaScript: </strong><?= $syst->answer[5] ?><br>

								<strong>Поддержка IFRAME: </strong><?= $syst->answer[6] ?><br>

								<strong>Cookies: </strong><?= $syst->answer[7] ?><br>

								<br><br>

                        <span class="gen">
								<a href="index.php<? if(!empty($_GET['SIDIX'])) echo "?SIDIX=".$_GET['SIDIX'];?>" class="gen">Перейти к игре >></a><br><br><br><br><br><br>
                        </span>

<?

}

?>





                            </td></tr>

                          </table>



                      </td>

                    </tr>



			      </table>

                </td>

              </tr>

</table>







</td></tr></table>



</td>

    <td width="1"></td>

	</tr>



  <tr>

    <td height="1" width="1"></td>

    <td height="1"></td>

    <td height="1" width="1"></td>



</table>





</body>

</html>

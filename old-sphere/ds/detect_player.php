<?
require_once 'Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';
require_once constant('INCLUDE_PATH').'/Smarty/Smarty.class.php';


if (GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}

if (!SU_GameSessionWork::SessionContinue())
{
	$str = "Location: ".GE_Login::GetPortalHome();
	Header ( $str );
	exit;
}

SU_ClientKey::checkKey(false);
SU_ClientKey::setKey(false);

include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/detect_player.lang");


class DetectSystem
{
	var $errors;
	var $errormsg;
	var $browser;
	var $browserType;
	var $version;
	var $majorver;
	var $minorver;
	var $css;
	var $iframes;
	var $tables;
	var $cookies;
	var $javascript;
	var $activexcontrols;
	var $os;
	var $flashversion;

	var $answer;

	function DetectSystem()
	{
		$this->browser = @get_browser();
//SU_DebugTools::LogAll(print_r($this->browser, true), __CLASS__);
		$this->flashversion = preg_split ("/[\s,]+/", @$_GET['set_swf']);
		
		$_SESSION['FLASH_VERSION'] = @$_GET['set_swf'];

		if ( !$this->proceed () )
		{
			echo $this->errormsg;
		}
	}

	function proceed ()
	{
		global $lang;

		$this->errors = 0;
		$this->answer = array();

		switch ($this->browser->browser)
		{
			case "IE":

			$this->answer[0] = "<font color='black'>".$lang['OK']."</font>";

			switch ($this->browser->majorver)
			{
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
			if ($flashvers < 6)
			{
				$this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";
				$this->errors = 1;
			}
			else if ($flashvers < 7)
			{
				$this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
				$this->errors = 1;
			} else {
				$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";
			}
			break;

			case "Mozilla":
			$this->answer[0] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			$this->errors = 1;

			switch ($this->browser->majorver)
			{
				case "1":
				$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";

				switch ($this->browser->minorver)
				{
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

			if ($flashvers < 7)
			{
				$this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";
			} elseif ($flashvers < 8) {
				$this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			} else {
				$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";
			}

			break;

			case "Netscape":
			$this->errors = 1;
			$this->answer[0] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";

			switch ($this->browser->majorver)
			{
				case "7":
				$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";
				break;

				default:
				$this->answer[1] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
				break;
			}

			$flashvers = (0+$this->flashversion[1]);
			$flashvers2 = (0+$this->flashversion[3]);

			if ($flashvers < 7)
			{
				$this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";
			} elseif ($flashvers < 8) {
				$this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			} else {
				$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";
			}
			break;

			case "Opera":
			$this->errors = 1;
			$this->answer[0] = "<font color='red'>".$lang['Bad_compatibility']."</font>";

			switch ($this->browser->majorver)
			{
				case "7":
				case "8":
				case "9":
					$this->answer[1] = "<font color='black'>".$lang['OK']."</font>";
					break;

				default:
					$this->answer[1] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
					break;
			}

			$flashvers = (0+$this->flashversion[1]);
			$flashvers2 = (0+$this->flashversion[3]);

			if ($flashvers < 7)
			{
				$this->answer[3] = "<font color='red'>".$lang['Critical_error']."</font>";
			} else if ($flashvers < 8) {
				$this->answer[3] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			} else {
				$this->answer[3] = "<font color='black'>".$lang['OK']."</font>";
			}

			break;

			default:
			$this->errors = 1;
			$this->answer[0] = "<font color='red'>".$lang['Bad_compatibility']."</font>";
			$this->answer[1] = "<font color='red'>".$lang['Bad_compatibility']."</font>";
			$this->answer[3] = "<font color='red'>".$lang['Bad_compatibility']."</font>";
			break;
		}

		if (strcmp ($this->flashversion[0],"WIN") != 0)
		{
			$this->answer[2] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			$this->errors = 1;
		} else {
			$this->answer[2] = "<font color='black'>".$lang['OK']."</font>";
		}

		if ($this->browser->tables == "1")
		{
			$this->answer[4] = "<font color='black'>".$lang['OK']."</font>";
		} else {
			$this->answer[4] = "<font color='red'>".$lang['Critical_error']."</font>";
			$this->errors = 1;
		}

		if ($this->browser->javascript == "1")
		{
			$this->answer[5] = "<font color='black'>".$lang['OK']."</font>";
		} else {
			$this->answer[5] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			$this->errors = 1;
		}

		if ($this->browser->iframes == "1")
		{
			$this->answer[6] = "<font color='black'>".$lang['OK']."</font>";
		} else {
			$this->answer[6] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			$this->errors = 1;
		}

		if ($this->browser->cookies == "1")
		{
			$this->answer[7] = "<font color='black'>".$lang['OK']."</font>";
		} else {
			$this->answer[7] = "<font color='orange'>".$lang['Reduced_compatibility']."</font>";
			$this->errors = 1;
		}
//SU_DebugTools::LogAll(print_r($this->answer, true), __CLASS__);
//SU_DebugTools::LogAll(print_r($this->flashversion, true), __CLASS__);
	}
}

$syst = new DetectSystem;

$tpl = new Smarty;
$tpl->template_dir = "templates/";
$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
$tpl->compile_check = true;
$tpl->debugging = false;


if (!@$_GET['set_swf'])
{
	// тут подгружаем темплейт с detect_swf
	$tpl->assign("CONTENT",array());
} else {
	//тут подгружаем блок с результатами сверки
	$tpl->assign("CONTENT", array(
									$syst->answer[0],
									$syst->answer[1],
									$syst->answer[2],
									$syst->answer[3],
									$syst->answer[4],
									$syst->answer[5],
									$syst->answer[6],
									$syst->answer[7]
								)
	);
}

if ($syst->errors == 0)
{
	//0
	$tpl->assign("REFRESH",0);
} else {
	//10
	$tpl->assign("REFRESH",10);
}

$url_index = "index.php?ck=".$_SESSION['client_key'];;
if (!GL_VersionPathWork::ValidateVersionPath($_SESSION['dbId']))
{
	$dir = GL_VersionPathWork::GetVersionPath($_SESSION['dbId']);
	$url_index = $dir.$url_index;
}
//var_dump($url_index);
//var_dump(GL_VersionPathWork::ValidateVersionPath($_SESSION['dbId']));


//die();


if ( session_id() ) $url_index .= "&".session_name()."=".session_id();
$tpl->assign("URL_INDEX",$url_index);

$url_swf = "detect_player.swf"."?ck=".$_SESSION['client_key'];
if ( session_id() ) $url_swf .= "&".session_name()."=".session_id();
$tpl->assign("URL_SWF",$url_swf);

$tpl->assign("lang",$lang);


$tpl->display('detect_player.tpl');

?>

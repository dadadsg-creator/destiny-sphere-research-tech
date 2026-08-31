<?php 
ini_set('display_errors', '1');

$CVS_Version = '$Revision: 1.12 $ ; $Date: 2010/03/29 09:03:43 $';
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

require_once( 'Main.php' );
//require_once ( "./IT.php" );
//require_once("../Admin/include/class/Forms.class.php");
require_once('classes/Validator.class.php');
require_once 'include/Smarty/Smarty.class.php';

if(!SU_GameSessionWork::SessionContinue())
{
	$str = "Location: index_login.php";
	Header($str);
	exit;
}


//Проверям не закрыта ли игра
require_once('include/GameDown.inc.php');
if (GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}


class Profile
{
	var $common = false;
	var $uid = false;
	var $errorMsg = array();
	var $lang = false;
	var $user = false;
	var $user_language = array();
	var $profi;
	var $currentDbId;

	function Profile()
	{
		assert(isset($_SESSION));

		assert(key_exists('uid', $_SESSION));
		assert(key_exists('profi_game', $_SESSION));
		assert(key_exists('dbId', $_SESSION));

		$this->currentDbId = $_SESSION["dbId"];

		$this->profi = $_SESSION['profi_game'];

		$_SESSION["dbId"] = 0;
		
		$this->common = DS_Common::CreateForCurentDB(0);

		$this->user = DE_User::CreateFromID((int)$_SESSION['uid']);

		$this->user_language = $this->getLangArray();
		assert(sizeof($this->user_language) > 0);

		if ($this->checkLocalParams())
		{
			if (!$this->getPostData($_POST))
			{
				$this->showProfile($showError = true);
			}
			else
			{
				$this->showProfile($showError = false);
			}
		}else{
			$this->showProfile($showError = true);
		}
	}

	function checkLocalParams()
	{
		if (sizeof($_POST) <= 0)
		{
			//$this->errorMsg[] = 'Not send POST data!';
			return false;
		}

		if (!key_exists('uid', $_SESSION))
		{
			$this->errorMsg[] = "Undefined uid!";
			return false;
		}


		if (!isset($_POST['lang']))
		{
			$this->errorMsg[] = "Undefined Lang!";
			return false;
		}

		if(!key_exists($_POST['lang'],$this->user_language))
		{
			$this->errorMsg[] = "Undefined Lang!";
			return false;
		}

		if(!isset($_POST['time_delta']))
		{
			if (!isset($_SESSION['time_delta']))
			{
				$this->errorMsg[] = "Undefined time delta!";
				return false;
			}

			$_POST['time_delta'] = $_SESSION['time_delta'];
		}

		if (!is_numeric($_POST['time_delta']))
		{
			$this->errorMsg[] = "Wrong time delta!1";
			return false;
		}

		if (((real)$_POST['time_delta'] < -13) || ((float)$_POST['time_delta'] > 12))
		{
			$this->errorMsg[] = "Wrong time delta!2";
			return false;
		}

		return true;
	}

	function getPostData($data)
	{
		if(isset($data['modified']))
		{
			if(isset($data['new_password']) && strlen($data['new_password']) > 0)
			{
				if(strcmp(md5($data['old_password']),$this->user->getPassword()) == 0)
				{
					$valid = new ValidatePassword($data['new_password'],
					$data['confirm_password'],
					$this->user->getName());
					if ( !$valid->isValid())
					{
						$this->errorMsg[] = $valid->getError();
						return false;
					}

					$data['new_password'] = md5($data['new_password']);
				}
				else
				{
					$this->errorMsg[] = 'WRONG_OLD_PASSWORD';
					return false;
				}
			}
			else
			{
				$data['new_password'] = $this->user->getPassword();
			}

			if($this->profi)
			{
				$scaling = false;

				if(isset($data['scaling']) && $data['scaling'] == 'on')
				{
					$scaling = true;
				}

				$this->setScaling($scaling);
			}

			$this->user->setTimeDelta((real)$data['time_delta']);

			$this->user->setLanguageID($this->user_language[$data['lang']][1]);

			$this->user->setNewPassword($data['new_password']);

			GB_SingletonObjectStorage::CommitAllCommitable();


			$this->common->closeTrans("commit");

			$_SESSION['time_delta'] = $this->user->getTimeDelta();
			$_SESSION['lang'] = $this->user_language[$data['lang']][1];
			$_SESSION['language'] = $data['lang'];

			if(!$this->updateForumInfo($this->user->getName(),
			$data['new_password'],
			$data['time_delta'],
			strtolower($this->user_language[$data['lang']][0])
			))
			{
				return false;
			}

			$_SESSION["dbId"] = $this->currentDbId;
			
			//получаем список сот игрока
			$userCombs = $this->getUserCombs();

			//Проходимся по каждой соте
			foreach ($userCombs as $key => $data)
			{
				$combId = $data['id_comb'];
				assert($combId > 0 );

				//Рестартим вид домашней соты
				CR_CombView::CreateFromCombID((int)$combId)->resetBuildings(1);

				//Рестартим вид центра
				CR_CombView::CreateFromCombID((int)$combId)->resetBuildings(2);

				//Рестартим вид нейтрала
				CR_CombView::CreateFromCombID((int)$combId)->resetBuildings(3);
			}

			$_SESSION["dbId"] = 0;
		}

		return true;
	}

	function showProfile($showError = false)
	{
		$tpl = new Smarty;
		$tpl->template_dir = "templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		assert($this->user instanceof DE_User);

		require_once ( "./language/".$_SESSION['shortname']."/index_profile.lang" );

		// блок переменных
		$tpl->assign("lang",$lang);

		if ( !@$lang )
		{
			echo "SystemError - Profile::noLangFile";
			return false;
		}

		$time_delta = array(
		'GMT - 12' => -12,
		'GMT - 11' => -11,
		'GMT - 10' => -10,
		'GMT - 9' => -9,
		'GMT - 8' => -8,
		'GMT - 7' => -7,
		'GMT - 6' => -6,
		'GMT - 5' => -5,
		'GMT - 4' => -4,
		'GMT - 3:30' => -3.5,
		'GMT - 3' => -3,
		'GMT - 2' => -2,
		'GMT - 1' => 1,
		'GMT '.$time_delta_phrases['GMT0'] => 0,
		'GMT + 1' => 1,
		'GMT + 2' => 2,
		'GMT + 3 '.$time_delta_phrases['GMT3'] => 3,
		'GMT + 3:30' => 3.5,
		'GMT + 4 '.$time_delta_phrases['GMT4'] => 4,
		'GMT + 4:30' => 4.5,
		'GMT + 5' => 5,
		'GMT + 5:30' => 5.5,
		'GMT + 6' => 6,
		'GMT + 6:30' => 6.5,
		'GMT + 7' => 7,
		'GMT + 8' => 8,
		'GMT + 9' => 9,
		'GMT + 9:30' => 9.5,
		'GMT + 10' => 10,
		'GMT + 11' => 11,
		'GMT + 12' => 12,
		);
		
		$scaling1 = '<!--';
		$scaling2 = '-->';

		if($this->profi)
		{
			$scaling1 = '';
			$scaling2 = '';
		}

		$tpl->assign('SCALING_1', $scaling1);
		$tpl->assign('SCALING_2', $scaling2);
		$reflink = 'http://'.$_SERVER['HTTP_HOST'].'/ds/reg_wizard.php?ur='.$_SESSION['uid'];
		$tpl->assign('REFLINK',$reflink);
		
		$scaling = $this->getScaling();

		$scalingOn = 'checked';
		if($scaling == 'f')
		{
			$scalingOn = '';
		}

		$tpl->assign('SCALING_ON', $scalingOn);

		$err_str = '';

		if ($showError)
		{
			foreach ($this->errorMsg as $k=>$v)
			{
				if(is_array($v))
				{
					foreach ($v as $k2=>$v2)
					{
						$err_str .= $v2.'<br>';
					}
				}
				else
				{
					if(array_key_exists($v,$errors))
					{
						$err_str .= $errors[$v].'<br>';
					}
					else
					{
						$err_str .= $v.'<br>';
					}
				}
			}
		}

		$tpl->assign('ERROR_MESSAGE', $err_str);

		$forum_link = '';
		if(strcmp($_SERVER['HTTP_HOST'],'ds.rambler.ru') === 0)
		{
			$forum_link = 'http://forum.destinysphere.ru/forum/profile.php?mode=editprofile';
		}
		elseif(strcmp($_SERVER['HTTP_HOST'],'dsgame.rambler.ru') === 0)
		{
			$forum_link = 'http://forum.ds-game.ru/ucp.php?i=profile&mode=profile_info';
		}
		elseif(strcmp($_SERVER['HTTP_HOST'],'alpha.destinysphere.ru') === 0)
		{
			$forum_link = 'http://alphaforum.destinysphere.ru/profile.php?mode=editprofile';
		}

		$tpl->assign('FORUM_PROFILE_LINK', $forum_link);
		$tpl->assign('LOGIN_NAME_CURRRENT', $this->user->getName());
		$tpl->assign('USER_MAIL_CURRRENT', $this->user->getEmail());
		$tpl->assign('timeZones', $time_delta);
		$tpl->assign('currentTimeZone', (real)$this->user->getTimeDelta(true));
		$tpl->assign('userLanguages', $this->user_language);
		$tpl->assign('currentUserLanguage', $this->user->getLanguageID());
		$tpl->display('index_profile.tpl');

		unset ( $lang );

		return true;
	}

	function getLangArray()
	{
		$sql = "
				SELECT
						id_language,
						language,
						shortname
				FROM
					ds_language
				";

		$res = $this->common->getResultArray ( "",$sql, "", "", "");
		assert(empty($this->common->errormsg));

		if ( sizeof ($res) > 0 )
		{
			foreach ($res as $k=>$v)
			{
				$result[$v['shortname']] = array($v['language'],$v['id_language']);
			}
			return $result;
		}
		else
		{
			return false;
		}
	}

	function updateForumInfo($username, $new_password, $time_delta, $lang)
	{
		if(!constant('FORUM_REGISTRATION_ENABLED'))
		{
			return true;
		}

		$commonForum = new DS_Common();

		if(!$commonForum->connect('forum'))
		{
			$this->errorMsg[] = 'CANT_CHANGE_FORUM_INFO';
			return false;
		}

		$sql = "UPDATE
								".constant('FORUM_PREFIX')."_users
							SET
								user_password = '".$new_password."',
								user_timezone = ".$time_delta.",
								user_lang = '".$lang."'
						WHERE
							username = '".$username."'";
		$res = $commonForum->executeQuery($sql);

		if($commonForum->errormsg)
		{
			$this->errorMsg[] = 'CANT_CHANGE_FORUM_INFO';
			return false;
		}

		return true;
	}

	function getScaling()
	{
		$sql = "SELECT scaling FROM ds_userprofitechdata WHERE id_user = ".$this->user->getId();

		$res = $this->common->getResultArray ( "",$sql, "", "", "");
		assert(empty($this->common->errormsg));

		if(count($res) == 1)
		{
			return $res[0]['scaling'];
		} else {

			return false;
		}
	}

	function setScaling($scaling)
	{
		assert(is_bool($scaling));

		if($scaling)
		{
			$scale = 't';
		} else {
			$scale = 'f';
		}

		$res = $this->getScaling();

		if($res !== false)
		{
			$sql = "UPDATE
						ds_userprofitechdata 
					SET	
						scaling = '".$scale."' 
					WHERE 
						id_user = ".$this->user->getId();
		} else {
			$sql = "INSERT INTO ds_userprofitechdata
						(id_user, scaling)
					VALUES
						(".$this->user->getId().", '".$scale."')";
		}

		$res = $this->common->getResultArray ( "",$sql, "", "", "");
		assert(empty($this->common->errormsg));
	}

	function getUserCombs()
	{		
		$combCommon = DS_Common::CreateForCurentDB($this->currentDbId);

		$sql = "SELECT
				  c.id_comb
				FROM ds_userinstance as ui
				INNER JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
 					WHERE
                    ui.id_user = ".$this->user->getId();

		$res = $combCommon->getResultArray ( "",$sql, "", "", "");
		assert(empty($combCommon->errormsg));

		return $res;
	}

}

$currentDbId = $_SESSION["dbId"];

$profile = new Profile();

$_SESSION["dbId"] = $currentDbId;

?>
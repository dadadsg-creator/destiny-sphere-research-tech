<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/05/22 08:38:27 $';

require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

final class GE_DisciplinaryRequest
{
	static private $requestType;

	static private $requestSend = false;

	static private $error;

	static private $uid;

	static private $DBID;

	static private $worldID;

	static private $data = array();

	static private $action = 'index';

	static private $DV_Mail = 'dvsupport@destinysphere.com';

	static private $messageLimit = 7;

	static private $attachments = array();

	static private $dataMask = array
	(
		'Mat' => array('User_Name', 'Game_World', 'Date', 'Text', 'Violation_Type', 'Email'),

		'MultiAccount' => array('User_Name', 'Game_World', 'Reason', 'Email'),

		'SittingRequest' => array('User_Name_For_Sitting', 'Sitter_User_name', 'Sitting_Comb', 'Sitting_World', 'Sitting_Start_Date', 'Sitting_End_Date', 'Read_Rules', 'Email'),

		'AccountSteal' => array('User_Name', 'User_Comb', 'Lost_List', 'User_Know_Password', 'Email'),
		
		'Same_IP' => array('User_List', 'Constant_Play', 'Email', 'Read_Rules_Same_Comp'),
		
		'Other' => array('Email', 'Additional_Info'),
	);

	static public function Exec($data)
	{
		self::$data = $data;

		self::_getParams(self::$data);

		if(self::$action == 'index')
		{
			self::_outTypeSelect();
		} else {
			self::_execFormOut();
		}
	}

	static private function _outTypeSelect()
	{
		assert(key_exists('uid', $_SESSION));

		unset($_SESSION['REQUSET_SEND']);

		$common = DS_Common::CreateForCurentDB(0);
		$common->setDbId(0);

		GE_SearchBase::SetCommon($common);

		$user = DE_User::CreateFromID((int)$_SESSION['uid']);

		$worldList = GE_LogicWorldsList::GetWorldsToEnter($user, 1);
		$userWorlds = array();
		foreach ($worldList as $k => $world)
		{
			$userWorlds[$world['dbId'].'_'.$world['id_world']] = $world['worldname'];
		}
		unset($worldList);

		include $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/disciplinary_request.lang";
		assert(is_array($lang));

		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		$tpl->assign('lang', $lang);
		$tpl->assign('REQUEST_TYPES', array_keys(self::$dataMask));
		$tpl->assign('USER_WORLDS', $userWorlds);

		$tpl->display('dv_request_select.tpl');
	}

	static public function _execFormOut()
	{
		$oldSessionDBID = $_SESSION['dbId'];

		$_SESSION['dbId'] = 0;

		if(isset($_SESSION['REQUSET_SEND']))
		{
			self::$requestSend = $_SESSION['REQUSET_SEND'];
		}

		//self::$requestSend = false;

		self::_getUserData();

		if(self::$action == 'send' && !self::$requestSend)
		{
			if(self::_checkData())
			{
				self::_sendRequest();
			}
		}

		self::_out();

		$_SESSION['dbId'] = $oldSessionDBID;
	}

	static private function _getParams($data)
	{
		assert(is_array($data));

		if(key_exists('Request_type', $data))
		{
			self::$requestType = $data['Request_type'];

			assert(key_exists(self::$requestType, self::$dataMask));

			assert(key_exists('User_game_world', $data));
			$arr = explode('_', $data['User_game_world']);
			assert(count($arr) == 2);

			self::$worldID = (int)$arr[1];
			assert(self::$worldID > 0);
			self::$DBID = (int)$arr[0];
			assert(self::$DBID >= 0);

			self::$action = 'form';
		}

		if(key_exists('Send_request', $data))
		{
			self::$action = 'send';
		}

		/*
		if(key_exists('From_Index_Start', $data))
		{
		self::$requestSend = false;

		self::$action = 'index';
		}
		*/
	}

	static private function _getUserData()
	{
		assert(key_exists('uid', $_SESSION));

		self::$uid = (int)$_SESSION['uid'];

		assert(self::$uid > 0);

		$common = DS_Common::CreateForCurentDB(self::$DBID);

		$sql = "
			SELECT
				r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber as comb_coords,
				(
					SELECT
						worldname
					FROM
						ds_world
					WHERE
						id_world = ".self::$worldID."
				) as worldname,
				cn.combname
			FROM
				ds_userinstance AS ui
				INNER JOIN ds_comb AS c ON (c.id_userinstance = ui.id_userinstance)
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
			WHERE
				ui.id_user = ".self::$uid."
				AND
				ui.id_world = ".self::$worldID."
				AND
				r.ringname != 'RING_COLONIZE'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		assert(count($res) == 1);

		self::$data['Comb'] = $res[0]['combname'].'('.$res[0]['comb_coords'].')';

		self::$data['World'] = $res[0]['worldname'];

		$user = DE_User::CreateFromID(self::$uid);

		self::$data['Username'] = $user->getName();

		self::$data['Email'] = $user->getEmail();

		GE_SearchBase::SetCommon($common);

		self::$data['User_Game_Worlds'] = GE_LogicWorldsList::GetWorldsToEnter($user, 1);

		self::$data['All_Game_Worlds'] = GE_SearchStart::GetWorldCollection(constant('DEFAULT_LANGUAGE'));
	}

	static private function _checkData()
	{
		foreach (self::$dataMask[self::$requestType] as $k => $value)
		{
			if(!key_exists($value, self::$data))
			{
				self::$error = 'EMPTY_VALUE';
				return false;
			}
		}

		foreach(self::$data as $key => $value)
		{
			if(in_array($key, self::$dataMask[self::$requestType]))
			{
				if(empty($value))
				{
					self::$error = 'EMPTY_VALUE';
					return false;
				}
			}
		}

		return true;
	}

	static private function _sendRequest()
	{
		if(self::$requestSend)
		{
			return;
		}

		if(!self::_checkLimit())
		{
			self::$error = "REQUEST_LIMIT";
			return;
		}

		$mailBody = date("d-m-Y H:i")." = request from DV form\n**********************************\n";

		// подключение файла с локализацией
		include $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/disciplinary_request.lang";
		assert(is_array($lang));

		$skipKeys = array('cnt', 'b', 'SIDIX', 'hotlog', 'User_game_world', 'Send_request', 'All_Game_Worlds', 'User_Game_Worlds', 'ck');
	
		foreach (self::$data as $key => $value)
		{
			if(in_array($key, $skipKeys))
			{
				continue;
			}

			if(key_exists($key, $lang['MAIL_LANG']))
			{
				$keyText = $lang['MAIL_LANG'][$key];
			} else {
				$keyText = $key;
			}

			if($key == 'Request_type')
			{
				$value = $lang['REQUEST_TYPE'][$value];
			}

			$mailBody .= $keyText.": ".$value."\n\n";
		}
		unset($lang);

		self::_logRequest();

		$attachments = self::_getAttachments();
		
		if($attachments === false)
		{
			self::$error = "FILE_SIZE_LIMIT";
			return;
		}
		
		// @mail(self::$DV_Mail, 'REQUEST FROM DS SITE', $mailBody, "Content-type: text/plain; charset=windows-1251\r\nfrom: ".self::$data['Email']."\r\n");
		self::_send_mail(self::$DV_Mail, self::$data['Email'], 'REQUEST FROM DS SITE', $mailBody, $attachments);		

		self::$requestSend = true;

		$_SESSION['REQUSET_SEND'] = 1;
	}


	static private function _logRequest()
	{
		$data = pg_escape_string(print_r(self::$data, true));

		$sql = "
			INSERT INTO ds_dv_request
				(id_user, date, request_type, data)
			VALUES
				(".self::$uid.", now(), '".self::$requestType."', E'$data')
			";
		$common = DS_Common::CreateForCurentDB(0);
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}

	static private function _checkLimit()
	{
		$sql = "
			SELECT
				count(*) as count
			FROM
				ds_dv_request
			WHERE
				id_user = ".self::$uid."
				AND
				date > now() - '7 DAY'::interval
			";
		$common = DS_Common::CreateForCurentDB(0);
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if($res[0]['count'] > self::$messageLimit)
		{
			return false;
		}

		return true;
	}

	static private function _getAttachments()
	{
		$accepted_ext = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
		$fileSizeLimit = 0;
		$imageSizeLimit = 500 * 1024; // 500 K file size limit

		$attachements = array();

		if(!key_exists('uploaded_images', $_FILES))
		{
			return $attachements;
		}

		if(!is_array($_FILES['uploaded_images']))
		{
			return $attachements;
		}
		
		if(count($_FILES['uploaded_images']['name']) == 0)
		{
			return $attachements;
		}
		
		$totalFileSize = 0;
		
		foreach($_FILES['uploaded_images']['tmp_name'] as $k => $filename)
		{
			if(isset($_FILES['uploaded_images']['error'][$k]) && isset($_FILES['uploaded_images']['size'][$k]))
			{
				if(!$_FILES['uploaded_images']['error'][$k] && $_FILES['uploaded_images']['size'][$k])
				{
					$totalFileSize += $_FILES['uploaded_images']['size'][$k];
					
					if($totalFileSize > $imageSizeLimit)
					{
						return false;
					}
					
					$ext = substr($_FILES['uploaded_images']['name'][$k], strrpos($_FILES['uploaded_images']['name'][$k], ".") + 1);

					if(!is_uploaded_file($filename))
					{
						continue;
					}

					if(!in_array($ext, $accepted_ext))
					{
						continue;
					}

					$size = getimagesize($filename);

					if($size[0] == 0 || $size[1] == 0)
					{
						continue;
					}

					$attachements[] = array(
							'file' => $filename, 
							'content_type' => $size['mime'],
							'show_filename' => 'screen'.$k.'.'.$ext
						);
				} else {
					continue;
				}
			} else {
				continue;
			}
		}

		return $attachements;
	}

	static private function _send_mail($emailaddress, $fromaddress, $emailsubject, $body, $attachments=false)
	{
		$eol="\r\n";
		$mime_boundary=md5(time());

		# Common Headers
		$headers .= 'From: '.self::$data['Username'].'<'.$fromaddress.'>'.$eol;
		$headers .= 'Reply-To: '.self::$data['Username'].'<'.$fromaddress.'>'.$eol;
		$headers .= 'Return-Path: '.self::$data['Username'].'<'.$fromaddress.'>'.$eol;    // these two to set reply address
		$headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
		$headers .= "X-Mailer: PHP v".phpversion().$eol;          // These two to help avoid spam-filters

		# Boundry for marking the split & Multitype Headers
		$headers .= 'MIME-Version: 1.0'.$eol;
		$headers .= "Content-Type: multipart/related; boundary=\"".$mime_boundary."\"".$eol;

		$msg = "";

		# Text Version
		$msg .= "--".$mime_boundary.$eol;
		$msg .= "Content-Type: text/plain; charset=windows-1251".$eol;
		$msg .= "Content-Transfer-Encoding: 8bit".$eol;
		$msg .= $body.$eol.$eol;
		
		if ($attachments !== false)
		{
			foreach ($attachments as $k => $attachement)
			{
				if (is_file($attachement["file"]))
				{
					# File for Attachment
					$file_name = substr($attachement["file"], (strrpos($attachement["file"], "/")+1));

					$handle=fopen($attachement["file"], 'rb');
					$f_contents=fread($handle, filesize($attachement["file"]));
					$f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
					fclose($handle);

					# Attachment
					$msg .= "--".$mime_boundary.$eol;
					$msg .= "Content-Type: ".$attachments[$i]["content_type"]."; name=\"".$attachement['show_filename']."\"".$eol;
					$msg .= "Content-Transfer-Encoding: base64".$eol;
					$msg .= "Content-Disposition: attachment; filename=\"".$attachement['show_filename']."\"".$eol.$eol; // !! This line needs TWO end of lines !! IMPORTANT !!
					$msg .= $f_contents.$eol.$eol;
				}
			}
		}

		# Finished
		$msg .= "--".$mime_boundary."--".$eol.$eol;  // finish with two eol's for better security. see Injection.

		# SEND THE EMAIL
		ini_set(sendmail_from, $fromaddress);  // the INI lines are to force the From Address to be used !
		mail($emailaddress, $emailsubject, $msg, $headers);
		ini_restore(sendmail_from);
	}

	static private function _out()
	{
		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		// подключение файла с локализацией
		include $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/disciplinary_request.lang";
		assert(is_array($lang));
		$tpl->assign('lang', $lang);
		unset($lang);

		$tpl->assign('REQUEST_TYPE', self::$requestType);
		$tpl->assign('REQUEST_SEND', self::$requestSend);
		$tpl->assign('MSG', self::$error);
		$tpl->assign('DATA', self::$data);

		$tpl->display('desciplinary_request.tpl');
	}
}

?>
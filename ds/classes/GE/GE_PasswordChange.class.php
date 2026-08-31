<?php $CVS_Version = '$Revision$ ; $Date$';

class GE_PasswordChange
{
	static private $msg;
	
	static private $oldPass;
	
	static private $newPass;
	
	static private $newPassConf;
	
	static private $change = false;
	
	static private $common;
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
				
		if(self::$change)
		{
			self::_changePassword();
		}
		
		self::_out();
	}
	
	static private function _changePassword()
	{
		GE_SearchStart::SetCommon(self::$common);
		
		assert(!GE_SearchStart::CheckUserPasswordChange((int)$_SESSION['uid']));
		
		self::$common->startTrans();
		
		GE_SearchStart::ChangeUserPassword((int)$_SESSION['uid'], md5(self::$newPass));
		
		self::$common->closeTrans('COMMIT');
		
		$_SESSION['s_pass'] = md5(self::$newPass);
		
		self::$msg = 'PASSWORD_CHANGE_OK';
	}
	
	static private function _out()
	{
		$tpl = new Smarty();
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = constant('SMARTY_CACHE_PATH');
		$tpl->compile_check = true;
		$tpl->debugging = false;
		
		$tpl->assign('MSG', self::$msg);
		
		if(self::$msg == 'PASSWORD_CHANGE_OK')
		{
			SU_ClientKey::setKey(false);
			$tpl->assign('URL_INDEX_START', 'index_start.php?ck='.$_SESSION['client_key'].'&'.session_name().'='.session_id());
		}
		
		include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/R/password_change.lang";
		assert(is_array($lang));
		$tpl->assign('lang', $lang);
		unset($lang_err);
		
		$tpl->display('password_change.tpl');
	}
	
	static private function _getParams(array $data)
	{
		if(!key_exists('action', $data))
		{
			return;
		}
		
		assert(key_exists('old_pass', $data));
		assert(key_exists('new_pass', $data));
		assert(key_exists('new_pass_conf', $data));
		assert(key_exists('uid', $_SESSION));
		assert(key_exists('s_login', $_SESSION));
		
		self::$oldPass = $data['old_pass'];
		self::$newPass = $data['new_pass'];
		self::$newPassConf = $data['new_pass_conf'];
		
		self::_validateParams();
	}
	
	static private function _validateParams()
	{
		self::$common = DS_Common::CreateForCurentDB(0);
		
		$uid = VD_User::Validate($_SESSION['s_login'], self::$oldPass, self::$common);
		if(!$uid)
		{
			self::$msg = VD_User::FetchErrorCode();
			return;
		}
		
		// всякое бывает ...
		assert($uid == $_SESSION['uid']);
		
		if(!VD_Password::Validate(self::$newPass))
		{
			self::$msg = 'NEW_PASS_'.VD_Password::FetchErrorCode();
			return;
		}
		
		if(self::$newPass == self::$oldPass)
		{
			self::$msg = 'NEW_PASSWORD_SAME_WITH_OLD';
			return;
		}
		
		if(self::$newPass != self::$newPassConf)
		{
			self::$msg = 'PASSWORD_CONFIRMATION_ERROR';
			return;
		}
		
		self::$change = true;
	}
}

?>
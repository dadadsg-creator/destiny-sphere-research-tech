<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2009/01/27 10:33:02 $';

class UC_ExecLogin
{	
	static private $common;
	
	static public function Exec($data)
	{
		assert(key_exists('login', $data));
		assert(key_exists('password', $data));
		
		self::$common = DS_Common::CreateForCurentDB();
		
		if(key_exists('cab_uid', $_SESSION))
		{
			return self::_confirmLogin($data);
		} else {
			return self::_execLogin($data);
		}
	}
	
	static public function execLogin($data)
	{
		//assert(key_exists('login', $data));
		assert(key_exists('uid', $_SESSION));
		
		$common = DS_Common::CreateForCurentDB();
		
		// Убираем проверку логина и пароля при входе в кабинет
		/*$uid = VD_User::Validate($data['login'], $data['password'], $common);

		if(false == $uid)
		{
			if(constant('ENABLE_RAMBLER_ID'))
			{
				$rsid = GE_Login::GetRSIDByLoginAndPassword($data['login'], $data['password']);
				if($rsid === false)
				{
					return '5_WRONG_USER_NAME_OR_PASSWORD';
				} else {
					$_GET['rsid'] = $rsid;
					$ramblerLogin = new GE_RamblerLogin();
					$user = $ramblerLogin->execLogin();

					if(!(is_object($user) && $user instanceof DE_User))
					{
						return '5_WRONG_USER_NAME_OR_PASSWORD';
					}
					
					$uid = $user->getID();
				}
			} else {
				return VD_User::FetchErrorCode();
			}
		}
		*/
		/*if($_SESSION['uid'] != $uid)
		{
			return 'SESSION_UID_MISSMATCH';
		}*/
		
		//$_SESSION['cab_uid'] = $uid;
		$_SESSION['cab_uid'] = $_SESSION['uid'];
		
		self::_setStartSessionParams();
		
		return true;
	}
	
	static public function confirmLogin($data)
	{
		self::checkStartSessionParams();
		
		assert(key_exists('login', $data));
		assert(key_exists('password', $data));
		
		$common = DS_Common::CreateForCurentDB();
		
		$uid = VD_User::Validate($data['login'], $data['password'], $common);
		
		if(false == $uid)
		{
			return VD_User::FetchErrorCode();
		}
		
		if($_SESSION['uid'] != $uid)
		{
			return 'SESSION_UID_MISSMATCH';
		}
		
		if($_SESSION['cab_uid'] != $uid)
		{
			return 'CAB_LOGIN_UID_MISSMATCH';
		}
		
		return true;
	}
	
	/**
	 * Для дополнительной защиты, при первом входе в кабинет кладем в сессию IP, SID и UserAgent
	 *
	 */
	static private function _setStartSessionParams()
	{	
		/**
		 * @desc кладем в сессию IP адрес
		 */
		$ipArr = explode(".", @$_SERVER['REMOTE_ADDR']);
		$ipPart = $ipArr[0].".".$ipArr[1];
		
		$xForwrdedPart = '';
		
		if(key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown')
		{
			$xForwardedArr = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach($xForwardedArr as $k => $ip)
			{
				$ipArr = explode('.', $ip);
				$xForwrdedPart .= $ipArr[0].".".$ipArr[1];
			}
		} else {
			$xForwardedArr = '';
		}
		
		$ipKey = $ipPart.".".$xForwrdedPart;
		
		$_SESSION['cab_start_ip'] = $ipKey;
		
		/**
		 * @desc кладем в сессию ID сессии
		 */
		$_SESSION['cab_start_sid'] = session_id();
		
		/**
		 * @desc кладем в сессию User Agent
		 */
		$_SESSION['cab_start_ua'] = md5(@$_SERVER['HTTP_USER_AGENT']);
	}
	
	static private function _validateSessionParams()
	{		
		if (!key_exists('cab_uid', $_SESSION)) {
			echo 'YOUR SESSION IS CLOSED. PLEASE, RELOGIN.';
			exit;
		}
		
		assert(key_exists('cab_uid', $_SESSION));
		assert(key_exists('cab_start_ip', $_SESSION));
		assert(key_exists('cab_start_sid', $_SESSION));
		assert(key_exists('cab_start_ua', $_SESSION));
	}
	
	/**
	 * Проверка данных совпадения текущих данных с данными при первом заходе
	 *
	 */
	static public function checkStartSessionParams()
	{
		self::_validateSessionParams();
		
		$ipArr = explode(".", @$_SERVER['REMOTE_ADDR']);
		$ipPart = $ipArr[0].".".$ipArr[1];
		
		$xForwrdedPart = '';
		
		if(key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown')
		{
			$xForwardedArr = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach($xForwardedArr as $k => $ip)
			{
				$ipArr = explode('.', $ip);
				$xForwrdedPart .= $ipArr[0].".".$ipArr[1];
			}
		} else {
			$xForwardedArr = '';
		}
		
		$ipKey = $ipPart.".".$xForwrdedPart;
		
		assert($ipKey == $_SESSION['cab_start_ip']);
		assert(session_id() == $_SESSION['cab_start_sid']);
		assert(md5(@$_SERVER['HTTP_USER_AGENT']) == $_SESSION['cab_start_ua']);
	}
	
	public function execLogout(DE_User $user, array $data)
	{
		self::_validateSessionParams();
		
		unset($_SESSION['cab_uid']);
		unset($_SESSION['cab_start_ip']);
		unset($_SESSION['cab_start_sid']);
		unset($_SESSION['cab_start_ua']);
		
		SU_ClientKey::setKey(false);

		Header("Location: index_start.php?ck=".$_SESSION['client_key']."&".session_name()."=".session_id());
		exit();
	}
}

?>
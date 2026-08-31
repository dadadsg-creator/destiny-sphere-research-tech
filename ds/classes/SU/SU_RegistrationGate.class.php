<?php

/**
 * Гейт регистрация для итальянской версии
 *
 */
class SU_RegistrationGate 
{
	/**
	 * Enter description here...
	 *
	 * @var array
	 */
	static private $httpPostData;
	
	static private $username;
	
	static private $password;
	
	static private $email;
	
	static private $partner;
	
	static private $common;
	
	static public function Registrate()
	{
		self::$httpPostData = $_REQUEST;
		
		self::_logRequest();
		
		self::$common = DS_Common::CreateForCurentDB(0);
		
		self::_checkParams();
		
		self::_registrateUser();
		
		self::_outResult();
	}
	
	static private function _logRequest()
	{
		$logData = self::$httpPostData;
		if(key_exists('password', $logData))
		{
			$logData['password'] = 'md5 = '.md5(trim($logData['password']));
		}
		
		SU_DebugTools::LogAll($logData, 'RegistrationGate.log', 0);
	}
	
	static private function _outResult($code = 0)
	{
		$str = 'error_status='.$code;
		echo $str;
		SU_DebugTools::LogAll($str, 'RegistrationGate.log', 0);
		exit();
	}
	
	static private function _checkParams()
	{
		if(!is_array(self::$httpPostData))
		{
			self::_outResult(1);
		}
		
		self::_validateIP();
		
		self::_validateSign();
		
		self::_validateUserName();
		
		self::_validatePassword();
		
		self::_validateEmail();
		
		self::_validatePartner();
	}
	
	static private function _validateIP()
	{
		global $gate_allowed_ip;
		
		$ip = $_SERVER['REMOTE_ADDR'];
		
		if(!in_array($ip, $gate_allowed_ip))
		{
			self::_outResult(7);
		}
	}
	
	static private function _validateSign()
	{
		if(!key_exists('sign', self::$httpPostData))
		{
			self::_outResult(2);
		}
		
		if(self::$httpPostData['sign'] != self::_createSign(self::$httpPostData))
		{
			self::_outResult(21);
		}
	}
	
	static private function _validateUserName()
	{
		if(!key_exists('username', self::$httpPostData))
		{
			self::_outResult(3);
		}
		
		self::$username = trim(self::$httpPostData['username']);
		
		if(!VD_UserName::Validate(self::$username))
		{
			self::_outResult(31);
		}
		
		if(VD_UserName::CheckIfExists(self::$username, self::$common) !== false)
		{
			self::_outResult(32);
		}
	}
	
	static private function _validatePassword()
	{
		if(!key_exists('password', self::$httpPostData))
		{
			self::_outResult(4);
		}
		
		self::$password = trim(self::$httpPostData['password']);
		
		if(!VD_Password::Validate(self::$password))
		{
			self::_outResult(41);
		}
	}
	
	static private function _validateEmail()
	{
		if(!key_exists('email', self::$httpPostData))
		{
			self::_outResult(5);
		}
		
		self::$email = trim(self::$httpPostData['email']);
		
		if(!VD_Email::Validate(self::$email, self::$common))
		{
			self::_outResult(51);
		}
	}
	
	static private function _validatePartner()
	{
		if(!key_exists('partner', self::$httpPostData))
		{
			self::_outResult(8);
		}
		
		self::$partner = intval(self::$httpPostData['partner']);
		
		if(self::$partner <= 0 || self::$partner > 2)
		{
			self::_outResult(81);
		}
	}
	
	static private function _registrateUser()
	{
		self::$common->startTrans();
		
		$userID = (int)DS_Base::getNextSeqVal(self::$common, 'ds_user_id_user_seq');
		
		$sql = "
			INSERT INTO DS_User
				(
					id_user,
					ID_Language,
					UserName,
					Password,
					Email,
					registrationdate,
					lastrefresh,
					vacationmode,
					accountactive,
					admin,
					time_delta,
					activate_mail
				)
			VALUES
				(
					".$userID.",
					".constant('DEFAULT_LANGUAGE').",
					'".self::$username."',
					'".md5(self::$password)."',
					'".self::$email."',
					'now()',
					'now()',
					false,
					true,
					false,
					".constant("DEFAULT_TIMEDELTA").",
					1
				)
			";
		self::$common->executeQuery($sql);
		if(!empty(self::$common->errormsg))
		{
			self::$common->errormsg = '';
			self::$common->closeTrans('ROLLBACK');
			self::_outResult(6);
		}
		
		$sql = "
			INSERT INTO ds_userprovidedbypartner
				(id_user, id_partner)
			VALUES
				($userID, ".self::$partner.")
			";
		self::$common->executeQuery($sql);
		if(!empty(self::$common->errormsg))
		{
			self::$common->errormsg = '';
			self::$common->closeTrans('ROLLBACK');
			self::_outResult(6);
		}
			
		self::$common->closeTrans('COMMIT');
	}
	
	static public function _createSign(array $data)
	{
		ksort($data);
		reset($data);
		
		$signString = '';
		
		foreach ($data as $k => $v)
		{
			if($k != 'sign')
			{
				$signString .= $k.'='.$v.';';
			}
		}
		
		return  md5($signString);
	}
}

?>
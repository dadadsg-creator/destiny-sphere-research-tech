<?php

/**
 * –егистраци€ игрока в турнире
 * 
 * со стороны игры вызываетс€ из игрового кабинета 
 * 		TN_RegistrationRequest::Request()
 * 
 * со стороны турнира 
 * 		TN_UserRegistration::Registrate()
 */
class TN_UserRegistration
{
	static private $error = '';
	
	static private $userName;
	
	static private $email;
	
	static private $password;
	
	static private $version;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $allowedVersions = array('CL', 'UN', 'TS');
	
	static public function Registrate(array $data)
	{
		self::$common = TN_TurnirController::GetCommon();
		
		if(!self::_getParams($data))
		{
			self::_outResult(true);
		}
		
		if(!self::_addUser())
		{
			self::_outResult(true);
		}
		
		self::_outResult();
	}
	
	static private function _addUser()
	{
		$sql = "
			INSERT INTO ds_user 
				(id_language, username, password, email, admin, accountactive, time_delta, activate_mail, registrationdate)
			VALUES
				(
				".constant('DEFAULT_LANGUAGE').", 
				E'".pg_escape_string(self::_getUserName())."', 
				E'".pg_escape_string(self::$password)."', 
				E'".pg_escape_string(self::$email)."',
				false,
				true,
				".constant('DEFAULT_TIMEDELTA').", 
				1,
				now()
				)
			";
		self::$common->executeQuery($sql);
		if(!empty(self::$common->errormsg))
		{
			self::$error = 'ERROR_IN_USER_INSERT';
			return false;
		}
		
		return true;
	}
	
	static private function _getUserName()
	{
		return self::$userName.'__'.self::$version;
	}
	
	static private function _outResult($isError = false)
	{
		assert(is_bool($isError));
		
		if($isError)
		{
			echo '0|'.self::$error;
			exit;
		} else {
			echo '1|OK';
		}
	}
	
	static private function _getParams(array $data)
	{
		if(!key_exists('username', $data))
		{
			self::$error = 'NO_USERNAME';
			return false;
		}
		
		if(!key_exists('email', $data))
		{
			self::$error = 'NO_EMAIL';
			return false;
		}
		
		if(!key_exists('password', $data))
		{
			self::$error = 'NO_PASSWORD';
			return false;
		}
		
		if(!key_exists('version', $data))
		{
			self::$error = 'NO_VERSION';
			return false;
		}
		
		self::$userName = $data['username'];
		if(!VD_UserName::Validate(self::$userName))
		{
			self::$error = 'WRONG_USERNAME';
			return false;
		}
		
		self::$email = $data['email'];
		if(!VD_Email::Validate(self::$email))
		{
			self::$error = 'WRONG_EMAIL';
			return false;
		}
		
		self::$password = $data['password'];
		if(!preg_match('/^[a-f0-9]{32}$/', self::$password))
		{
			self::$error = 'WRONG_PASSWORD';
			return false;
		}
		
		self::$version = $data['version'];
		if(!in_array(self::$version, self::$allowedVersions))
		{
			self::$error = 'WRONG_VERSION';
			return false;
		}
		
		if(VD_UserName::CheckIfExists(self::_getUserName(), self::$common))
		{
			self::$error = 'USER_REGISTERED';
			return false;
		}
		
		if(!VD_Email::Validate(self::$email, self::$common))
		{
			self::$error = 'EMAIL_REGISTERED|'.TN_Search::GetUsernameByEmail(self::$email);
			return false;
		}
		
		return true;
	}
}

?>
<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2008/07/08 15:38:35 $';

require_once('classes/Validator.class.php');
require_once('include/GameDown.inc.php');

class UC_ExecUserProfile
{
	static private $common = null;
	
	static private $user;
	
	static private $data = array();
	
	static private $user_language;
	
	static private $profi = false;
	
	static private $uid = false;

	public function Exec(DE_User $user, $data)
	{
		self::$user = $user;
		
		//Проверям не закрыта ли игра
		if (GameDown())
		{
			Header("Status: 302 Moved Temporaly");
			Header("Location: index_down.html");
			exit();
		}
		
		assert(isset($_SESSION));
		assert(key_exists('profi_game', $_SESSION));
		
		self::$profi = $_SESSION['profi_game'];
		
		self::$data = $data;

		$dbId = $_SESSION["dbId"];
		$_SESSION["dbId"] = 0;
		self::$common = DS_Common::CreateForCurentDB(0);

		self::$user_language = self::getLangArray();
		assert(sizeof(self::$user_language) > 0);

		if (isset($data['modified']) && $data['modified'] == 'true')
		{
			//Проверяем входные параметры
			if (self::checkLocalParams($data))
			{
				self::changeProfile($data);
			}
		}
		
		if (isset($data['new_name']) && strlen($data['new_name']) > 0)
		{
			self::changeName($data['new_name']);
		}
		
		if(isset($_FILES['avatar']) && strlen($_FILES['avatar']['name']) > 0)
		{
			if (VD_Avatar::Validate($_FILES['avatar']))
			{
				switch ($data['action_avatar'])
				{
					case 'change':
						//Смотрим достаточно ли денег у игрока
						if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_CHANGE_AVATAR'))
						{
							self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
							/*self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';
				
							//Получаем имя мира, в котором есть достаточно денег для данной операции
							$worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, 'PS_CHANGE_AVATAR');
							
							//Если такой мир есть
							if($worldName)
							{
								self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
							}*/
							return false;
						}
		
						if (!UC_AddAvatar::ChangeUserAvatar(self::$user, $_FILES['avatar']))
						{
							self::$data['MSG'] = 'ERROR_ADD_AVATAR';
							return false;	
						}
						
						//Снимаем деньги
						UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_CHANGE_NAME');
		
						
						break;
						
					case 'add':
						
						//Смотрим достаточно ли денег у игрока
						if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_ADD_AVATAR'))
						{
							self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
							/*self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';
				
							//Получаем имя мира, в котором есть достаточно денег для данной операции
							$worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, 'PS_ADD_AVATAR');
							
							//Если такой мир есть
							if($worldName)
							{
								self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
							}*/
							break;
						}
						
						if (!UC_AddAvatar::AddUserAvatar(self::$user, $_FILES['avatar']))
						{
							self::$data['MSG'] = 'ERROR_ADD_AVATAR';	
							break;
						}
						
						//Снимаем деньги
						UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_CHANGE_NAME');
		
						break;
						
					default:
						self::$data['MSG'] = 'ERROR_ADD_AVATAR';	
						break;
				}
			} else {
				self::$data['MSG'] = VD_UserName::FetchErrorCode();	
			}
			
			self::$common->closeTrans("commit");
			
		}
		
		self::$data['timeZones'] = array(
		'GMT - 12' => -13,
		'GMT - 11' => -12,
		'GMT - 10' => -11,
		'GMT - 9' => -10,
		'GMT - 8' => -9,
		'GMT - 7' => -8,
		'GMT - 6' => -7,
		'GMT - 5' => -6,
		'GMT - 4' => -5,
		'GMT - 3:30' => -4.5,
		'GMT - 3' => -4,
		'GMT - 2' => -3,
		'GMT - 1' => -2,
		'GMT '.$time_delta_phrases['GMT0'] => -1,
		'GMT + 1' => 0,
		'GMT + 2' => 1,
		'GMT + 3 '.$time_delta_phrases['GMT3'] => 2,
		'GMT + 3:30' => 2.5,
		'GMT + 4 ' => 3,
		'GMT + 4:30' => 3.5,
		'GMT + 5' => 4,
		'GMT + 5:30' => 4.5,
		'GMT + 6' => 5,
		'GMT + 6:30' => 5.5,
		'GMT + 7' => 6,
		'GMT + 8' => 7,
		'GMT + 9' => 8,
		'GMT + 9:30' => 8.5,
		'GMT + 10' => 9,
		'GMT + 11' => 10,
		'GMT + 12' => 11,
		);
		
		self::$data['LOGIN_NAME_CURRRENT'] = $user->getName();
		self::$data['USER_MAIL_CURRRENT'] = $user->getEmail();
		self::$data['currentTimeZone'] = (real)$user->getTimeDelta(true);
		self::$data['userLanguages'] = self::$user_language;
		self::$data['currentUserLanguage'] = $user->getLanguageID();
		
		$scaling1 = '<!--';
		$scaling2 = '-->';

		if(self::$profi)
		{
			$scaling1 = '';
			$scaling2 = '';
		}
	
		self::$data['SCALING_1'] = $scaling1;
		self::$data['SCALING_2'] = $scaling2;
		
		$scaling = self::getScaling();

		$scalingOn = 'checked';
		if($scaling == 'f')
		{
			$scalingOn = '';
		}
		
		self::$data['SCALING_ON'] = $scalingOn;

		//Смотрим, есть ли у пользователя аватарка
		if (DS_Avatar::GetUrlUserAvatar(self::$user->getId()))
		{
			self::$data['USER_HAS_AVATAR'] = 'YES';
			
			//Получаем стоимость смены аватара
			$actionType = DE_MoneyActionType::CreateFromName('PS_CHANGE_AVATAR');
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);

			self::$data['PRICE_CHANGE_AVATAR'] = $MoneyActionType->getAmount();
			self::$data['CREDIT_CHANGE_AVATAR'] = $MoneyActionType->getCreditName();
		} else {
			self::$data['USER_HAS_AVATAR'] = 'NO';
			
			//Получаем стоимость добавления аватара
			$actionType = DE_MoneyActionType::CreateFromName('PS_ADD_AVATAR');
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);

			self::$data['PRICE_ADD_AVATAR'] = $MoneyActionType->getAmount();
			self::$data['CREDIT_ADD_AVATAR'] = $MoneyActionType->getCreditName();
		}

		$_SESSION["dbId"] = $dbId;
		self::$common->closeConnection();
		
		//Получаем стоимость смены ника
		$actionType = DE_MoneyActionType::CreateFromName('PS_CHANGE_NAME');
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);

		self::$data['PRICE_CHANGE_NAME'] = $MoneyActionType->getAmount();
		self::$data['CREDIT_CHANGE_NAME'] = $MoneyActionType->getCreditName();
		
		
		UC_OutUserCabinet::Out('UserProfile', self::$data);
	}

	static function changeProfile(array $data)
	{
		//Меняем параметры профиля
		if(isset($data['new_password']) && strlen($data['new_password']) > 0)
		{
			if(strcmp(md5($data['old_password']),self::$user->getPassword()) == 0)
			{
				$valid = new ValidatePassword($data['new_password'],
				$data['confirm_password'],
				self::$user->getName());
				if ( !$valid->isValid())
				{
					self::$data['MSG_LANG'] = $valid->getError();
					self::$data['MSG_LANG'] = self::$data['MSG_LANG'][1];
					return;
				}

				$data['new_password'] = md5($data['new_password']);
			}
			else
			{
				self::$data['MSG'] = 'WRONG_OLD_PASSWORD';
				return;
			}
		}
		else
		{
			$data['new_password'] = self::$user->getPassword();
		}

		self::$user->setTimeDelta((real)$data['time_delta']);

		self::$user->setLanguageID(self::$user_language[$data['lang']][1]);

		self::$user->setNewPassword($data['new_password']);

		GB_SingletonObjectStorage::CommitAllCommitable();


		assert(key_exists('profi_game', $_SESSION));
		self::$profi = $_SESSION['profi_game'];

		if(self::$profi)
		{
			$scaling = false;


			if(isset($data['scaling']) && $data['scaling'] == 'on')
			{
				$scaling = true;
			}
			self::setScaling($scaling);
		}

		self::$common->closeTrans("commit");
	}
	
	static function changeName($newName)
	{
		//Смотрим достаточно ли денег у игрока
		if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_CHANGE_NAME'))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			/*self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

			//Получаем имя мира, в котором есть достаточно денег для данной операции
			$worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, 'PS_CHANGE_NAME');
			
			//Если такой мир есть
			if($worldName)
			{
				self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
			}*/
			return false;
		}
		
		if (!VD_UserName::Validate($newName, true, self::$common))
		{
			self::$data['MSG'] = VD_UserName::FetchErrorCode();	
			return false;
		}
	
		self::$user->setName($newName);
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_CHANGE_NAME');

		GB_SingletonObjectStorage::CommitAllCommitable();
		self::$common->closeTrans("commit");

	}
	
	static function getLangArray()
	{
		$sql = "
				SELECT
						id_language,
						language,
						shortname
				FROM
					ds_language
				";

		$res = self::$common->getResultArray ( "",$sql, "", "", "");
		assert(empty(self::$common->errormsg));

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
	
	static function checkLocalParams($data)
	{
		if (!isset($data['lang']))
		{
			self::$data['MSG'] = "Undefined Lang!";
			return false;
		}

		if(!key_exists($data['lang'], self::$user_language))
		{
			self::$data['MSG'] = "Undefined Lang!";
			return false;
		}

		if(!isset($data['time_delta']))
		{
			if (!isset($_SESSION['time_delta']))
			{
				self::$data['MSG'] = "Undefined time delta!";
				return false;
			}
		}

		if (!is_numeric($data['time_delta']))
		{
			self::$data['MSG'] = "Wrong time delta!1";
			return false;
		}

		if (((real)$data['time_delta'] < -13) || ((float)$data['time_delta'] > 11))
		{
			self::$data['MSG'] = "Wrong time delta!2";
			return false;
		}
		return true;
	}
	
	static function setScaling($scaling)
	{
		assert(is_bool($scaling));
		
		if($scaling)
		{
			$scale = 't';
		} else {
			$scale = 'f';
		}
		
		$res = self::getScaling();
		
		if($res !== false)
		{
			$sql = "UPDATE 
						ds_userprofitechdata 
					SET	
						scaling = '".$scale."' 
					WHERE 
						id_user = ".self::$user->getId();
		} else {
			$sql = "INSERT INTO ds_userprofitechdata 
						(id_user, scaling)
					VALUES
						(".self::$user->getId().", '".$scale."')";
		}
		
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static function getScaling()
	{
		$sql = "SELECT scaling FROM ds_userprofitechdata WHERE id_user = ".self::$user->getId();
		$res = self::$common->getResultArray("", $sql, "", "", "");

		assert(empty(self::$common->errormsg));
		
		if(count($res) == 1)
		{
			return $res[0]['scaling'];
		} else {
			return false;
		}
	}
}

?>
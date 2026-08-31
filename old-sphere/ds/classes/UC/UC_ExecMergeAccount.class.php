<?php $CVS_Version = '$Revision: 1.11 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecMergeAccount extends UC_PaymentServiceBase
{
	static private $passwords = array();

	static private $logins = array();

	static private $toUser;

	public function Exec()
	{
		self::_getParams();

		$_SESSION['dbId'] = 0;

		//получаем цену
		self::_GetPrice();

		switch(self::$action)
		{
			case 'show_second_menu':
				if (!self::_ShowSecondMenu())
				{
					self::_ShowIndex();
				}
				break;

			case 'merge_account':
				if (!self::_MergeAccount())
				{
					self::_ShowIndex();
				}
				break;

			default:
				self::_ShowIndex();
				break;

			case 'successful':
				self::_ShowSuccessful();
				break;
		}

		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}

	static private function _ShowIndex()
	{
		self::$data['MODE'] = 'MERGE_ACCOUNTS_INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';

		if (!empty(self::$logins))
		{
			self::$data['LOGINS'] = self::$logins;
		}
	}

	static private function _ShowSecondMenu()
	{
		self::$data['MODE'] = 'MERGE_ACCOUNTS_SECOND_MENU';
		self::$data['EXEC_ACTION'] = 'merge_account';

		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}

		$basicUserWorlds = self::_GetUserWorlds();

		foreach ($basicUserWorlds as $k => $w)
		{
			$worlds[$w['dbname']."_".$w['id_world']] = self::$user->getId();
		}

		//Флаг введен, чтобы игрок не мог обьединится с самим собой
		//и с игроками, которые не имеют сот ни в одном из миров
		$nothingToMergeFlag = true;

		//Проходимся по всем логинам
		foreach (self::$logins as $key => $userName)
		{
			if (empty($userName)) {
				continue;
			}

			//Проверяем существование игрока
			$userId = self::_GetUserIdByName($userName);

			if (empty($userId)) {
				self::$data['MSG'] = 'ERROR_USER_NOT_EXIST';
				self::$data['ERROR'] = 'WRONG_DATA';
				return false;
			}

			self::$data['MERGE_USERS'][] = $userName;


			//Если не был введен пароль
			if (empty(self::$passwords[$key])) {
				self::$data['MSG'] = 'ENTER_ALL_PASSWORDS';
				self::$data['ERROR'] = 'WRONG_DATA';
				return false;
			}

			//Проверяем правильность введенного пароля
			$pass = self::_GetUserPasswordByName($userName);

			//Проверяем правильность пароля
			if(strcmp(md5(self::$passwords[$key]), $pass) != 0)
			{
				self::$data['MSG'] = 'WRONG_PASSWORD';
				self::$data['ERROR'] = 'WRONG_DATA';
				return false;
			}

			//Получаем все миры игрока
			$userWorlds = self::_GetThiseUserWorlds((int)$userId);

			//Проходимся по всем мирам игрока
			foreach ($userWorlds as $kw => $curW)
			{

				if ($userId != self::$user->getId()) {
					$nothingToMergeFlag = false;
				}


				$world = $curW['dbname']."_".$curW['id_world'];
				//Если миры повторяется
				if (key_exists($world, $worlds))
				{
					self::$data['MSG'] = 'DOUBLE_WORLD';
					self::$data['ERROR'] = 'TWO_PLAYERS_IN_SOME_WORLD';
					return false;
				}

				$worlds[$world] = (int)$userId;
			}
		}

		if ($nothingToMergeFlag == true)
		{
			self::$data['MSG'] = 'NOTHING_TO_MERGE';
			return false;
		}

		self::$data['USER_NAME'] = self::$user->getName();

		return true;
	}


	static private function _MergeAccount()
	{
		$_SESSION['dbId'] = 0;

		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}

		DS_Common::SetConnectWithTransaction(true);

		$basicUserWorlds = self::_GetUserWorlds();

		foreach ($basicUserWorlds as $k => $w)
		{
			$worlds[$w['dbname']."_".$w['id_world']] = self::$user->getId();
		}

		//Для лога
		$usersArr = array(0 => array(
		'id_user' => self::$user->getId(),
		'is_initiator' => 't',
		'is_payer' => 't'
		),
		);

		//Флаг введен, чтобы игрок не мог обьединится с самим собой
		//и с игроками, которые не имеют сот ни в одном из миров
		$nothingToMergeFlag = true;

		//Проходимся по всем логинам
		foreach (self::$logins as $key => $userName)
		{
			if (empty($userName)) {
				continue;
			}
			//Проверяем существование игрока
			$userId = self::_GetUserIdByName($userName);

			if (empty($userId)) {
				self::$data['MSG'] = 'ERROR_USER_NOT_EXIST';
				//self::$data['ERROR'] = 'Игрок с ником <b>"'.$userName.'" не существует!</b>';
				self::$data['ERROR'] = 'WRONG_DATA';
				return false;
			}

			//Получаем все миры игрока
			$userWorlds = self::_GetThiseUserWorlds((int)$userId);

			//Проходимся по всем мирам игрока
			foreach ($userWorlds as $kw => $curW)
			{

				if ($userId != self::$user->getId()) {
					$nothingToMergeFlag = false;
				}

				$world = $curW['dbname']."_".$curW['id_world']."_".$curW['world_identifier'];

				//Если миры повторяется
				//if (in_array($world, $worlds))
				if (key_exists($world, $worlds))
				{
					self::$data['MSG'] = 'DOUBLE_WORLD';
					return false;
				}

				$worlds[$world] = (int)$userId;
				//$worlds[] = $world;
			}

			//Для лога
			$usersArr[] = array(
			'id_user' => $userId,
			'is_initiator' => 'f',
			'is_payer' => 'f'
			);
		}


		$common = self::getCommon();

		if ($nothingToMergeFlag == true)
		{
			self::$data['MSG'] = 'NOTHING_TO_MERGE';
			return false;
		}

		//Проходимся по каждому миру
		foreach ($worlds as $key => $userId)
		{
			if ((int)self::$user->getId() == $userId) {
				continue;
			}

			assert($userId > 0);

			$tempArr = explode('_', $key);
			$dbName = $tempArr[0];
			$worldId = (int)$tempArr[1];
			$worldIdentifier = $tempArr[2];

			assert($worldId > 0);
			assert(!empty($dbName));
			assert(!empty($worldIdentifier));
			
			$dbId = $common->getDBIDByName($dbName);
			$common->setDbId($dbId);
			$_SESSION['dbId'] = $dbId;
			$common->setDbId($dbId);			

			$currentUser = DE_User::CreateFromID($userId);

			//Переводим все деньги из мира в кабинет
			$worldFromCollection = DE_WorldFromCollection::CreateFromID($worldIdentifier);
			$member = new MU_EntityOperationMember($currentUser, $worldFromCollection);
			$account = MU_SearchOperations::GetAccountData($member);
			if(!empty($account))
			{
				if(!$account['IS_ACTIVE'])
				{
					self::$data['MSG'] = 'ERROR';
					return false;
				}
				
				$memberFrom = new MU_EntityOperationMember($currentUser, $worldFromCollection);
				$memberTo = new MU_EntityOperationMember($currentUser, DE_WorldFromCollection::CreateFromID('UC'));
				//Если есть SC
				if ($account['SC'] > 0)
				{
					$summ = new MU_EntityMoneySumm((int)$account['SC'], 'SC');
					MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
				}
				//Если есть GC
				if ($account['GC'] > 0)
				{
					$summ = new MU_EntityMoneySumm((int)$account['GC'], 'GC');
					MU_ExecTransferInterworld::Exec($memberFrom, $memberTo, $summ);
				}
			
			}
			
			$_SESSION['dbId'] = $dbId;
			$common->setDbId($dbId);

			$userInstanceId = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID($userId, $worldId);

			$sql = "UPDATE
		      								ds_userinstance
										SET
		   									id_user = ".self::$user->getId()."
		   								WHERE 
		   									id_userinstance = ".$userInstanceId."
		   								
		   						";

			$res = $common->executeQuery( $sql );

			if (!empty($common->errormsg) || !$res)
			{
				self::$data['MSG'] = 'ERROR';
				return false;
			}

			//Смотрим, есть ли у игрока аккаунт в этом мире
			$sql = "SELECT * FROM ds_money_account WHERE id_user = ".self::$user->getId()." AND world_identifier = '$worldIdentifier'";
			$res = $common->getResultArray('', $sql, '', '', '');
			if (!empty($common->errormsg))
			{
				self::$data['MSG'] = 'ERROR';
				return false;
			}

			//Если у игрока нет ака
			if (count($res) == 0)
			{
				//Создаем игроку денежный аккаунт
				$sql = "INSERT INTO ds_money_account (id_user, world_identifier) VALUES (".self::$user->getId().", '$worldIdentifier')";
				$res = $common->executeQuery($sql);

				if (!empty($common->errormsg) || !$res)
				{
					self::$data['MSG'] = 'ERROR';
					return false;
				}
			}

			$_SESSION['dbId'] = 0;
			$common->setDbId(0);

			$sql = "UPDATE
											ds_userinstancecollection 
										SET 
											id_user = ".self::$user->getId()." 
										WHERE 
											dbname = '".$dbName."'
											AND id_userinstance = '".$userInstanceId."'
								";
			
			$res = $common->executeQuery($sql);

			if (!empty($common->errormsg) || !$res)
			{
				self::$data['MSG'] = 'ERROR';
				return false;
			}
		}

		$_SESSION['dbId'] = 0;

		$mergedUsers = "<b>".self::$user->getName()."</b>";

		//Снова проходимся по всем логинам
		foreach (self::$logins as $key => $userName)
		{
			if (empty($userName)) {
				continue;
			}

			if ($userName == self::$user->getName()) {
				continue;
			}

			//Переводим все деньги из кабинета игроку
			$world = DE_WorldFromCollection::CreateFromID('UC');
			$memberFrom = new MU_EntityOperationMember(DE_User::CreateFromName($userName), $world);
			$memberTo = new MU_EntityOperationMember(self::$user, $world);
			$account = MU_SearchOperations::GetAccountData($memberFrom);

			//Если есть SC
			if ($account['SC'] > 0 )
			{
				//переводим все SC
				$summ = new MU_EntityMoneySumm($account['SC'], 'SC');
				$actionType = DE_MoneyActionType::CreateFromName('PVP_TRANSFER');
				$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
				MU_ControlExecOperations::ExecOperation($operation);
			}

			//Если есть GC
			if ($account['GC'] > 0 )
			{
				//переводим все GC
				$summ = new MU_EntityMoneySumm($account['GC'], 'GC');
				$actionType = DE_MoneyActionType::CreateFromName('PVP_TRANSFER');
				$operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
				MU_ControlExecOperations::ExecOperation($operation);
			}

			$mergedUsers .= ", <b>$userName</b>";
		}

		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_MERGE_ACCOUNTS', self::$data['PRICE'], self::$data['CREDIT'], "MrgeUsers: $mergedUsers", $usersArr))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}

		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_MERGE_ACCOUNTS');

		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&mergeUsers=".$mergedUsers);
		return true;
	}
	
	private static function _ShowSuccessful ()
	{				
		self::$data['MERGE_USERS'] = $_REQUEST['mergeUsers'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}

	static private function _GetUserIdByName($userName)
	{
		$common = DS_Common::CreateForCurentDB(0);

		$sql = "SELECT id_user
					FROM
					    ds_user
					WHERE
						username = '$userName'
					";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));

		return $res[0]['id_user'];
	}

	static private function _GetUserPasswordByName($userName)
	{
		$common = DS_Common::CreateForCurentDB(0);

		$sql = "SELECT password
					FROM
					    ds_user
					WHERE
						username = '$userName'
					";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));

		return $res[0]['password'];
	}

	private function _GetThiseUserWorlds($userId)
	{
		$common = DS_Common::CreateForCurentDB(0);

		GE_SearchBase::SetCommon($common);
		$worldsToEnter = GE_SearchStart::GetWorldsWhereUserIsRegistered($userId);

		foreach ($worldsToEnter as $k => $world)
		{
			$worldsToEnter[$k]['dbId'] = $common->getDbIdByName($world['dbname']);
		}

		return $worldsToEnter;
	}


	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}

		if(key_exists('login', self::$data))
		{
			self::$logins = self::$data['login'];
		}

		if(key_exists('password', self::$data))
		{
			self::$passwords = self::$data['password'];
		}
	}

}

?>
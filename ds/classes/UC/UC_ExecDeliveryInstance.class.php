<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecDeliveryInstance extends UC_PaymentServiceBase
{
	static private $password;
	
	static private $toUser;
	
	static private $toUserId;
		
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
				
			case 'deliveryInstance':
				if (!self::_DeliveryInstance()) 
				{
					self::_ShowIndex();
				}
				break;	
				
			case 'successful':
					self::_ShowSuccessful();
				break;	
				
			default:
				self::_ShowIndex();
				break;
		}
		
		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
	
	static private function _ShowIndex()
	{
		self::$data['MODE'] = 'DELYVERY_INSTANCE_INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';
		
		if (!empty(self::$toUser)) 
		{
			self::$data['TO_USER'] = self::$toUser;
		} else 
		{
			self::$data['TO_USER'] = '';
		}
	}
	
	static private function _ShowSecondMenu()
	{
		$_SESSION['dbId'] = 0;
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		self::$data['TO_USER'] = self::$toUser;
		
		//Если пароль пустой
		if(empty(self::$password))
		{
			self::$data['MSG'] = 'EMPTY_PASSWORD';
			return false;
		}
		//Проверяем правильность пароля
		if(strcmp(md5(self::$password), self::$user->getPassword()) != 0)
		{
			self::$data['MSG'] = 'WRONG_PASSWORD';
			return false;
		}
		
		//Если ник пустой
		if(empty(self::$toUser))
		{
			self::$data['MSG'] = 'EMPTY_TO_USER';
			return false;
		}
		
		//Проверяем, есть ли пользователь с введенным ником
		$toUserId = self::_GetUserIdByName(self::$toUser);
		if (empty($toUserId))
		{
			self::$data['MSG'] = 'TO_USER_NOT_EXIST';
			return false;
		}
		
		//Проверяем, нет ли у игрока, которому хотим передать ак соты в тех же мирах, как и у нас
		//Получаем все миры первого игрока
		$worldsUserFrom = self::_GetUserWorlds();

		//Получаем все миры второго игрока
		$worldsUserTo = self::_GetThiseUserWorlds((int)$toUserId);
		
		//Проходимся по всем мирам первого игрока
		foreach ($worldsUserFrom as $key => $val) 
		{
			//Проходимся по всем мирам второго игрока
			foreach ($worldsUserTo as $key2 => $val2)
			{
				//Если второй игрок зареган в том же мире, то это ошибка
				if ($val['worldname'] == $val2['worldname']) 
				{
					self::$data['MSG'] = 'USER_HAS_COMB_IN_YOUR_WORLD';
					return false;
				}
			}
		}
		
		self::$data['TO_USER_ID'] = $toUserId;
		self::$data['MODE'] = 'DELYVERY_INSTANCE_SECOND_MENU';
		self::$data['EXEC_ACTION'] = 'deliveryInstance';
		
		return true;
	}
	
	
	static private function _DeliveryInstance()
	{
		$_SESSION['dbId'] = 0;
		
		$desc = 'Worsds: ';
		
		//Смотрим достаточно ли денег у игрока
		if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_DELYVERY_INSTANCE'))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

			//Получаем имя мира, в котором есть достаточно денег для данной операции
			$worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, 'PS_DELYVERY_INSTANCE');
			
			//Если такой мир есть
			if($worldName)
			{
				self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
			}
			return false;
		}

		assert(!empty(self::$toUser));
		assert(self::$toUserId > 0);
		
		$common = self::getCommon();
		
		//Получаем все миры первого игрока
		$worldsUserFrom = self::_GetUserWorlds(self::$user->getId());
					
		//Проходимся по каждому миру
		foreach ($worldsUserFrom as $key => $world)
		{
			$desc .= $world['worldname']." ";
			
			$common->setDbId($world['dbId']);

			$_SESSION['dbId'] = $world['dbId'];
		
			$userInstanceId = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getID(), (int)$world['id_world']);

			$sql = "UPDATE
		      								ds_userinstance
										SET
		   									id_user = ".self::$toUserId."
		   								WHERE 
		   									id_userinstance = $userInstanceId
		   								
		   						";

				
			$res = $common->executeQuery( $sql );

			if (!empty($common->errormsg) || !$res)
			{
				self::$data['MSG'] = 'ERROR';
				return false;
			}
			
			//Смотрим, есть ли у игрока аккаунт в этом мире
			$sql = "SELECT * FROM ds_money_account WHERE id_user = ".self::$toUserId." AND world_identifier = '".$world['world_identifier']."'";
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
				$sql = "INSERT INTO ds_money_account (id_user, world_identifier) VALUES (".self::$toUserId.", '".$world['world_identifier']."')";
				$res = $common->executeQuery($sql);

				if (!empty($common->errormsg) || !$res)
				{
					self::$data['MSG'] = 'ERROR';
					return false;
				}
			}

			$common->setDbId(0);

			$sql = "UPDATE
											ds_userinstancecollection 
										SET 
											id_user = ".self::$toUserId."
										WHERE 
											dbname = '".$world['dbname']."'
											AND 
											id_userinstance = $userInstanceId
											
								";
			$res = $common->executeQuery($sql);

			if (!empty($common->errormsg) || !$res)
			{				
				self::$data['MSG'] = 'ERROR';
				return false;
			}					
			
		}
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_DELYVERY_INSTANCE');
		
		//Переводим все деньги из миров в кабинет
		//Получаем список всех миров
		GE_SearchBase::SetCommon($common);
		GE_SearchStart::GetWorldCollection(3);
		
		$worlds = GE_SearchStart::GetWorldCollection(3);
		
		//Проходимся по всем мирам
		foreach ($worlds as $key => $worldData)
		{
			$dbId = $common->getDbIdByName($worldData['dbname']);
			$_SESSION['dbId'] = $dbId;
		
			$worldFromCollection = DE_WorldFromCollection::CreateFromID($worldData['world_identifier']);
			$member = new MU_EntityOperationMember(self::$user, $worldFromCollection);
			$account = MU_SearchOperations::GetAccountData($member);
			
			if($account['IS_ACTIVE'])
			{
				$memberFrom = new MU_EntityOperationMember(self::$user, $worldFromCollection);
				$memberTo = new MU_EntityOperationMember(self::$user, DE_WorldFromCollection::CreateFromID('UC'));

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
		}
		
		$_SESSION['dbId'] = 0;
		
		//Переводим все деньги на счет втророго игрока
		$world = DE_WorldFromCollection::CreateFromID('UC');
		$memberFrom = new MU_EntityOperationMember(self::$user, $world);
		$memberTo = new MU_EntityOperationMember(DE_User::CreateFromID((int)self::$toUserId), $world);
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
		
		
		
		$usersArr = array(0 => array(
									'id_user' => self::$user->getId(),
									'is_initiator' => 't',
									'is_payer' => 't'
									),
									
							1 => array(
									'id_user' => self::$toUserId,
									'is_initiator' => 'f',
									'is_payer' => 'f'
									)
						);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_DELYVERY_INSTANCE', self::$data['PRICE'], self::$data['CREDIT'], $desc, $usersArr))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}

		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&userName=".self::$toUser);
		return true;
	}
	
	private static function _ShowSuccessful ()
	{				
		self::$data['USER_NAME'] = $_REQUEST['userName'];
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
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('password', self::$data))
		{
			self::$password = self::$data['password'];
		}
		
		if(key_exists('to_user', self::$data))
		{
			self::$toUser = self::$data['to_user'];
		}
		
		if(key_exists('to_user_id', self::$data))
		{
			self::$toUserId = self::$data['to_user_id'];
		}
	}
	
	protected static function _GetThiseUserWorlds ($userId)
	{				
		$common = self::getCommon();
		
		GE_SearchBase::SetCommon($common);
		$worldsToEnter = GE_SearchStart::GetWorldsWhereUserIsRegistered((int)$userId);

		foreach ($worldsToEnter as $k => $world)
		{
			$worldsToEnter[$k]['dbId'] = $common->getDbIdByName($world['dbname']);
		}

		return $worldsToEnter;
	}//END OF METHOD
}

?>
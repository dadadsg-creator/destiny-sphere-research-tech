<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2010/07/07 09:00:42 $';


class UC_ExecExchangeCoords extends UC_PaymentServiceBase
{
	static private $ring;
	
	static private $userInstanceFrom;
	
	static private $combIdFrom;
	
	static private $combIdTo;
	
	static private $combComplexFrom;
	
	static private $combComplexTo;
	
	static private $combNumberFrom;
	
	static private $combNumberTo;
	
	static private $userNameWith;
	
	static private $userIdWith;
	
	static private $whoPay = 'not_chossen';
	
	public function Exec()
	{
		self::_GetParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();

		switch(self::$action)
		{
			case 'index':
				self::_ShowIndex();
				break;
				
			case 'show_second_menu':
				self::_ShowSecondMenu();
				break;
				
			case 'cansel_exchange':
				self::_CanselExchange();
				break;
		
			case 'exchange_coords':
				if (!self::_ExchangeCoords()) 
				{
					self::_ShowSecondMenu();
				}
				
				break;	
				
			case 'confirm_exchange_coords':
				if (!self::_ConfirmExchangeCoords()) 
				{
					self::_ShowSecondMenu();
				}
				
				break;					
				
			case 'successful':
					self::_ShowSuccessfulExchange();
				break;	
				
			default:
				break;
		}
		
		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
	
	static private function _ShowIndex()
	{
		self::$data['MODE'] = 'INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';
		
		if (self::$data['PS'] == 'PS_EXCHANGE_COORDS') {
			self::$ring = 1;	
		} else {
			self::$ring = 4;
		}
		self::$data['RING'] = self::$ring;
		
		//Получаем список миров игрока
		$userworlds = self::_GetUserWorlds();
		
		//Если мир у игрока один
		if(count($userworlds) == 1)
		{
			self::$worldId = (int)$userworlds[0]['id_world'];
			self::$dbId = (int)$userworlds[0]['dbId'];
			self::$worldName = $userworlds[0]['worldname'];

			self::_ShowSecondMenu();
		} 
		else{
			self::$data['USER_WORLDS'] = $userworlds;
		}
	}
	
	static private function _ShowSecondMenu()
	{
		$_SESSION['dbId'] = 0;				
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);

		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
		self::$data['MODE'] = 'EXCHANGE_COORDS_SECOND_MENU';
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['RING'] = self::$ring;
		
		$_SESSION['dbId'] = self::$dbId;
		
		//Смотрим, заказал ли кто-нибудь переезд с этим игроком
		$orderedTransferWith = self::_GetOrderedTransferWith(self::$user->getId());
		
		//Смотрим, заказал ли этот игрок с кем-нибудь переезд
		$orderedTransfer = self::_GetOrderedTransfer(self::$user->getId());	
		
		//Если переезд заказали уже оба
		if (count($orderedTransfer) > 0 && count($orderedTransferWith) > 0) 
		{
			self::$data['TRANSFER_COMPLITE'] = 'true';
			//Получаем инфу о сотах
			$transferCombFrom = self::_GetCombData($orderedTransfer[0]['transfer_to']);
			$transferCombTo = self::_GetCombData($orderedTransfer[0]['transfer_from']);

			//Получаем ник того, с кем заказали переезд
			$userIdWith = DE_User::CreateFromID((int)$orderedTransfer[0]['exchange_with']);
			$userNameWith = $userIdWith->getName();
			
			self::$data['TRANSFER_COMB_FROM'] = $transferCombFrom;
			self::$data['TRANSFER_COMB_TO'] = $transferCombTo;
			
			self::$data['USER_NAME_WITH'] = $userNameWith;
			
			return;
		}
		
		//Если этот игрок уже заказал переезд
		if (count($orderedTransfer) > 0) 
		{
			$_SESSION['dbId'] = self::$dbId;
			
			//Смотрим, не бесплатный ли это переезд по кольцу
			if (empty($orderedTransfer[0]['transfer_from'])) {
				//Проверяем, чтобы это был не обмен сотами
				assert(empty($orderedTransfer[0]['exchange_with']));
				self::$data['ORDERED_FREE_RING_TRANSFER'] = 'true';	
				return;
			}
				
			$_SESSION['dbId'] = 0;
			
			$isServisePaid = self::_GetServicePaidInfo(self::$user->getId());
			//Если игрок заказал переезд, но не в рамках данной услуги
			if (empty($isServisePaid)) {
				self::$data['MSG'] = 'CANT_ORDERED_EXCHANGE';
				self::$data['ORDERED_FREE_RING_TRANSFER'] = 'true';	
				return;
			}
			
			$_SESSION['dbId'] = self::$dbId;
			
			self::$data['ORDERED_TRANSFER'] = 'true';	
			
			//Получаем инфу о сотах
			$transferCombFrom = self::_GetCombData($orderedTransfer[0]['transfer_to']);
			$transferCombTo = self::_GetCombData($orderedTransfer[0]['transfer_from']);

			//Получаем ник того, с кем заказали переезд
			$userIdWith = DE_User::CreateFromID((int)$orderedTransfer[0]['exchange_with']);
			$userNameWith = $userIdWith->getName();
			
			$_SESSION['dbId'] = 0;
			
			//Смотрим, кто платит
			assert($isServisePaid[0]['is_paid'] == 't' || $isServisePaid[0]['is_paid'] == 'f');
			
			self::$data['TRANSFER_COMB_FROM'] = $transferCombFrom;
			self::$data['TRANSFER_COMB_TO'] = $transferCombTo;
			
			self::$data['USER_NAME_WITH'] = $userNameWith;
			self::$data['USER_ID_WITH'] = (int)$orderedTransfer[0]['exchange_with'];

			return;
		}
		
		//Если с этим игроком кто-то уже заказал переезд
		if (count($orderedTransferWith) > 0) 
		{		
			$_SESSION['dbId'] = self::$dbId;
			
			self::$data['ORDERED_TRANSFER_WITH'] = 'true';	
							
			//Получаем инфу о сотах
			$transferCombFrom = self::_GetCombData($orderedTransferWith[0]['transfer_to']);
			$transferCombTo = self::_GetCombData($orderedTransferWith[0]['transfer_from']);

			//Получаем ник того, с кем заказали переезд
			$userIdWith = DE_User::CreateFromID((int)$orderedTransferWith[0]['id_user']);
			$userNameWith = $userIdWith->getName();
			
			$_SESSION['dbId'] = 0;

			//Смотрим, кто должен платить за эту услегу
			$isServisePaid = self::_GetServicePaidInfo((int)$orderedTransferWith[0]['id_user']);
			assert($isServisePaid[0]['is_paid'] == 't' || $isServisePaid[0]['is_paid'] == 'f');
			
			if ($isServisePaid[0]['is_paid'] == 'f')
			{ 
				self::$data['WHO_PAY'] = 1;
			} else {
				self::$data['WHO_PAY'] = 2;
			}

			self::$data['TRANSFER_COMB_FROM'] = $transferCombFrom;
			self::$data['TRANSFER_COMB_TO'] = $transferCombTo;
			
			self::$data['COMB_ID_TO'] = $orderedTransferWith[0]['transfer_from'];
			self::$data['COMB_ID_FROM'] = $orderedTransferWith[0]['transfer_to'];
			
			self::$data['USER_ID_WITH'] = (int)$orderedTransferWith[0]['id_user'];
			self::$data['USER_NAME_WITH'] = $userNameWith;	
			self::$data['USER_INSTANSE_FROM'] = $orderedTransferWith[0]['id_userinstance'];	
			
			return;
		}
		
		$_SESSION['dbId'] = self::$dbId;
		
		//Получаем список соты или колоний в мире
		$usersCombs = self::_GetCombIdByUID(self::$user->getId());

		foreach ($usersCombs as $key => $comb)
		{
			//Получаем данные соты
			$combs[] =  DS_Comb::GetByID($comb['id_comb']);
		}

		//Если у нас есть в данном мире сота или колония
		if (!empty($combs)) {
			//Запоминаем номер кольца
			self::$data['RING'] = $combs[0]['ringnumber'];
		} else {
			self::$data['RING'] = self::$ring;
		}
		
		//Если у нас уже было выбрано
		self::$data['WHO_PAY'] = self::$whoPay;
		self::$data['COMB_COMPLEX_TO'] = self::$combComplexTo;
		self::$data['COMB_NUMBER_TO'] = self::$combNumberTo;
		self::$data['USER_NAME_WITH'] = self::$userNameWith;
		
		self::$data['COMBS'] = $combs;	
	}
	
	static private function _ExchangeCoords()
	{
		//Если не было выбрано кто платит
		if (self::$whoPay != '1' && self::$whoPay != '2')
		{
			self::$data['MSG'] = 'EMPTY_WHO_PAY';
			return false;
		}
		
		//Если платит данный игрок
		if (self::$whoPay == 1)
		{
			//Смотрим достаточно ли денег у игрока
			if (!self::_CheckEnoughMoney())
			{
				return false;
			}
		}

		//Если поле с ником было оставлено пустым
		if (empty(self::$userNameWith))
		{
			self::$data['MSG'] = 'EMPTY_USER_NAME';
			return false;
		}
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));

		//Проверяем, есть ли пользователь с введенным ником
		self::$userIdWith = (int)self::_GetUserIdByName(self::$userNameWith);

		if (empty(self::$userIdWith) || self::$userIdWith <= 0)
		{
			self::$data['MSG'] = 'TO_USER_NOT_EXIST';
			return false;
		}
		
		//Проверяем, чтобы игрок не мог заказать переезд сам с собой
		if (self::$userIdWith == self::$user->getId()) {
			self::$data['MSG'] = 'YOU_CANT_EXCHENGE_WITH_YOURSELF';
			return false;
		}
		
		if(!self::_checkCombForExchange(self::$userIdWith))
		{
			return false;
		}
		
		//Записываем игрока на переезд
		if (!self::_SetTransferInfo(self::$userIdWith)) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Если платит данный игрок, то снимаем деньги с него сейчас
		if (self::$whoPay == 1) 
		{
			//Снимаем деньги
			UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, self::$data['PS'], 1, $WI);	

			$is_payer = 't';
			$is_secondPayer = 'f';
			$isPaid = 't';
			
			$_SESSION['IS_PAID'] = 'YES';
		} else {
			$is_payer = 'f';
			$is_secondPayer = 't';
			$isPaid = 'f';
			
			$_SESSION['IS_PAID'] = 'NO';
		}
		
		//Логиреум
		$usersArr = array(0 => array(
									'id_user' => self::$user->getId(),
									'is_initiator' => 't',
									'is_payer' => $is_payer
									),
									
							1 => array(
									'id_user' => self::$userIdWith,
									'is_initiator' => 'f',
									'is_payer' => $is_secondPayer
									)
						);
		
		$desc = 'World: '.self::$worldName.', CombIdFrom:'.self::$combIdFrom.', CombIdTo: '.self::$combIdTo;
						
		//Логиреум
		if (!self::_Log($isPaid, 't', 'f', 'f', self::$data['PS'], self::$data['PRICE'], self::$data['CREDIT'], $desc, $usersArr))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&userNameWith=".self::$userNameWith."&transferAction=order");
		return true;
	}	
	
	private static function _ShowSuccessfulExchange()
	{
		self::$data['USER_NAME_WITH'] = $_REQUEST['userNameWith'];
		self::$data['IS_PAID'] = $_SESSION['IS_PAID'];
		self::$data['TRANSFER_ACTION'] = $_REQUEST['transferAction'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _ConfirmExchangeCoords()
	{
		//Если не было выбрано кто платит
		if (self::$whoPay != '1' && self::$whoPay != '2')
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Если платит данный игрок
		if (self::$whoPay == 1) 
		{
			//Смотрим достаточно ли денег у игрока
			if (!self::_CheckEnoughMoney())
			{
				return false;
			}
		}
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));

		/*if(!self::_checkCombForExchange($toUserId))
		{
			return false;
		}*/
		
		//Записываем игрока на переезд
		if (!self::_SetTransferInfo(self::$userIdWith)) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Если платит данный игрок, то снимаем с него деньги
		if (self::$whoPay == 1) 
		{
			//Снимаем деньги
			UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, self::$data['PS'], 1, $WI);	

			$_SESSION['IS_PAID'] = 'YES';
			$isPaid = true;	
		} else {
			$isPaid = false;	
			$_SESSION['IS_PAID'] = 'NO';
		}
		
		if (!self::_setTransferIsComplete($isPaid))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&userNameWith=".self::$userNameWith."&transferAction=confrirm");
		return true;
	}
	
	static private function _CanselExchange()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		assert(self::$userIdWith > 0);
		
		$_SESSION['dbId'] = self::$dbId;

		//Смотрим, заказал ли кто-нибудь переезд с этим игроком
		$orderedTransferWith = self::_GetOrderedTransferWith(self::$user->getId());
		
		//Смотрим, заказал ли этот игрок с кем-нибудь переезд
		$orderedTransfer = self::_GetOrderedTransfer(self::$user->getId());	
		
		//Если игрок ни с кем не заказывал переезд
		if (count($orderedTransfer)== 0 && count($orderedTransferWith)== 0) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Если игрок заказал переезд
		if (count($orderedTransfer) > 0) 
		{
			//Пытаемся отменить пеерзд
			if (!self::_CanselTransfer(self::$user->getId())) {
				self::$data['MSG'] = 'ERROR';
				return false;
			}
			
			$_SESSION['dbId'] = 0;
			$serviseInfo = self::_GetServicePaidInfo(self::$user->getId());					
		}
		
		//Если с игроком заказали переезд
		if (count($orderedTransferWith) > 0) 
		{
			//Пытаемся отменить пеерзд
			if (!self::_CanselTransfer(self::$userIdWith)) {
				self::$data['MSG'] = 'ERROR';
				return false;				
			}
			
			$_SESSION['dbId'] = 0;
			$serviseInfo = self::_GetServicePaidInfo(self::$userIdWith);
		}

		assert($serviseInfo[0]['is_paid'] == 't' || $serviseInfo[0]['is_paid'] == 'f');

		//Смотрим, была ли оплачена данная услуга
		if ($serviseInfo[0]['is_paid'] == 't')
		{
			//Смотрим, сколько заплатили
			if ($serviseInfo[0]['gc_price'] != 0) 
			{
				$operationCost = (int)$serviseInfo[0]['gc_price'];
				$operationCredits = 'GC';
			} else {
				$operationCost = (int)$serviseInfo[0]['sc_price'];
				$operationCredits = 'SC';
			}
			
			if (self::$data['PS'] == 'PS_EXCHANGE_COORDS') {
				$returnAction = 'PS_RETURN_MONEY_EXCHANGE_COORDS';
			} else {
				$returnAction = 'PS_RETURN_MONEY_EXCHANGE_COORDS_MELS';
			}
			
			//Смотрим, кто из игроков платил за эту услугу
			if ($serviseInfo[0]['id_user'] == self::$user->getId()) 
			{
				//Возвращаем денюжку текущему игроку
				UC_PaymentServiceMoneyOperation::ReturnMoney(self::$user, $returnAction, $operationCost, $operationCredits);
			} else {
				//Возвращаем денюжку другому игроку
				assert(self::$userIdWith == $serviseInfo[0]['id_user']);
				UC_PaymentServiceMoneyOperation::ReturnMoney(DE_User::CreateFromID(self::$userIdWith), $returnAction, $operationCost, $operationCredits);
			}
			
		}
		
		//Записываем в лог, что услуга была отменена
		//Если игрок заказал переезд
		if (count($orderedTransfer) > 0) 
		{
			//Записываем в лог, что услуга была отменена
			self::_setTransferIsCanseled(self::$user->getId());
		} else {
			//Записываем в лог, что услуга была отменена
			self::_setTransferIsCanseled(self::$userIdWith);
		}
		
		$_SESSION['IS_PAID'] = 'NO';
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&userNameWith=".self::$userNameWith."&transferAction=cancel");
		return true;
	}
	
	static private function _CanselTransfer($userId)
	{
		$common = self::getCommon();
		
		$sql = "
			DELETE
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = $userId
				AND
				id_world = ".self::$worldId."
			";
		
		$common->executeQuery($sql);
		
		if(!empty($common->errormsg))
		{
			return false;
		}
		return true;
	}
	
	static private function _GetCombIdByUID($ToUserId)
	{
		$common = self::getCommon();
		
		if (self::$ring == 4) {
			$where = ' r.ringnumber = 4 ';
		} else {
			$where = ' NOT r.ringnumber = 4 ';	
		}
		
		$sql = "
			SELECT
				c.id_comb,
				ui.id_userinstance
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = c.id_userinstance)
			WHERE
				$where
				AND
				ui.id_user = ".$ToUserId."
				AND
     			r.id_world = ".self::$worldId."
     			AND
     			c.is_capture = 'f'
     			order by r.ringnumber, cc.complexnumber, c.combnumber
			";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static private function _checkCombForExchange($userIdWith)
	{
		//Если у нас основное кольцо
		if (self::$ring != 4) 
		{
			$combTo = self::_getCombIdByUID($userIdWith);
			assert(count($combTo) == 1);
			self::$combIdTo = (int)$combTo[0]['id_comb'];
		} else {
			//Если у нас колония
			if (empty(self::$combComplexTo)) {
				self::$data['MSG'] = 'EMPTY_COMPLEX';
				return false;
			}
			
			if (empty(self::$combNumberTo)) {
				self::$data['MSG'] = 'EMPTY_COMB';
				return false;
			}
			
			//Проверяем, существует ли такая сота
			self::$combIdTo = (int)DS_Comb::GetIDByCordinates(self::$worldId, self::$ring, self::$combComplexTo, self::$combNumberTo);

			if(!self::$combIdTo)
			{
				self::$data['MSG'] = 'COMB_NOT_EXISTS';
				return false;
			}
		}
		
		if(self::$combIdTo == 0)
		{
			self::$data['MSG'] = 'NO_COMB_TO_EXCHANGE';
			return false;
		}

		
		$combTo = DS_Comb::GetByID(self::$combIdTo);
		
		if(self::$ring != $combTo['ringnumber'])
		{
			self::$data['MSG'] = 'NO_COMB_TO_EXCHANGE';
			return false;
		}

		$combData = self::_getCombData(self::$combIdTo);//DS_Comb::GetByID($this->combToId);
		
		//Проверяем, принадлежит ли данная сота введенному игроку
		if($combData['id_user'] != $userIdWith)
		{
			self::$data['MSG'] = 'COMB_NOT_BELONG_TO_THIS_USER';
			return false;
		}

		if(!(int)$combData['id_userinstance'] > 0 || empty($combData['combname']))
		{
			self::$data['MSG'] = 'NO_COMB_TO_EXCHANGE';
			return false;
		}
		
		return true;
	}
	
	static private function _GetCombData($combId)
	{
		$common = DS_Common::CreateForCurentDB();

		$sql = "
			SELECT
				cn.combname,
				c.id_userinstance,
				cc.complexnumber,
				r.ringnumber,
				c.combnumber,
				u.id_user
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
				LEFT JOIN ds_userinstance AS ui ON (c.id_userinstance = ui.id_userinstance)
				LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)
			WHERE
				c.id_comb = ".$combId."				
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0];
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
	
	static private function _GetOrderedTransfer($userId)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				*
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$userId."
				 AND
				id_world = ".self::$worldId."
				 AND
				NOT transfer_complite is true
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg)); 
		
		assert(count($res) == 0 || count($res) == 1);
			
		return $res;
	}
	
	static private function _GetOrderedTransferWith($userId)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				*
			FROM
				ds_free_ring_transfer t
			WHERE
				t.exchange_with = $userId
				 AND
				id_world = ".self::$worldId."
				 AND
				NOT transfer_complite is true
			";
		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg)); 
			
		return $res;
	}
	
	
	static private function _SetTransferInfo($userIdWith)
	{
		$common = self::getCommon();
		$rderedTransfer = self::_GetOrderedTransfer(self::$user->getId());
		
		if(count($rderedTransfer) > 0)
		{
			$sql = "
				UPDATE ds_free_ring_transfer
				SET
					transfer_to = ".self::$combIdTo.",
					transfer_from = ".self::$combIdFrom.",
					exchange_with = ".$userIdWith.",
					used_free_transfer = true,
					id_world = ".self::$worldId."
				WHERE
					id_user = ".self::$user->getId()."		
					AND
					id_world = ".self::$worldId."
			";
		} else {
			$sql = "
				INSERT INTO ds_free_ring_transfer
					(transfer_to, transfer_from, exchange_with, used_free_transfer, id_user, id_userinstance, id_world)
				VALUES
					(".self::$combIdTo.", ".self::$combIdFrom.", ".$userIdWith.", true, ".self::$user->getId().", ".self::$userInstanceFrom.", ".self::$worldId.")
				";
		}
		
		$common->executeQuery($sql);
		if (!empty($common->errormsg)) {
			return false;
		}
		
		return true;
	}
	
	static private function _GetServicePaidInfo($idUserOrdered)
	{
		assert(is_int($idUserOrdered) && $idUserOrdered > 0);

		$common = self::getCommon();
		
		$sql = "SELECT 
					*
				FROM
					ds_paymentserviceslog psl,
					ds_paymentserviceslog_users pslu
				WHERE
					psl.is_complete IS FALSE
					 AND
					psl.is_canceled IS FALSE
					 AND
					psl.id_paymentserviceslog = pslu.id_paymentserviceslog
					 AND
					pslu.is_initiator IS TRUE
					 AND
					action_name = '".self::$data['PS']."'
					 AND
					pslu.id_user = ".$idUserOrdered;

		$res = $common->getResultArray("", $sql, "", "", "");

		assert(empty($common->errormsg));
		assert(count($res) == 1 || count($res) == 0);
		
		return $res;
	}
	
	static private function _setTransferIsCanseled($idUserOrdered)
	{
		$common = self::getCommon();
		
		$sql = "UPDATE ds_paymentserviceslog
				SET is_canceled = 't', cancel_date = now()
				WHERE id_paymentserviceslog =
						(
							SELECT psl.id_paymentserviceslog
								FROM
									ds_paymentserviceslog psl,
									ds_paymentserviceslog_users pslu
								WHERE
									psl.is_complete IS FALSE
									 AND
									psl.is_canceled IS FALSE
									 AND
									psl.id_paymentserviceslog = pslu.id_paymentserviceslog
									 AND
									pslu.is_initiator IS TRUE
									 AND
									pslu.id_user = ".$idUserOrdered."
									 AND
									action_name = '".self::$data['PS']."'
							) 
							
								";
		
		$common->executeQuery($sql);		
		assert(empty($common->errormsg));
	}
	
	static private function _setTransferIsComplete($isPaid)
	{
		$common = self::getCommon();
		
		if ($isPaid == true) {
			$set = ", is_paid = 't', paid_date = now()";
		}
		
		$sql = "UPDATE ds_paymentserviceslog
				SET is_complete = 't', complite_date = now()
				$set
				WHERE id_paymentserviceslog =
						(
							SELECT psl.id_paymentserviceslog
								FROM
									ds_paymentserviceslog psl,
									ds_paymentserviceslog_users pslu
								WHERE
									psl.is_complete IS FALSE
									 AND
									psl.is_canceled IS FALSE
									 AND
									psl.id_paymentserviceslog = pslu.id_paymentserviceslog
									 AND
									pslu.is_initiator IS TRUE
									 AND
									pslu.id_user = ".self::$userIdWith."
									 AND
									action_name = '".self::$data['PS']."'
							) 
								";
		$common->executeQuery($sql);	
		if (!empty($common->errormsg)) {
				return false;
			}	
		
		return true;
	}
	
	static private function _GetParams()
	{		
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('user_instance_from', self::$data))
		{
			self::$userInstanceFrom = (int)self::$data['user_instance_from'];
			assert(self::$userInstanceFrom > 0);
		}
		
		if(key_exists('ring', self::$data))
		{
			self::$ring = (int)self::$data['ring'];
		}
		
		if(key_exists('comb_id_from', self::$data))
		{
			self::$combIdFrom = (int)self::$data['comb_id_from'];
			assert(self::$combIdFrom > 0);
		}
		
		if(key_exists('comb_id_to', self::$data))
		{
			self::$combIdTo= (int)self::$data['comb_id_to'];
			assert(self::$combIdTo > 0);
		}
		
			if(key_exists('comb_complex_to', self::$data))
		{
			self::$combComplexTo = (int)self::$data['comb_complex_to'];
		}
		
		if(key_exists('comb_number_to', self::$data))
		{
			self::$combNumberTo = (int)self::$data['comb_number_to'];
		}
		
		if(key_exists('user_id_with', self::$data))
		{
			self::$userIdWith = (int)self::$data['user_id_with'];
			assert(self::$userIdWith > 0);
		}
				
		if(key_exists('user_name_with', self::$data))
		{
			self::$userNameWith = self::$data['user_name_with'];
		}
		
		if(key_exists('who_pay', self::$data))
		{
			self::$whoPay = self::$data['who_pay'];
		}
		
		if(key_exists('world', self::$data))
		{
			$arr = explode('_', self::$data['world']);
			self::$dbId = (int)$arr[0];
			self::$worldId = (int)$arr[1];
			self::$worldName = $arr[2];
			
			assert(self::$dbId >= 0);
			assert(self::$worldId > 0);
			assert(!empty(self::$worldName));
		}
	}
}

?>
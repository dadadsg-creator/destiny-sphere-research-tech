<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecByResources extends UC_PaymentServiceBase
{	
	static private $resource;
	
	static private $amount;
	
	static private $resName;
	
	static private $packetAmount;

	public function Exec()
	{
		self::_getParams();
		
		$_SESSION['dbId'] = 0;
		
		switch(self::$action)
		{
			case 'show_second_menu':
					self::_ShowSecondMenu();
				break;	
				
			case 'confirm_buy_resource':
				if (!self::_ConfirmBuyResource()) 
				{
					self::_ShowSecondMenu();
				}
				break;
				
			case 'buy_resource':
				if (!self::_BuyResource()) 
				{
					self::_ShowSecondMenu();
				}
				break;
				
			case 'successful':
					self::_ShowSuccessfulByRes();
				break;	
				
			default:
				self::_ShowIndex();
				break;
		}
		
		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
	
	static private function _ShowIndex()
	{
		self::$data['MODE'] = 'INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';

		$_SESSION['dbId'] = 0;
		
		//Получаем список миров игрока
		$userworlds = self::_GetUserWorlds();

		foreach ($userworlds as $key => $world)
		{				
			$_SESSION['dbId'] = $world['dbId'];
			$_SESSION['world'] = $world['id_world'];

			//Смотрим, можно ли в этом мире покупать ресы
			$allowBuyResources = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_FOR_MONEY_ALLOWED');

			if ($allowBuyResources == 1) 
			{
				$allowedWorlds[] = $world;
			}

		}

		self::$data['USER_WORLDS'] = $allowedWorlds;			
	}
	
	static private function _ShowSecondMenu()
	{
		$_SESSION['dbId'] = 0;
		
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['WORLD_NAME'] = self::$worldName;		

		/*$resources = array(
		'STASIS_ENERGY' => array('name' =>'статической энергии',
		'amount' => 200000
		),
		'FOOD' => array('name' =>'еды',
		'amount' => 100000
		),
		'KADERIUM' => array('name' =>'кадериума',
		'amount' => 30000
		),
		'NANO_CRYSTAL' => array('name' =>'нано-кристаллов',
		'amount' => 20000
		),
		'PRODIUM' => array('name' =>'продиума',
		'amount' => 5000
		),
		);*/
		
		$resources = array(
		'STASIS_ENERGY' => 200000,
		'FOOD' => 100000,
		'KADERIUM' => 30000,
		'NANO_CRYSTAL' => 20000,
		'PRODIUM' => 5000
		);

		//Получаем цену ресов
		//foreach ($resources as $res => $resName)
		foreach ($resources as $resName => $amount)
		{
			$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_RESOURCE_'.$resName);
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
			$price = $MoneyActionType->getAmount();
			$credit = $MoneyActionType->getCreditName();
			
			$resData[$resName]['PRICE'] = $price;
			$resData[$resName]['CREDIT'] = $credit;
		}

		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
			
		//Получаем данные ресов
		foreach ($resources as $resName => $amount)
		{
			//Сморим, сколько еще игрок может купить
			$boughtRes = DE_UserBoughtResources::CreateFromID(self::$user->getId().'_'.self::$worldId);
			$new = $boughtRes->get($resName);
			$max = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_'.$resName.'_LIMIT');
				
			$resData[$resName]['res_available'] = max(0, floor(($max - $new)/$amount));
			$resData[$resName]['name'] = $resName;
			$resData[$resName]['amount'] = number_format($amount, 0, null, " ");
		}

		self::$data['RES_DATA'] = $resData;
		
		self::$data['MODE'] = 'BUY_RESOURCE_SECOND_MENU';
	}
	
	
	static private function _ConfirmBuyResource()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		assert(!empty(self::$resource));
		assert(is_int(self::$amount) &&  self::$amount > 0);		
		
		//Проверям была ли введена цифра
		if(strcmp((int)self::$packetAmount, self::$packetAmount) != 0 || (int)self::$packetAmount <= 0)
		{
			self::$data['MSG'] = 'WRONG_PACKET_AMMOUNT';
			return false;
		}

		self::$packetAmount = (int)self::$packetAmount;
		
		$_SESSION['dbId'] = 0;

		//Смотрим достаточно ли денег у игрока
		if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_BUY_RESOURCE_'.self::$resource, self::$packetAmount))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

			return false;
		}
		
		$_SESSION['dbId'] = self::$dbId;		
		$_SESSION['world'] = self::$worldId;
		
		//Смотрим, может ли игрок купить такое количество пакетов
		$boughtRes = DE_UserBoughtResources::CreateFromID(self::$user->getId().'_'.self::$worldId);
		$new = $boughtRes->get(self::$resource) + self::$amount*self::$packetAmount;
		$max = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_'.self::$resource.'_LIMIT');

		if($new > $max)
		{
			self::$data['MSG'] = 'CANT_BY_THIS_AMOUNT';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Получаем цену (для лога)
		$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_RESOURCE_'.self::$resource);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$price = $MoneyActionType->getAmount()*self::$packetAmount;
		$credit = $MoneyActionType->getCreditName();

		self::$data['RESOURSE'] = self::$resource;
		self::$data['RES_NAME'] = self::$resName;
		self::$data['PACKET_AMOUNT'] = self::$packetAmount;
		self::$data['AMOUNT'] = self::$amount;
		self::$data['CALCULATED_AMOUNT'] = number_format(self::$amount*self::$packetAmount, 0, null, " ");
		
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['WORLD_NAME'] = self::$worldName;
		
		self::$data['CALCULATED_PRICE'] = $price;
		self::$data['CREDIT'] = $credit;
		
		self::$data['MODE'] = 'BUY_RESOURCE_CONFIRM';
		
		return true;
	}
	
	static private function _BuyResource()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		assert(!empty(self::$resource));
		assert(is_int(self::$amount) &&  self::$amount > 0);
		assert((int)self::$packetAmount > 0);
		
		$_SESSION['dbId'] = 0;

		//Смотрим достаточно ли денег у игрока
		if(!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, 'PS_BUY_RESOURCE_'.self::$resource, (int)self::$packetAmount))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';
			
			return false;
		}
		
		//Получаем цену (для лога)
		$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_RESOURCE_'.self::$resource);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$price = $MoneyActionType->getAmount()*self::$packetAmount;
		$credit = $MoneyActionType->getCreditName();
		
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));

		$common = self::getCommon();
		
		$uiid = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);
		assert($uiid > 0);
		
		//Еще раз проверяем, можно ли в этом мире покупать ресы за деньги
		$buyAllowed = (int)DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_FOR_MONEY_ALLOWED');
		
		if($buyAllowed != 1)
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$boughtRes = DE_UserBoughtResources::CreateFromID(self::$user->getId().'_'.self::$worldId);
		$new = $boughtRes->get(self::$resource) + self::$amount*self::$packetAmount;
		$max = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_'.self::$resource.'_LIMIT');

		if($new > $max)
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$common->startTrans();
		
		$combAttr = DE_CombAttributes::CreateFromCombID(DS_BillingBuyResource::getCombID(self::$dbId, $uiid));
		$boughtRes = DE_UserBoughtResources::CreateFromID(self::$user->getId().'_'.self::$worldId);
		
		$combAttr->deltaAttribute('CURRENT_AMOUNT', self::$resource, self::$amount*self::$packetAmount);
		$boughtRes->delta(self::$resource, self::$amount*self::$packetAmount);
		
		$combAttr->commit();
		$boughtRes->commit();
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_BUY_RESOURCE_'.self::$resource, (int)self::$packetAmount, $WI);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_BUY_RESOURCE_'.self::$resource, $price, $credit, "Buy: ".self::$amount." x ".self::$packetAmount." packets of ".self::$resource.", World: ".self::$worldName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Завершаем транзакцию
		$common->closeTrans('COMMIT');
		
		$_SESSION['AMOUNT'] = number_format(self::$amount*self::$packetAmount, 0, null, " ");
		$_SESSION['CALCULATED_PRICE'] = $price;
		$_SESSION['CREDIT'] = $credit;
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&resName=".self::$resName);
		return true;
	}
	
	private static function _ShowSuccessfulByRes()
	{
		self::$data['MODE'] = 'SUCCESSFUL';
		self::$data['AMOUNT'] = $_SESSION['AMOUNT'];
		self::$data['TEG'] = self::$resName;
		self::$data['CALCULATED_PRICE'] = $_SESSION['CALCULATED_PRICE'];
		self::$data['CREDIT'] = $_SESSION['CREDIT'];
	}
		
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('resource', self::$data))
		{
			self::$resource = self::$data['resource'];
		}
		
		if(key_exists('amount', self::$data))
		{
			self::$amount = (int)str_replace(" ", "", self::$data['amount']);		
			assert(self::$amount > 0);
		}
			
		if(key_exists('resName', self::$data))
		{
			self::$resName = self::$data['resName'];
			assert(!empty(self::$resName));
		}
				
		if(key_exists('packet_amount', self::$data))
		{
			self::$packetAmount = self::$data['packet_amount'];
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
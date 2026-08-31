<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2009/02/16 10:48:37 $';


class UC_ExecByPremium extends UC_PaymentServiceBase
{	
	static private $countOfFays;
	
	static private $analizator;
	
	static private $userName;
	
	static private $days = array(
							0 => '360',
							1 => '180',
							2 => '90',
							3 => '30',
						);

	public function Exec()
	{
		$_SESSION['dbId'] = 0;
		
		self::_getParams();

		switch(self::$action)
		{
			case 'show_second_menu':
					self::_ShowSecondMenu();
				break;
			
			case 'buy_premium':				
				if (!self::_BuyPremium())
				{
					self::_ShowSecondMenu();
				}
				break;
				
			case 'successful':
				self::_ShowSuccessfulByPremium();
				break;	
				
			default:
				self::_ShowIndex();
				break;
		}
		UC_OutUserCabinet::Out('PaymantServices', self::$data);
	}
	
	static private function _ShowIndex()
	{
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
		}		

		//Смотрим, можно ли продавать анализатор
		if (UC_PaymentServiceBase::IsServiceActive('PS_BUY_ANALIZATOR') == 't'){
			$byAnalizator = true;
			self::$data['BUY_ANALIZATOR'] = 'true';
		} else {
			$byAnalizator = false;
		}
		
		//Получаем цену према
		foreach (self::$days as $key => $countOfDay)
		{
			$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_PREMIUM_'.$countOfDay);
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
			$price = $MoneyActionType->getAmount();
			$credit = $MoneyActionType->getCreditName();

			$premData[$countOfDay]['PRICE'] = $price;
			$premData[$countOfDay]['CREDIT'] = $credit;

			if ($byAnalizator == true) {
				//Получаем цену према с анализатором
				$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_PREMIUM_PLUS_ANALIZATOR_'.$countOfDay);
				$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
				$price = $MoneyActionType->getAmount();
				$credit = $MoneyActionType->getCreditName();

				$premData[$countOfDay]['PRICE_PLUS'] = $price;
				$premData[$countOfDay]['CREDIT_PLUS'] = $credit;
			}
		}

		self::$data['DAYS'] = $premData;
		
		// Если игрок премиум
		if (self::$user->isProfy()) {
			//Получаем количество оставшихся дней премиума
			$common = self::getCommon();
			GE_SearchStart::SetCommon($common);
			$premiumDays = GE_SearchStart::GetPremiumDaysLeft(self::$user->getId());
			self::$data['PREM_DAYS'] = $premiumDays['day'];
			self::$data['PREM_HOURS'] = $premiumDays['hour'];	
		}
		
		
		self::$data['MODE'] = 'BUY_PREMIUM_INDEX';
	}
	
	static private function _ShowSecondMenu()
	{
		assert(in_array(self::$countOfFays, self::$days));		
		assert(self::$analizator == 'no' || self::$analizator == 'yes');	
		
		if (self::$analizator == 'yes') {
			$plus = 'PLUS_ANALIZATOR_';
			self::$data['BUY_ANALIZATOR'] = 'true';
		} else {
			$plus = '';
		}			
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney('_'.$plus.self::$countOfFays))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			return;
		}
				
		//Получаем цену (для лога)
		$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_PREMIUM_'.$plus.self::$countOfFays);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		self::$data['PRICE_PLUS'] = $MoneyActionType->getAmount();
		self::$data['CREDIT_PLUS'] = $MoneyActionType->getCreditName();
		
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
		}
		
		self::$data['COUNT_OF_DAY'] = self::$countOfFays;
		self::$data['FOR_USER'] = self::$userName;

		self::$data['MODE'] = 'BUY_PREMIUM_SECOND_MENU';
	}
	
	static private function _BuyPremium()
	{
		assert(in_array(self::$countOfFays, self::$days));		
		assert(self::$analizator == 'no' || self::$analizator == 'yes');	
		
		if (self::$analizator == 'yes') {
			$plus = 'PLUS_ANALIZATOR_';
		} else {
			$plus = '';
		}			
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney('_'.$plus.self::$countOfFays))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			return;
		}

		$desc = self::$countOfFays." days";

		// Если игрок покупает прем сам себе
		if (self::$userName == '') 
		{
			$toUser = self::$user;
		} else {
			//Проверяем, есть ли пользователь с введенным ником
			$toUserId = (int)self::_GetUserIdByName(self::$userName);

			if (empty($toUserId))
			{
				self::$data['MSG'] = 'TO_USER_NOT_EXIST';
				return false;
			}
			$toUser = DE_User::CreateFromID($toUserId);

			$desc .= " for user ".self::$userName;

			$header = "&forOutherPlayer=t&for_user=".self::$userName;
		}
				
		//Получаем цену (для лога)
		$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_PREMIUM_'.$plus.self::$countOfFays);
		$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
		$price = $MoneyActionType->getAmount();
		$credit = $MoneyActionType->getCreditName();
		
		//Если надо, добавляем анализатор
		if (self::$analizator == 'yes') {
			if (!PU_ExecChangeAnalizerPremium::Exec($toUser, self::$countOfFays, 'CABINET', 'payment servises', true)) {
				self::$data['MSG'] = 'ERROR';
				return false;
			}
		}

		// Начисляем премиум
		if(!PU_ExecChangePremiumDays::Exec($toUser, self::$countOfFays, 'CABINET', 'payment servises', true))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_BUY_PREMIUM_'.$plus.self::$countOfFays);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_BUY_PREMIUM_'.$plus.self::$countOfFays, $price, $credit, $desc))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		if (empty(self::$userName) && self::$userName !== 0) {
			//Получаем количество оставшихся дней премиума
			$common = self::getCommon();
			GE_SearchStart::SetCommon($common);
			$premiumDays = GE_SearchStart::GetPremiumDaysLeft(self::$user->getId());
			$header = "&forOutherPlayer=f&days=".$premiumDays['day']."&hours=".$premiumDays['hour'];
		}
		
		$_SESSION['CALCULATED_PRICE'] = $price;
		$_SESSION['CREDIT'] = $credit;
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&count_of_days=".self::$countOfFays."&analizator=".self::$analizator.$header);
		return true;
	}
	
	private static function _ShowSuccessfulByPremium()
	{
		self::$data['COUNT_OF_DAYS'] = $_REQUEST['count_of_days'];
		self::$data['ANALIZATOR'] = $_REQUEST['analizator'];
		self::$data['DAYS'] = $_REQUEST['days'];
		self::$data['HOURS'] = $_REQUEST['hours'];
		self::$data['FOR_OUTHER_PLAYR'] = $_REQUEST['forOutherPlayer'];
		if (isset($_REQUEST['for_user'])) {
		self::$data['FOR_USER'] = $_REQUEST['for_user'];
			
		}
		self::$data['CREDIT'] = $_SESSION['CREDIT'];
		self::$data['CALCULATED_PRICE'] = $_SESSION['CALCULATED_PRICE'];
				
		self::$data['MODE'] = 'SUCCESSFUL';
	}
		
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('userName', self::$data))
		{
			self::$userName = self::$data['userName'];
		}
		
		if(key_exists('days', self::$data))
		{
			self::$countOfFays = (int)self::$data['days'];
		}
			
		if(key_exists('analizator', self::$data))
		{
			self::$analizator = self::$data['analizator'];
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
}

?>
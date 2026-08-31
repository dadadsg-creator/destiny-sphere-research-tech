<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/09/11 08:05:53 $';


class UC_ExecByEntryTurnir extends UC_PaymentServiceBase
{	
	static private $combName = "";
	
	static private $race = 1;
	
	// В чем будем платить, золоте или сере
	static private $curency = 1;
	
	public function Exec()
	{
		$_SESSION['dbId'] = 0;
		
		self::_getParams();
		
		self::_GetPrice();

		switch(self::$action)
		{
			case 'show_second_menu':
				self::_ShowSecondMenu();
				break;
				
			case 'buy_turnir':				
				if (!self::_ExecByEntryTurnir())
				{
					self::_ShowSecondMenu();
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
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}	
		
		self::$data['MODE'] = 'INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';
		
		// Получаем список турнирных миров
		self::$data['USER_WORLDS'] = TN_Registration::GetTurnirWorlds(true);
		
		//Если мир у игрока один
		if(count(self::$data['USER_WORLDS']) == 1)
		{
			self::$dbId = (int)self::$data['USER_WORLDS'][0]['dbId'];
			self::$worldId = (int)self::$data['USER_WORLDS'][0]['id_world'];
			self::$worldName = self::$data['USER_WORLDS'][0]['worldname'];
			
			self::_ShowSecondMenu();
		} 
	}	
	
	static private function _ShowSecondMenu()
	{
		assert(is_int(self::$dbId) && self::$dbId >= 0);
		assert(is_int(self::$worldId) && self::$worldId > 0);
		assert(!empty(self::$worldName));
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Проверяем, нет ли у игрока уже возможности играть в этом мире
		if (TN_Registration::CheckEnryTurnirAbility(self::$user->getId(), self::$dbId, self::$worldId) === true) {
			self::$data['ALREADY_HAS_TURNIR'] = 'YES';
		}
		
		// Проверяем, не достигло ли количество участников в турнире максимума
		$maxPlayers = DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_MAX_PLAYERS');
		assert($maxPlayers >= 0);
		// Если есть ограничение
		if ($maxPlayers != 0) {
			// Выбираем текущее количество участников
			$currentPlayers = TN_Registration::GetCurrentCountOfPlayers(self::$dbId, self::$worldId);
			assert($currentPlayers >= 0);			
			if ($currentPlayers >= $maxPlayers) {
				self::$data['ALREADY_TURNIR_MAX_USERS'] = 'YES';
			}
		}
		
		$_SESSION['dbId'] = 0;
		
		// список локализованных названий всех рас 
		$common = DS_Common::CreateForCurentDB(0);
		GE_SearchStart::SetCommon($common);
		$races = GE_SearchStart::GetRaceList($_SESSION['lang']);
		foreach($races AS $id_race => $race)
		{
			$allRaces[$race['id_race']] = $race['text'];
		}
		
		self::$data['COMB_NAME'] = self::$combName;
		self::$data['RACE'] = self::$race;
		self::$data['RACES'] = $allRaces;
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['MODE'] = 'BUY_TURNIR_SECOND_MENU';
	}
	
	static private function _ExecByEntryTurnir()
	{
		assert(is_int(self::$dbId) && self::$dbId >= 0);
		assert(is_int(self::$worldId) && self::$worldId > 0);
		assert(!empty(self::$worldName));
		assert(self::$curency == 'SC' || self::$curency == 'GC');

		//Смотрим достаточно ли денег у игрока
		if (self::$curency == 'SC') {
			self::$data['SC_SELECTED'] = 'YES';
			if (!self::_CheckEnoughCredit('SC'))
			{
				return false;
			}
		} else {
			if (!self::_CheckEnoughCredit('GC'))
			{
				return false;
			}
		}
		
		// Проверяем рассу
		if (self::$race < 1 || self::$race > 4) {
			
		}
	
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Проверяем, не достигло ли количество участников в турнире максимума
		$maxPlayers = DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_MAX_PLAYERS');
		assert($maxPlayers >= 0);
		// Если есть ограничение
		if ($maxPlayers != 0) {
			// Выбираем текущее количество участников
			$currentPlayers = TN_Registration::GetCurrentCountOfPlayers(self::$dbId, self::$worldId);
			assert($currentPlayers >= 0);			
			if ($currentPlayers >= $maxPlayers) {
				self::$data['MSG'] = 'ALREADY_TURNIR_MAX_USERS';
				return false;
			}
		}
		
		// Пробуем создать инстанс
		$createInstanceRes = TN_Registration::Registration(self::$user->getId(), self::$dbId, self::$worldId, self::$race, self::$combName);
		if ($createInstanceRes != 'SACCESSFUL') {			
			self::$data['MSG'] = $createInstanceRes;	
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_BUY_TURNIR', 1, '', self::$curency);
		
		if (self::$curency == 'GC') {
			$price = self::$data['PRICE_IN_GOLD'];
		} else {
			$price = self::$data['PRICE'];
		}
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_BUY_TURNIR', $price, self::$curency, 'Reg in turnir world'.self::$worldName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&curency=".self::$curency);
		return true;
	}
	
	private static function _ShowSuccessful()
	{
		self::$data['MODE'] = 'SUCCESSFUL';
		self::$data['CURENCY'] = self::$curency;
	}
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('comb_name', self::$data))
		{
			self::$combName = self::$data['comb_name'];
		}
		
		if(key_exists('race', self::$data))
		{
			self::$race = (int)self::$data['race'];
		}
		
		if(key_exists('curency', self::$data))
		{
			self::$curency = self::$data['curency'];
			assert(self::$curency == 'SC' || self::$curency == 'GC');
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
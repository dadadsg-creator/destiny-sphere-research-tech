<?php  $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/10/08 10:14:37 $';


/**
 * Платная услуга по ускорению обучения и завершению постройки зданий
 *
 */

class UC_ExecByTraining extends UC_PaymentServiceBase {

	public function Exec() {

		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();

		self::_getParams();

		switch(self::$action)
		{
			case 'show_second_menu':
			 self::_ShowSecondMenu();
				break;
			case 'complete':
				if (!self::_CompleteTraining()) {
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
		self::$data['MODE'] = 'INDEX';
		self::$data['EXEC_ACTION'] = 'show_second_menu';

		$_SESSION['dbId'] = 0;

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


	static private function _ShowSecondMenu() {
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId)) {
			header('Location: index_down.html');
			exit;
		}
		self::$data['MODE'] = 'BUY_TRAINING_COMPLETION';

		$_SESSION['dbId'] = self::$dbId;

		$userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);


		// Есть ли у игрока изучающиеся науки выше 20го уровня
		self::$data['USER_IS_NUB'] = DS_PaymentService::CheckUserIsNub($userInstanse);
		// Есть ли у игрока ивенты науки или постройки зданий (за исключением здания Гондола-Транспортер)
		self::$data['USER_EVENTS'] = DS_PaymentService::GetCountUserEvents($userInstanse);
		
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
	}

	static private function _CompleteTraining() {
		
		$_SESSION['dbId'] = 0;
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney()) {
			return false;
		}
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;

		$userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), (int)self::$worldId);

		// Проверяем, является ли чел нубом
		if (!DS_PaymentService::CheckUserIsNub($userInstanse)) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		// Проверяем наличие у игрока ивентов, еоторые бум завершать
		if (DS_PaymentService::GetCountUserEvents($userInstanse) == 0) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));

		// Завершаем ивенты
		DS_PaymentService::ComplitTraining($userInstanse);

		$_SESSION['dbId'] = 0;

		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_BUY_TRAINING_COMPLETION', 1, $WI);

		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', self::$data['PS'], self::$data['PRICE'], self::$data['CREDIT'], "World: ".self::$worldName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}

		ME_EndDBTransactions::Exec();

		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful");
		return true;
	}

	static private function _ShowSuccessful() {
		self::$data['MODE'] = 'SUCCESSFUL';
	}


	static private function _getParams() {
		if(key_exists('exec_action', self::$data)) {
			self::$action = self::$data['exec_action'];
		}

		if(key_exists('world', self::$data)) {
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
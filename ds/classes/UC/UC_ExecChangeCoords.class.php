<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2010/07/07 09:00:42 $';


class UC_ExecChangeCoords extends UC_PaymentServiceBase
{
	static private $combToId;
	
	static private $ring;
	
	static private $complex;
	
	static private $comb;
	
	static private $oldCoords;
	
	static private $combName;
		
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
		
			case 'change_coords':
				if (!self::_ChangeCoords()) 
				{
					self::_ShowSecondMenu();
				}
				
				break;
					
			case 'successful':
					self::_ShowSuccessful();
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
		
		if (self::$data['PS'] == 'PS_CHANGE_COORDS') {
			self::$ring = 1;	
		} else {
			self::$ring = 4;
		}
		self::$data['RING'] = self::$ring;
		
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
	
	static private function _ShowSecondMenu()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);

		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
				
		$_SESSION['dbId'] = 0;
		
		$_SESSION['dbId'] = self::$dbId;
		
		//Получаем список соты или колоний в мире
		$usersCombs = self::_GetCombIdByUID();

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
		
		self::$data['COMBS'] = $combs;
		
		//Если у нас уже была выбрана сота, которую хотим перевести
		if (!empty(self::$oldCoords)) {
			self::$data['OLD_COORDS'] = self::$oldCoords;
		} else {
			self::$data['OLD_COORDS'] = '';
		}
		
		//Если у нас уже была выбрана сота, на которую хотим переехать
		if (!empty(self::$complex)) 
		{
			self::$data['COMPLEX'] = self::$complex;
		}
		
		if (!empty(self::$comb)) 
		{
			self::$data['COMB_NUMBER'] = self::$comb;
		}
		
		self::$data['MODE'] = 'CHANGE_COORDS_SECOND_MENU';
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
	}
	
	static private function _ChangeCoords()
	{
		$_SESSION['dbId'] = 0;
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
				
		$_SESSION['dbId'] = self::$dbId;
		
		//Если поле с координатами соты было оставлено пустым
		if (empty(self::$ring) || empty(self::$complex) || empty(self::$comb))
		{
			self::$data['MSG'] = 'WRONG_COMB_COORDS';
			return false;
		}
					
		self::$combToId = (int)DS_Comb::GetIDByCordinates(self::$worldId, self::$ring, self::$complex, self::$comb);

		if(!self::$combToId)
		{
			self::$data['MSG'] = 'COMB_NOT_EXISTS';
			return false;
		}
		
		if(!self::_checkCombForTransfer())
		{
			return false;
		}
		
		$coordsTemp = explode('.', self::$oldCoords);
		$complexNumber = (int)$coordsTemp['1'];
		$combNumber = (int)$coordsTemp['2'];
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));

		
		//Сбрасываем кеш вида комплекса с которого переехали
		//Создаем обьект Кольцо
		$comb = DE_Comb::CreateFromCoordinates(self::$worldId, self::$ring, $complexNumber, $combNumber);
		$ring = DE_Ring::CreateFromNumber($comb->getRingNumber());
		$ringId = $ring->getID();
		$combComplex = 	$comb->getComplexID();
		//Создаем обьект Кольцо
		$comb2 = DE_Comb::CreateFromCoordinates(self::$worldId, self::$ring, self::$complex, self::$comb);
		$combComplex2 = 	$comb2->getComplexID();
		
		//Меняем коордтнаты соты
		$transfer = new SU_ChangCombCoords(self::$worldName);
		$transfer->change(self::$ring.".".self::$complex.".".self::$comb, self::$oldCoords);
		
		//Сбрасываем кеш вида комплекса с которого переехали
		UA_ComplexView::ResetCache(self::$worldId, $ringId, $combComplex);
		//Сбрасываем кеш вида комплекса на который переехали
		UA_ComplexView::ResetCache(self::$worldId, $ringId, $combComplex2);

		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, self::$data['PS'], 1, $WI);		

		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', self::$data['PS'], self::$data['PRICE'], self::$data['CREDIT'], "Old coords: ".self::$oldCoords.", New coords: ".self::$ring.".".self::$complex.".".self::$comb.", World: ".self::$worldName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&combName=".self::$combName."&oldCoords=".self::$oldCoords."&newCoords=".self::$ring.".".self::$complex.".".self::$comb);
		return true;
	}
	
	private static function _ShowSuccessful ()
	{				
		self::$data['COMB_NAME'] = $_REQUEST['combName'];
		self::$data['OLD_COORDS'] = $_REQUEST['oldCoords'];
		self::$data['NEW_COORDS'] = $_REQUEST['newCoords'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _GetCombIdByUID()
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
				ui.id_user = ".self::$user->getID()."
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
	
	static private function _CheckCombForTransfer()
	{
		$common = self::getCommon();
		
		$combData = self::_getCombData(self::$combToId);
		
		if((int)$combData['id_userinstance'] > 0 || !empty($combData['combname']))
		{
			self::$data['MSG'] = 'NOT_EMPTY_COMB';
			return false;
		}
		
		$sql = "
			SELECT
				u.id_user
			FROM
				ds_reservedcomb AS rc
				INNER JOIN ds_user AS u ON (u.username = rc.username)
			WHERE
				id_comb = ".self::$combToId."	
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			if((int)$res[0]['id_user'] != self::$user->getId())
			{
				self::$data['MSG'] = 'COMB_RESERVED';
				return false;
			}
		}
		
		return true;
	}
	
	static private function _GetCombData($combId)
	{
		$common = self::getCommon();
		
		$sql = "
			SELECT
				cn.combname,
				c.id_userinstance,
				cc.complexnumber,
				r.ringnumber,
				c.combnumber
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
			WHERE
				c.id_comb = ".$combId."				
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0];
	}
	
	static private function _GetParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('ring', self::$data))
		{
			self::$ring = (int)self::$data['ring'];
		}
		
		if(key_exists('complex', self::$data))
		{
			self::$complex = (int)self::$data['complex'];
		}
		
		if(key_exists('comb', self::$data))
		{
			self::$comb = (int)self::$data['comb'];
		}
		
		if(key_exists('old_coords', self::$data))
		{
			$temp = explode('_', self::$data['old_coords']);
			self::$oldCoords = $temp[0];
			self::$combName = $temp[1];
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
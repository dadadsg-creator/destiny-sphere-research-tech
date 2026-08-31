<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecByUnit extends UC_PaymentServiceBase
{
	// Количество покупаемых юнитов
	static private $unitAmount;
	
	// Тип юнитов, которых покупаем 
	static private $unit;
	
	// Сота, на которую будут вписаны бниты
	static private $id_comb;
	
	public function Exec()
	{
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		//self::_GetPrice();
		
		self::_getParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();
		
		// Чтобы цена выводилась не стандартно
		self::$data['UNIT_PRICE'] = self::$data['PRICE'];
		self::$data['PRICE'] = '';
		
		switch (self::$data['PS'])
		{
			case 'PS_BY_UNIT_SCIENTIIFIC':
				self::$unit = 'SCIENTIST';
				break;
			case 'PS_BY_UNIT_ARCHEOLOG':
				self::$unit = 'ARCHEOLOGIST';
				break;
			default:
				return;
		}
		
		self::$data['UNIT_NAME'] = self::$unit;

		switch(self::$action)
		{
			case 'index':
				self::_ShowIndex();
				break;
				
			case 'show_second_menu':
				self::_ShowSecondMenu();
				break;
				
			case 'by_unit_confirm':
				if (!self::_BuyUnitConfirm()) {
					self::_ShowSecondMenu();
				}
				
				break;
				
			case 'by_unit':
				if (!self::_BuyUnit()) {
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
		
		//Если мир один выводим запрос на подтвержление удаления
		//Получаем список миров игрока
		$userworlds = self::_getUserWorlds();
		
		//Если мир у игрока один
		if(count($userworlds) == 1)
		{
			self::$worldId = (int)$userworlds[0]['id_world'];
			self::$dbId = (int)$userworlds[0]['dbId'];
			self::$worldName = $userworlds[0]['worldname'];
			
			self::_ShowSecondMenu();
		} 
		else{
			//Если миров несколько, то выводим выбор миров
			self::$data['USER_WORLDS'] = $userworlds;
		}
	}
	
	static private function _ShowSecondMenu()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
			
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
			
		// Получам соты игрока	
		$usersCombs = self::_GetCombIdByUID();
		
		foreach ($usersCombs as $key => $comb)
		{
			//Получаем данные соты
			$combs[] =  DS_Comb::GetByID($comb['id_comb']);
		}

		// Получаем предельное число юнитов
		$unitLimit = DE_WorldSettings::CreateForCurentWorld()->get(self::$unit.'_BUY_LIMIT');
		if ($unitLimit > 0 ) {
			self::$data['UNIT_LIMIT'] = $unitLimit;
		}
		
		self::$data['COMBS'] = $combs;
		
		self::$data['MODE'] = 'BY_UNIT_SECOND_MENU';
		self::$data['EXEC_ACTION'] = 'by_unit_confirm';
			
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
	}
	
	static private function _BuyUnitConfirm()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
		
		// Проверяем на корректность количество юнитов, которое хотим купить
		assert(is_int(self::$unitAmount));
		if (self::$unitAmount <= 0) {
			self::$data['MSG'] = 'WRONG_UNIT_AMMOUNT';
			return false;
		}
		
		// Проверяем на корректность соту
		assert(is_int(self::$id_comb));
		if (self::$id_comb <= 0) {
			self::$data['MSG'] = 'WRONG_DATA';
			return false;
		}
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;

		// Получам количество юнитов, которые можем вписать на данную соту
		$unitsArr = self::_GetMaxAndCurUnitAmount(self::$id_comb);
		
		if (empty($unitsArr)) {
			self::$data['MSG'] = 'WRONG_DATA';
			return false;
		}
		
		$maxAmount = (int)$unitsArr[0]['max_amount'];
		$curAmount = (int)$unitsArr[0]['cur_amount'] + (int)$unitsArr[0]['away_amount'];
		
		// Высчитываем количество юнитов, которые можем добавить
		$availableAmount = $maxAmount - $curAmount;
		
		// Если у игрока нет места для юнитов
		if ($availableAmount <= 0) {
			self::$data['ENABLE_BY_UNIT'] = false;
		} else {
			self::$data['ENABLE_BY_UNIT'] = true;
		}
		
		// Если значение, введенное игроком больше доступного для покупки
		if (self::$unitAmount > $availableAmount) {
			self::$data['NOT_PLACE_TO_ALL'] = true;
			self::$data['OLD_UNIT_AMOUNT'] = self::$unitAmount;
			
			self::$unitAmount = $availableAmount;
		}
		
		// Проверяем, не достигли максимального количества юнитов на соте
		$unitLimit = DE_WorldSettings::CreateForCurentWorld()->get(self::$unit.'_BUY_LIMIT');
		// Если огараничение есть 
		if ($unitLimit > 0 ) {
			$AllCombCurAmount = 0;
			
			// Юниты в трансфере
			$transferUnitAmount = (int)self::_getUnitInTransfer();
			assert($transferUnitAmount >= 0 );
			
			// Получам соты игрока	
			$usersCombs = self::_GetCombIdByUID();
			// Считаем общее количесво юнитов на всех сотыах			
			foreach ($usersCombs as $key => $comb)
			{
				$combUnitsArr = self::_GetMaxAndCurUnitAmount((int)$comb['id_comb']);
				$AllCombCurAmount += (int)$combUnitsArr[0]['cur_amount'] + (int)$combUnitsArr[0]['away_amount'];
			}

			if ((self::$unitAmount + $AllCombCurAmount + $transferUnitAmount) > $unitLimit) {
				self::$data['MSG'] = 'MAX_UNIT_LIMIT_'.self::$unit;
				
				// Выводим пердупреждение
				if (($AllCombCurAmount + $transferUnitAmount) < $unitLimit) {
					self::$data['ENABLE_TO_BY'] = $unitLimit - ($AllCombCurAmount + $transferUnitAmount);
				}
				
				
				self::$data['MODE'] = 'BY_UNIT_MAX_UNIT_LIMIT';
				self::$data['UNIT_LIMIT'] = $unitLimit;
				self::$data['UNIT_LIMIT_LANG'] = 'MAX_UNIT_ON_ALL_COMB_'.self::$unit;
				return true;
			}	
		}
		
		
		// Количество юнтитов, которые будут добавлены
		self::$data['UNIT_AMOUNT'] = self::$unitAmount;
		
		// Стоимость
		self::$data['FINAL_COST'] = (int)self::$data['UNIT_PRICE']*self::$unitAmount;
	
		// Название соты, на которую будут вписаны юниты
		self::$data['COMB_NAME'] = $unitsArr[0]['combname']."(".$unitsArr[0]['coords'].")";
		self::$data['COMB_ID'] = self::$id_comb;

		
		self::$data['MODE'] = 'BY_UNIT_CONFIRM';
		self::$data['EXEC_ACTION'] = 'by_unit';
			
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		
		return true;
	}
	
	static private function _BuyUnit()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		// Проверяем на корректность количество юнитов, которое хотим купить
		assert(is_int(self::$unitAmount));
		if (self::$unitAmount <= 0 ) {
			self::$data['MSG'] = 'WRONG_UNIT_AMMOUNT';
			return false;
		}
		
		// Проверяем на корректность соту
		assert(is_int(self::$id_comb));
		if (self::$id_comb <= 0) {		
			self::$data['MSG'] = 'WRONG_DATA';
			return false;
		}
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));
		
		// Получам количество юнитов, которые можем вписать на данную соту
		$unitsArr = self::_GetMaxAndCurUnitAmount(self::$id_comb);
		
		if (empty($unitsArr)) {		
			self::$data['MSG'] = 'WRONG_DATA';
			return false;
		}
		
		$maxAmount = (int)$unitsArr[0]['max_amount'];
		$awayAmount = (int)$unitsArr[0]['away_amount'];
		$curAmount = (int)$unitsArr[0]['cur_amount'];
		
		// Считаем новое количество юнитов на соте
		$newValue = $curAmount + self::$unitAmount;
		
		// Если новое количество юнитов получилось больше, чем может быть, это ошибка 
		if ($newValue+$awayAmount > $maxAmount) {	
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		// Проверяем, не достигли максимального количества юнитов на соте
		$unitLimit = DE_WorldSettings::CreateForCurentWorld()->get(self::$unit.'_BUY_LIMIT');
		// Если огараничение есть 
		if ($unitLimit > 0 ) {
			$AllCombCurAmount = 0;
			
			// Юниты в трансфере
			$transferUnitAmount = (int)self::_getUnitInTransfer();
			assert($transferUnitAmount >= 0 );
			
			// Получам соты игрока	
			$usersCombs = self::_GetCombIdByUID();
			// Считаем общее количесво юнитов на всех сотыах			
			foreach ($usersCombs as $key => $comb)
			{
				$combUnitsArr = self::_GetMaxAndCurUnitAmount((int)$comb['id_comb']);
				$AllCombCurAmount += (int)$combUnitsArr[0]['cur_amount'] + (int)$combUnitsArr[0]['away_amount'];
			}
			
			if ((self::$unitAmount + $AllCombCurAmount + $transferUnitAmount) > $unitLimit) {
				self::$data['MSG'] = 'MAX_UNIT_LIMIT_'.self::$unit;
				return false;
			}	
		}
		
		// Название соты, на которую будут вписаны юниты
		$combName = $unitsArr[0]['combname']."(".$unitsArr[0]['coords'].")";
		
		$_SESSION['dbId'] = 0;

		//Смотрим достаточно ли денег у игрока
		if (!UC_PaymentServiceMoneyOperation::CheckMoneyForOperation(self::$user, self::$data['PS'], self::$unitAmount))
		{
			self::$data['MSG'] = 'NOT_ENOUGHT_CREDITS';
			self::$data['MODE'] = 'NOT_ENOUGHT_CREDITS';

			//Смотрим, может есть достаточное количество золотых крелитов
			if (UC_PaymentServiceMoneyOperation::CheckEnoughtMoney(self::$user, self::$data['PS'].$serviceDetail, 'UC')) {
				self::$data['ENOUGHT_GOLD'] = 'true';
				UC_OutUserCabinet::Out('PaymantServices', self::$data);
				exit;
			}
			
			//Получаем имя мира, в котором есть достаточно денег для данной операции
			$worldName = UC_PaymentServiceMoneyOperation::CheckMoneyInWorlds(self::$user, self::$data['PS'].$serviceDetail);
			
			//Если такой мир есть
			if($worldName)
			{
				self::$data['WORLD_WHERE_ENOUGH_MONEY'] = $worldName;
			}
			UC_OutUserCabinet::Out('PaymantServices', self::$data);
			exit;
		}		
				
		$_SESSION['dbId'] = self::$dbId;

		// Добавляем игроку юнитов
		if (!self::_UpdateUnotAmount($newValue)) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		// Пишем системное сообщение
		$messparams[0] = "{b}".$combName."{/b}";
		$messparams[1] = "{b}".self::$unitAmount."{/b}";

		switch (self::$data['PS'])
		{
			case 'PS_BY_UNIT_SCIENTIIFIC':
				$message = 'ADD_SCIENTIST_TO_COMB';
				break;
			case 'PS_BY_UNIT_ARCHEOLOG':
				$message = 'ADD_ARCHEOLOGIST_TO_COMB';
				break;
			default:
				return false;
		}
		
		// Получам userinstance игрока		
		$userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);
		assert($userInstanse > 0);
		
		DE_SystemMessages::AddMessageFromMessageCompiler(GB_TypeFactory::CreateBase('GB_Date', time()), $userInstanse, $message, $messparams);
					
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, self::$data['PS'], self::$unitAmount, $WI);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', self::$data['PS'], self::$data['UNIT_PRICE']*self::$unitAmount, self::$data['CREDIT'], "World: ".self::$worldName.", Comb: ".$combName.", Amount: ".self::$unitAmount))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		//Завершаем транзакцию
		$_SESSION['dbId'] = self::$dbId;
		GB_SingletonObjectStorage::CommitAllCommitable();
		
		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&worldname=".self::$worldName.'&amount='.self::$unitAmount.'&cobm_name='.$combName);
		return true;
	}
	
	private static function _ShowSuccessful ()
	{				
		self::$data['MODE'] = 'SUCCESSFUL';
		self::$data['WORLD_NAME'] = $_REQUEST['worldname'];
		self::$data['UNIT_AMOUNT'] = $_REQUEST['amount'];
		self::$data['COMB_NAME'] = $_REQUEST['cobm_name'];
	}
	
	static private function _GetCombIdByUID()
	{
		$common = self::getCommon();
		
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
				ui.id_user = ".self::$user->getID()."
				AND
     			r.id_world = ".self::$worldId."
     		order by r.ringnumber, cc.complexnumber, c.combnumber
			";
		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	private  static function _GetMaxAndCurUnitAmount($id_comb)
	{
		assert(is_int(self::$worldId) && self::$worldId > 0);
		assert(is_int($id_comb) && $id_comb > 0);
		assert(!empty(self::$unit));
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
                  u.username,
                  ui.id_userinstance,
                  ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                  co.id_comb,
                  cn.combname,
                  (
	                 SELECT 
	                  		chre.value 
	                 FROM  
	                  		ds_combhasresource chre                
	                 LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
	                 LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
	                 LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
	                 WHERE 
	                 	chre.id_comb = co.id_comb
		                 AND  
		                 attrtype.typename = 'MAX_AMOUNT'
		                 AND 
		                 attrname.attributename = '".self::$unit."'
                  ) AS max_amount,
                  
                  (
	                 SELECT 
	                 	chre.value 
	                 FROM  
	                 	ds_combhasresource chre                
	                 LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
	                 LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
	                 LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
	                 WHERE 
	                 	 chre.id_comb = co.id_comb
		                 AND  
		                 attrtype.typename = 'CURRENT_AMOUNT'
		                 AND 
		                 attrname.attributename = '".self::$unit."'
                 ) AS cur_amount,
                  
                (
	                 SELECT 
	                 	chre.value 
	                 FROM  
	                 	ds_combhasresource chre                
	                 LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
	                 LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
	                 LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
	                 WHERE 
	                 	 chre.id_comb = co.id_comb
		                 AND  
		                 attrtype.typename = 'AWAY_AMOUNT'
		                 AND 
		                 attrname.attributename = '".self::$unit."'
                  ) AS away_amount
            FROM
                 ds_user u
                 LEFT JOIN ds_userinstance ui ON ( u.id_user = ui.id_user )
                 LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
                 LEFT JOIN ds_combname cn ON ( co.id_comb = cn.id_comb )
                 LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                 LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
            
            WHERE
               u.username = '".self::$user->getName()."'
               AND 
               ui.id_world = '".self::$worldId."'
               AND
               co.id_comb = '".$id_comb."'
            ORDER BY
                ri.ringnumber,
                cc.complexnumber,
                co.combnumber 
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));

		if (count($res) != 1) {
			return false;
		} 
		
		return $res;
	}
	
	private  static function _getUnitInTransfer()
	{
		assert(is_int(self::$worldId) && self::$worldId > 0);
		assert(is_int(self::$id_comb) && self::$id_comb > 0);
		assert(!empty(self::$unit));
		
		$common = self::getCommon();
		
		$sql = "
			SELECT 
				COALESCE(
					sum(
						(SELECT 
								value 
							FROM 
								ds_eventhasoption eho
							LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
							WHERE 
								eho.id_eventqueue = eq.id_eventqueue
								 AND
								eo.optionname = 'Amount')
					), 0) as sum
			FROM 
				ds_eventqueue eq
			LEFT JOIN ds_event e ON (e.id_event = eq.id_event)
			LEFT JOIN ds_eventparticipant ep ON (eq.id_eventqueue = ep.id_eventqueue)
			LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = ep.id_userinstance)
			WHERE
					ui.id_user = ".self::$user->getID()."
				AND
					ui.id_world = ".self::$worldId."
				AND 
					e.eventname = 'TRANSFER_RESOURCE'
				AND
					-- нужный юнит
						(SELECT 
							value 
						FROM 
							ds_eventhasoption eho
						LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
						WHERE 
							eho.id_eventqueue = eq.id_eventqueue
							 AND
							eo.optionname = 'ID_Article' 
						) = (
							SELECT 
		                    	id_attributename
		                     FROM  
		                        ds_attributename 
		                     WHERE 
		                         attributename = '".self::$unit."')
					AND
						(SELECT 
							value 
						FROM 
							ds_eventhasoption eho
						LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
						WHERE 
							eho.id_eventqueue = eq.id_eventqueue
							AND
							eo.optionname = 'ID_Category' 
						) = 1 
			";
		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));

		return $res[0]['sum'];
	}
	
	private  static function _UpdateUnotAmount($value)
	{
		assert(is_int(self::$id_comb) && self::$id_comb > 0);
		assert(!empty(self::$unit));
		assert($value > 0);
		
		$common = self::getCommon();
		
		$sql = "
			UPDATE 
				ds_combhasresource
			SET 
				value = ".$value."
			WHERE 
				id_comb = ".self::$id_comb."
				 AND
				id_attribute =
					(
						SELECT 
							id_attribute 
						FROM 
							ds_attribute attr
						LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
						LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
						WHERE
							attrname.attributename = '".self::$unit."'
							 AND
							attrtype.typename = 'CURRENT_AMOUNT')
			";
		$res = $common->executeQuery($sql);		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static private function _getParams()
	{
		
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('count', self::$data))
		{
			self::$unitAmount = (int)self::$data['count'];
		}
		
		if(key_exists('id_comb', self::$data))
		{
			self::$id_comb = (int)self::$data['id_comb'];
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
<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecDeleteUniqueBuilding extends UC_PaymentServiceBase
{
	static private $combName;
	
	static private $combId;
	
	static private $idAttachedBuilding;
	
	static private $buildingName;
	
	static private $idBuilding;
	
	static private $allowBuilding = array(
		// Храм созерцателей облаков
		46,
		// Храм штурмовиков
		45,
		// Силовой щит
		66,
		// Посольство
		14,
		// Внутрикольцевой коннектор торговли
		31,
		// Локальный коннектор торговли
		23,
		// Межкольцевой коннектор торговли
		20,
		// Терраформер
		//64,
		// Управление разведки
		33,
		// Центр шпионажа
		35,
		// Сканер туннельной реки
		37
	);
	
	public function Exec()
	{
		self::_getParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();
		
		switch(self::$action)
		{
			case 'show_second_menu':
					self::_ShowSecondMenu();
				break;	
				
			case 'building_list':
					self::_ShowBuildingList();
				break;	
				
			case 'delete_building_confirm':
					if (!self::_ShowBuildingConfirm()) {
						self::_ShowBuildingList();
					}
				break;	
		
			case 'delete_building':
					if (!self::_DeleteBuilding()) {
						self::_ShowBuildingList();
					}
				break;	
				
			case 'change_comb_name':
				if (!self::_ChangeCombName()) 
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
	
	static private function _ShowSecondMenu()
	{
		$_SESSION['dbId'] = 0;
		
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['WORLD_NAME'] = self::$worldName;
		
		$_SESSION['dbId'] = self::$dbId;

		//Получаем список всех сот игрока в данном мире
		$userInstanse = (int)DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID(self::$user->getId(), self::$worldId);
		
		//Получаем айдишники сот игрока в этом мире
		$combs = DS_Comb::GetCombsByUserInstance($userInstanse);
		
		self::$data['COMBS'] = $combs;
		
		if (!empty(self::$combId)) {
			self::$data['COMB_ID'] = self::$combId;
		} else {
			self::$data['COMB_ID'] = $combs[0]['id_comb'];
		}

		// Если у игрока несколько сот в этом мире
		if (count($combs) > 1) {
			self::$data['MODE'] = 'DELETE_UNIQUE_CHOOSE_COMB';
			self::$data['EXEC_ACTION'] = 'building_list';
		} else {
			// Если у игрока одна сота в этом мире 
			self::$combId = (int)$combs[0]['id_comb'];
			self::$combName = $combs[0]['combname'];
			self::_ShowBuildingList();
		}
	}
	
	static private function _ShowBuildingList()
	{
		assert(is_int(self::$combId) && self::$combId > 0);
		
		// Выбираем все уникалние здания игрока
		$uniqueBuildings = self::_getPlayerUniqueBuilding();
		
		// Проходимся по всем зданиям
		foreach ($uniqueBuildings as $key => $data)
		{
			// Если здание есть в спике доступных для удаления
			if (in_array($data['id_building'], self::$allowBuilding)) {
				self::$data['BUILDINGS_TO_DELETE'][$data['celltypename_l']][] = $data;
			}
		}
		
		self::$data['COMB'] = self::$combId."_".self::$combName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['MODE'] = 'DELETE_UNIQUE_CHOOSE_BUILDING';
		self::$data['EXEC_ACTION'] = 'delete_building_confirm';
	}
	
	static private function _DeleteBuilding()
	{
		assert(is_int(self::$idAttachedBuilding) && self::$idAttachedBuilding > 0);
		assert(is_int(self::$combId) &&  self::$combId > 0);
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);	
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		// Еще раз проверяем
		if (!self::_checkDelete()) {
			self::$data['ID_ATTACHED_BUILDING'] = self::$idAttachedBuilding;		
			return false;
		}
		
		// 
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));

		if (!self::_execDelete()) {
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_DELETE_UNIQUE_BUILDING', 1, $WI);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_DELETE_UNIQUE_BUILDING', self::$data['PRICE'], self::$data['CREDIT'], "World: ".self::$worldName.", building: ".self::$buildingName." on comb: ".self::$combName))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
				
		//Рестартим вид домашней соты
		$_SESSION["dbId"] = self::$dbId;
		CR_CombView::CreateFromCombID(self::$combId)->resetBuildings(1);

		//Рестартим вид центра
		CR_CombView::CreateFromCombID(self::$combId)->resetBuildings(2);

		//Рестартим вид нейтрала
		CR_CombView::CreateFromCombID(self::$combId)->resetBuildings(3);
		
		CR_CombView::CreateFromCombID(self::$combId)->commitCacheResets();

		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&worldName=".self::$worldName."&combName=".self::$combName."&buildingName=".self::$buildingName);
		return true;
	}
	
	private static function _ShowSuccessful()
	{
		self::$data['WORLD_NAME'] = $_REQUEST['worldName'];
		self::$data['COMB_NAME'] = $_REQUEST['combName'];
		self::$data['BUILDING_NAME'] = $_REQUEST['buildingName'];
		self::$data['MODE'] = 'SUCCESSFUL';
	}
	
	static private function _ShowBuildingConfirm()
	{
		if (self::$idAttachedBuilding == 0) {
			self::$data['MSG'] = 'CHOOSE_BUILDING';
			return false;
		}
		
		assert(is_int(self::$idAttachedBuilding) && self::$idAttachedBuilding > 0);
		assert(is_int(self::$idBuilding) && self::$idBuilding > 0);
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		if (!self::_checkDelete()) {
			self::$data['ID_ATTACHED_BUILDING'] = self::$idAttachedBuilding;			
			return false;
		}
		
		self::$data['BUILDING_NAME'] = self::$buildingName;
		self::$data['BUILDING'] = self::$idAttachedBuilding."_".self::$idBuilding."_".self::$buildingName;
		self::$data['COMB_NAME'] = self::$combName;
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['COMB'] = self::$combId."_".self::$combName;
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		self::$data['WORLD_NAME'] = self::$worldName;
		self::$data['MODE'] = 'DELETE_UNIQUE_CONFIRM';
		self::$data['EXEC_ACTION'] = 'delete_building';
		return true;
	}
	
	private static function _getPlayerUniqueBuilding()
	{				
		assert(is_int(self::$combId) && self::$combId > 0);
		assert(is_int(self::$dbId) && self::$dbId >= 0);
		assert(is_int(self::$worldId) && self::$worldId > 0);
		
		$common = DS_Common::CreateForCurentDB(0);

		$common->setDbId(self::$dbId);

		$sql = "SELECT
				u.username,
				cn.combname,
				attb.id_attachedbuilding,
				ltl.text AS celltypename_l,
				( ri.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber ) as coords,
				(SELECT ltl3.text FROM ds_localizedtextlanguage as ltl3 where ltl3.id_language = ".constant('DEFAULT_LANGUAGE')." AND bcbr.id_localizedname = ltl3.id_localizedtext) AS buildingname,
				--  (SELECT ltl2.text FROM ds_localizedtextlanguage as ltl2 where ltl2.id_language = 3 AND bcbr.id_localizeddescription = ltl2.id_localizedtext) AS buildingdescription,
				ct.celltypename,
				b.id_building
			FROM
				ds_user u
				INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
				INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
				LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
				LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
				INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
				INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
				INNER JOIN ds_building b ON (attb.id_building = b.id_building)
				INNER JOIN public.ds_buildingcanbuildbyrace bcbr ON (b.id_building = bcbr.id_building) AND
				(ui.id_race = bcbr.id_race)
				INNER JOIN ds_cell cell ON (attb.id_cell = cell.id_cell)
				INNER JOIN ds_celltype ct ON (cell.id_celltype = ct.id_celltype)
				INNER JOIN ds_localizedtextlanguage ltl ON (ct.id_localizedname = ltl.id_localizedtext)
			WHERE
				(u.id_user = ".self::$user->getId().") AND
				(b.isunique = 't') AND
				(ltl.id_language = ".constant('DEFAULT_LANGUAGE').")
				AND
				ui.id_world = ".self::$worldId."
				AND
				c.id_comb = ".self::$combId."
			ORDER BY
				cell.id_cell";
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));

		return $res;
	}
	
	private static function _execDelete()
	{				
		assert(is_int(self::$idAttachedBuilding) && self::$idAttachedBuilding > 0);
		assert(is_int(self::$dbId) && self::$dbId >= 0);
		assert(is_int(self::$worldId) && self::$worldId > 0);

		$common = DS_Common::CreateForCurentDB(0);
		$common->setDbId(self::$dbId);

		$sql = "
									SELECT
      									ab.id_comb,
      									bhts.id_tradescope
									FROM
    									ds_attachedbuilding ab,
    									ds_building b,
    									ds_buildinghastradescope bhts
									WHERE
     									ab.id_attachedbuilding = ".self::$idAttachedBuilding."
     									AND ab.id_building = b.id_building
     									AND b.id_building = bhts.id_building";

		$res = $common->getResultArray('', $sql, '', '', '');
		if (!empty($common->errormsg)) {
			return false;
		}

		if(sizeof($res)>0)
		{
			foreach ($res as $k=>$v)
			{
				$sql = "
											DELETE FROM
           											ds_comballowedtradescope
												WHERE
     												id_comb = ".$v['id_comb']."
     												AND id_tradescope = ".$v['id_tradescope'];
				$res = $common->executeQuery($sql);
				if(!$res)
				{
					return false;
				}
			}
		}

		$sql = "
						DELETE
							FROM
								ds_attachedbuilding 
							WHERE id_attachedbuilding = ".self::$idAttachedBuilding;

		$res = $common->executeQuery( $sql );
		if(!$res)
		{
			return false;
		}

		return true;
	}
	
	private static function _checkDelete()
	{				
		assert(is_int(self::$idBuilding) && self::$idBuilding > 0);
		assert(is_int(self::$idAttachedBuilding) && self::$idAttachedBuilding > 0);
		assert(is_int(self::$dbId) && self::$dbId >= 0);
		assert(is_int(self::$worldId) && self::$worldId > 0);
		assert(is_int(self::$combId) && self::$combId > 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDbId(self::$dbId);

		// Смотрим, принадлежит ли игроку это здание
		$sql = "
						SELECT
							*	
						FROM
							ds_user u
							INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
							INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
							INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
						WHERE
							u.username = '".self::$user->getName()."'
							AND
							ui.id_world = ".self::$worldId."
							AND
						  attb.id_attachedbuilding = ".self::$idAttachedBuilding;
		$res = $common->getQueryArray($sql);
		assert(empty($common->errormsg));

		if(count($res) == 0)
		{
			self::$data['MSG'] = 'NOT_YOU_BUILDING';
			return false;
		}
		assert(count($res) == 1);

		// Смотрим, не строится ли сейчас это здание
		if ($res[0]['underconstruction'] == 't' || $res[0]['inactivation'] == 't' || $res[0]['inupgrade'] == 't') {
			self::$data['MSG'] = 'BUILDING_IN_CONSTRACTION';
			return false;
		}

		switch (self::$idBuilding)
		{
			// Силовой щит
			case '66':
				// Проверяем, не включен ли он
				$sql = "
						SELECT
							ps.end_date,
							ps.start_date,
							psn.statusname
						FROM
							ds_powershild_current_status AS ps
							INNER JOIN ds_powershild_status_name AS psn USING (id_powershildstatusname)
					    	INNER JOIN ds_userinstance AS ui USING (id_userinstance)
					    	INNER JOIN ds_user AS u ON (ui.id_user = u.id_user)
						WHERE
							ui.id_world = ".self::$worldId."
			    			AND
			    			u.username = '".self::$user->getName()."'
			    			AND
			    			ps.end_date > now()
						";
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));
				assert(count($res) <= 1);

				if(count($res) == 1 && $res[0]['statusname'] == 'WORKING')
				{
					self::$data['MSG'] = 'ACTIVE_POWERSHILD';
					return false;
				} 
				return true;
				
			// Храм созерцателей облаков
			case '46':
				// Проверяем, не принята ли религия созерцателей облаков
				$sql = "
						SELECT
		    			r.religionname
						FROM
		    				ds_user AS u				
		    				INNER JOIN ds_userinstance AS ui ON (ui.id_user = u.id_user)
		    				INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
		    				LEFT JOIN ds_userhasreligion AS uhr ON (uhr.id_userinstance = ui.id_userinstance)
		    				LEFT JOIN ds_religion AS r ON (r.id_religion = uhr.id_religion)
		    			WHERE
		    				w.id_world = ".self::$worldId."
		    				AND
		    				u.username = '".self::$user->getName()."'
						";
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));
				assert(count($res) == 1);

				if($res[0]['religionname'] == 'RELIGI_WOLKENENDER')
				{
					// Смотрим, нет ли уигрока белее одного здания этой религии
					$sql = "
						SELECT
							b.id_building	
						FROM
							ds_user u
							INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
							INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
							INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
							INNER JOIN ds_building b ON (attb.id_building = b.id_building)
						WHERE
							u.username = '".self::$user->getName()."'
							AND
							ui.id_world = ".self::$worldId."
							AND
							b.id_building = 46";
					$res = $common->getQueryArray($sql);
					assert(empty($common->errormsg));
					
					if(count($res) > 1)
					{
						return true;
					} 
					self::$data['MSG'] = 'RELIGI_WOLKENENDER';
					return false;
				} 
				return true;
				
			// Храм штурмовиков
			case '45':
				// Проверяем, не принята ли религия штурмовиков
				$sql = "
						SELECT
		    			r.religionname
						FROM
		    				ds_user AS u				
		    				INNER JOIN ds_userinstance AS ui ON (ui.id_user = u.id_user)
		    				INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
		    				LEFT JOIN ds_userhasreligion AS uhr ON (uhr.id_userinstance = ui.id_userinstance)
		    				LEFT JOIN ds_religion AS r ON (r.id_religion = uhr.id_religion)
		    			WHERE
		    				w.id_world = ".self::$worldId."
		    				AND
		    				u.username = '".self::$user->getName()."'
						";
			
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));
				assert(count($res) == 1);

				if($res[0]['religionname'] == 'RELIGI_WOLKENSTURMER')
				{
					// Смотрим, нет ли уигрока белее одного здания
					$sql = "
						SELECT
							b.id_building	
						FROM
							ds_user u
							INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
							INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
							INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
							INNER JOIN ds_building b ON (attb.id_building = b.id_building)
						WHERE
							u.username = '".self::$user->getName()."'
							AND
							ui.id_world = ".self::$worldId."
							AND
							b.id_building = 45";
			
					$res = $common->getQueryArray($sql);
					assert(empty($common->errormsg));
					
					if(count($res) > 1)
					{
						return true;
					} 
					self::$data['MSG'] = 'RELIGI_WOLKENSTURMER';
					return false;
				} 
				return true;
				
			// Посольство
			case '14':
				// Смотрим, не состоит ли игрок в альянсе
				$sql = "
						SELECT
		    				a.id_alliance 
		    			FROM 
		    				ds_userhasalliance uha
						LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = uha.id_userinstance)
						LEFT JOIN ds_alliance a ON (uha.id_alliance = a.id_alliance)
						LEFT JOIN ds_user u USING (id_user)
		    			WHERE
		    				ui.id_world = ".self::$worldId."
		    				AND
		    				u.username = '".self::$user->getName()."'
						";
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));

				if(count($res) > 0)
				{
					self::$data['MSG'] = 'IN_ALLIANCE';
					return false;
				} 
				return true;
				
			// Локальный коннектор торговли
			case '23':
				// Смотрим, чтобы у игрока не было Внутрикольцевого и Межкольцевого коннектора торговли
				$sql = "
						SELECT
							b.id_building	
						FROM
							ds_user u
							INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
							INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
							INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
							INNER JOIN ds_building b ON (attb.id_building = b.id_building)
						WHERE
							u.username = '".self::$user->getName()."'
							 AND
							ui.id_world = ".self::$worldId."
							 AND
							(b.id_building = 31
							OR
							b.id_building = 20
							)
							 AND
							 c.id_comb = ".self::$combId."
							";
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));

				if(count($res) > 0)
				{
					self::$data['MSG'] = 'RING_TRADE_CONNECTOR_OR_INTERRING_TRADE_CONNECTOR';
					return false;				
				}
				return true;
				
			// Внутрикольцевой коннектор торговли
			case '31':
				// Смотрим, чтобы у игрока не было Межкольцевого коннектора торговли
				$sql = "
						SELECT
							b.id_building	
						FROM
							ds_user u
							INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
							INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
							INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
							INNER JOIN ds_building b ON (attb.id_building = b.id_building)
						WHERE
							u.username = '".self::$user->getName()."'
							 AND
							ui.id_world = ".self::$worldId."
							 AND
							b.id_building = 20
							 AND
							 c.id_comb = ".self::$combId."
							";
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));


				if(count($res) > 0)
				{					
					self::$data['MSG'] = 'INTERRING_TRADE_CONNECTOR';
					return false;
				}
				return true;
				
			// Терраформер
			case '64':
				// Проверяем, работает ли он
				$sql = "
					SELECT
							t.id_eventqueue
						FROM
							ds_user u
							INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
							INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)						
							INNER JOIN ds_cell cell ON (c.id_comb = cell.id_comb)
						INNER JOIN ds_terraformingbindeventqueue t ON (t.id_cell = cell.id_cell)
						WHERE
							u.username = '".self::$user->getName()."'
							 AND
							ui.id_world = ".self::$worldId."
							 AND
							 c.id_comb = ".self::$combId;
				
				$res = $common->getQueryArray($sql);
				assert(empty($common->errormsg));
			
				if(count($res) > 0)
				{
					self::$data['MSG'] = 'ACTIVE_TERRAFORMER';
					return false;
				}
				return true;
				
			// Межкольцевой коннектор торговли
			case '20':
				// Можно удалять всегда
				return true;
		
			// Управление разведки
			case '33':
				// Можно удалять всегда
				return true;
		
			// Центр шпионажа
			case '35':
				// Можно удалять всегда
				return true;
				
			// Сканер туннельной реки
			case '37':
				// Можно удалять всегда
				return true;
		
			default:
				return false;
		}		
	}
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('comb', self::$data))
		{
			$strpos = strpos(self::$data['comb'], '_');
			assert($strpos > 0);
			
			self::$combId = (int)substr(self::$data['comb'], 0, $strpos);
			self::$combName = substr(self::$data['comb'], $strpos+1);
			
			assert(self::$combId > 0);
			assert(!empty(self::$combName));
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
		
		if(key_exists('building', self::$data))
		{
			$bildArr = explode('_', self::$data['building']);

			self::$idAttachedBuilding = (int)$bildArr[0];
			//assert(self::$idAttachedBuilding > 0);
			
			self::$idBuilding = (int)$bildArr[1];
			//assert(self::$idBuilding > 0);
				
			self::$buildingName = $bildArr[2];
			//assert(!empty(self::$buildingName));
		}
	}
}

?>
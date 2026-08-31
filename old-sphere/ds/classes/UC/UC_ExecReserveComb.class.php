<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/02/16 10:42:23 $';


class UC_ExecReserveComb extends UC_PaymentServiceBase
{
	static private $ring;
	
	static private $complex;
	
	static private $comb;
	
	static private $common;
	
	public function Exec()
	{
		self::_getParams();
		
		$_SESSION['dbId'] = 0;
		
		//получаем цену
		self::_GetPrice();

		switch(self::$action)
		{
			case 'reserve_comb':
				if (!self::_ReserveComb()) 
				{
					self::_ShowSecondMenu();
				}
				break;	
			
			case 'show_second_menu':
					self::_ShowSecondMenu();
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
		
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}
		
		//Получаем список миров игрока
		$userworlds = self::_GetAllWorldsList();
	
		self::$data['USER_WORLDS'] = $userworlds;
	}
	
	static private function _ShowSecondMenu()
	{
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);
		
		$_SESSION['dbId'] = 0;
		
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		self::$data['WORLD'] = self::$dbId.'_'.self::$worldId.'_'.self::$worldName;
		
		self::$data['MODE'] = 'RESERVE_COMB_INDEX';
		self::$data['EXEC_ACTION'] = 'reserve_comb';
		
		if (!empty(self::$ring)) {
			self::$data['ring'] = self::$ring;
		}
		
		if (!empty(self::$complex)) {
			self::$data['complex'] = self::$complex;
		}
		
		if (!empty(self::$comb)) {
			self::$data['comb'] = self::$comb;
		}
		if (!empty(self::$worldId)) {
			self::$data['CHOOSEN_WORLD'] = self::$dbId."_".self::$worldId."_".self::$worldName;
		} else {
			self::$data['CHOOSEN_WORLD'] = '';
		}
		
		$_SESSION['dbId'] = self::$dbId;
		
		//Смотрим, не зарезервировал ли игрок уже себе соту в этом мире
		$reservedComb = self::_IsUserAlreadyReserveComb();
		if (count($reservedComb) > 0) {
			//Получаем координаты соты
			$combData = self::_GetCombData($reservedComb[0]['id_comb']);
			
			self::$data['RESERVED_COMB'] = $combData['ringnumber'].'.'.$combData['complexnumber'].'.'.$combData['combnumber'];
			self::$data['WORLD_NAME'] = self::$worldName;
		} 
	}
	
	static private function _ReserveComb()
	{
		//Смотрим достаточно ли денег у игрока
		if (!self::_CheckEnoughMoney())
		{
			return false;
		}
		
		assert(self::$dbId >= 0);
		assert(self::$worldId > 0);		
		
		$_SESSION['dbId'] = self::$dbId;
		$_SESSION['world'] = self::$worldId;
		
		// Получаем World Identifier мира (для лога)
		$WI = DE_World::CreateFromID(self::$worldId)->getShortName();
		assert(!empty($WI));
		
		//Проверка на гейм даун
		if(GameDown(self::$dbId, self::$worldId))
		{
			header('Location: index_down.html');
			exit;
		}

		if (empty(self::$ring)) {
			self::$data['MSG'] = 'EMPTY_RING';
			return false;
		}
		
		if (empty(self::$complex)) {
			self::$data['MSG'] = 'EMPTY_COMPLEX';
			return false;
		}
		
		if (empty(self::$comb)) {
			self::$data['MSG'] = 'EMPTY_COMB';
			return false;
		}
		
		if (!self::_IsCombExist(self::$dbId, self::$worldId, self::$ring, self::$complex, self::$comb)) {
			self::$data['MSG'] = 'CONB_NOT_EXIST';
			return false;
		}

		if (!self::_CheckIfNewCoordsFree()) {		
			self::$data['MSG'] = 'CONB_NOT_FREE';
			return false;
		}
		
		/*if (!self::_CheckIfUserCanRegInWorld()) {
			self::$data['MSG'] = 'YOU_CANT_REG_IN_THIS_WORLD';
			return false;
		}*/
				
		self::$common = self::getCommon();

		$id_comb = self::_GetCombIDByCordinates();
		 
		if (!self::_Reserve($id_comb))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}
		
		$_SESSION['dbId'] = 0;
		
		//Снимаем деньги
		UC_PaymentServiceMoneyOperation::GetMoneyForOperation(self::$user, 'PS_RESERVE_COMB', 1, $WI);
		
		//Логиреум
		if (!self::_Log('t', 'f', 't', 'f', 'PS_RESERVE_COMB', self::$data['PRICE'], self::$data['CREDIT'], "Reserved coords:  ".self::$ring.".".self::$complex.".".self::$comb.", world:".self::$worldName.""))
		{
			self::$data['MSG'] = 'ERROR';
			return false;
		}

		ME_EndDBTransactions::Exec();
		header("Location: user_cabinet.php?action=payment_services&ps_action=".self::$data['PS']."&exec_action=successful&coords=".self::$ring.".".self::$complex.".".self::$comb."&worldName=".self::$worldName);
		return true;
	}
	
	private static function _ShowSuccessful ()
	{				
		self::$data['COORDS'] = $_REQUEST['coords'];
		self::$data['WORLD'] = $_REQUEST['worldName'];
		self::$data['MODE'] = 'SUCCESSFUL';
		self::$data['WORLD_NAME'] = $_REQUEST['worldName'];

	}
	
	static private function _getParams()
	{
		if(key_exists('exec_action', self::$data))
		{
			self::$action = self::$data['exec_action'];
		}
		
		if(key_exists('ring', self::$data))
		{
			self::$ring = self::$data['ring'];
		}
		
		if(key_exists('complex', self::$data))
		{
			self::$complex = self::$data['complex'];
		}
		
		if(key_exists('comb', self::$data))
		{
			self::$comb = self::$data['comb'];
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
	
	static private function _GetAllWorldsList()
	{
		$common = DS_Common::CreateForCurentDB(0);

		$out = array();

		foreach($GLOBALS['dbWorldAccess'] as $dbid => $dbData)
		{
			$common->setDbId((int)$dbid);

			$sql = "SELECT id_world, worldname FROM ds_world";
			$res = $common->getResultArray('', $sql, '', '', '');
			assert(empty($common->errormsg));

			foreach($res as $k=>$v)
			{
				$v['dbId'] = $dbid;
				$out[] = $v;
			}
		}

		return $out;
	}
	
	static private function _IsCombExist($dbId, $worldId, $ringnumber, $complexnumber, $combnumber)
	{
		assert($dbId >= 0);
		assert($worldId > 0);
		assert($ringnumber > 0);
		assert($complexnumber > 0);
		assert($combnumber > 0);
		
		$common = DS_Common::CreateForCurentDB($dbId);
		
		$sql = "SELECT co.id_comb
				FROM
				     ds_comb as co
				INNER JOIN ds_combcomplex as cc ON (co.id_combcomplex = cc.id_combcomplex)
				INNER JOIN ds_ring as ri ON (cc.id_ring = ri.id_ring)
				WHERE
				co.combnumber = $combnumber
				AND
				cc.complexnumber = $complexnumber
				AND
				ri.ringnumber = $ringnumber
				AND 
				ri.id_world = $worldId";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1 || count($res) == 0);
		
		if (!$res)
		{
			return false;
		} else {
			return true;
		}
	}
	
	static private function _CheckIfUserCanRegInWorld()
	{
		$isProfi = DS_Premium::checkIfUserProfi(self::$user->getId());
		
		$common = DS_Common::CreateForCurentDB(0);
		GE_SearchStart::SetCommon($common);
		
		if(!GE_LogicCanRegInWorld::Test(self::$user, $isProfi, self::$dbId, self::$worldId))
		{
			return false;
		}
		
		return true;
	}
	
	
	static private function _GetCombIDByCordinates()
	{
		$sql = "SELECT
		       			c.id_comb
		 			FROM
		     			(ds_comb AS c INNER JOIN ds_combcomplex AS cc ON ( c.id_combcomplex = cc.id_combcomplex )
		              		INNER JOIN ds_ring AS r ON ( cc.id_ring = r.id_ring ))
					WHERE
						r.id_world = ".self::$worldId." AND
		     			r.ringnumber = ".self::$ring." AND
		     			cc.complexnumber = ".self::$complex." AND
		     			c.combnumber = ".self::$comb;
		
		$res = self::$common->getResultArray("",$sql,"","","NO_CACHE");	
		
		if(!$res)
		{
			return false;
		}

		return $res[0]['id_comb'];
	}
	
	
	static private function _CheckIfNewCoordsFree()
	{	
		$common = self::getCommon();
					
		$sql = "SELECT
				    count(*)
				FROM
				    ds_comb c,
				    ds_combcomplex cc,
				    ds_ring r
				WHERE
				    c.id_userinstance is NULL
				    AND c.combnumber = ".self::$comb."
				    AND c.id_combcomplex = cc.id_combcomplex
				    AND cc.complexnumber = ".self::$complex."
				    AND cc.id_ring = r.id_ring
				    AND r.ringnumber = ".self::$ring."
				    AND r.id_world = ".self::$worldId."
                    AND
			 			NOT EXISTS 
			 			(
			 				SELECT id_comb
			 				FROM ds_combname
			 				WHERE id_comb = c.id_comb
	 					)
	 				AND
     					NOT EXISTS 
     					(
		         			SELECT id_comb
				 			FROM ds_reservedcomb
				 			WHERE id_comb = c.id_comb
     					)";
		
		$res = $common->getResultArray("",$sql,"","","NO_CACHE");	
		if((int)$res[0]['count'] != 1)
		{
			return false;
		}
		
		return true;		
	}
	
	static private function _Reserve($id_comb)
	{
		assert($id_comb > 0);
		
		//Смотрим, не зарезервировал ли игрок уже себе соту в этом мире
		$reservedComb = self::_IsUserAlreadyReserveComb();
		if (count($reservedComb) > 0) {
			$sql = "UPDATE ds_reservedcomb SET id_comb = ".$id_comb.", add_date = now() WHERE username = '".self::$user->getName()."'";
		} else {
			$sql = "insert into 
							ds_reservedcomb
							(username, id_comb, comment)
						values 
							('".self::$user->getName()."', ".$id_comb.", 'payment services')";
		}

		self::$common->executeQuery($sql);
		
		if(!empty(self::$common->errormsg))
		{
			return false;
		}
		
		return true;
	}

	static private function _IsUserAlreadyReserveComb()
	{
		$common = self::getCommon();
		
		$sql = "SELECT *
						FROM 
							ds_reservedcomb
						WHERE	
							username = '".self::$user->getName()."'";
		$res = $common->getResultArray("",$sql,"","","NO_CACHE");	
		assert(empty(self::$common->errormsg));		
		
		return $res;
	}
	
	static private function _GetCombData($combId)
	{
		$common = self::getCommon();
		assert($combId > 0);
		
		$sql = "
			SELECT
				cc.complexnumber,
				r.ringnumber,
				c.combnumber
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
			WHERE
				c.id_comb = ".$combId."				
			";
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res[0];
	}
}

?>
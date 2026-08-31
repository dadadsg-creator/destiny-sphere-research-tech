<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_UserInstanceEmptyArmys extends MP_Patch
{
	private $userInstanceData;

	public function __construct($userInstanceData)
	{
		assert(is_array($userInstanceData) && count($userInstanceData) > 0);
		$this->userInstanceData = $userInstanceData;
	}

	public function patch()
	{
		$sql = "SELECT *
				FROM ds_unitcontainer AS uc
				WHERE
     				uc.id_unitcontainertype = 1 -- ARMY
     				AND
     				uc.isdefault = false
     				AND
     				NOT EXISTS
     				(
         				SELECT *
         				FROM ds_unitcontainerhasunit AS uchu
         				WHERE uchu.id_army = uc.id_unitcontainer
     				)
     				AND
     				uc.id_userinstance = ".$this->userInstanceData['id_userinstance'];
		$rs =  self::$common->getResultArray( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		if(sizeof($rs) === 0)
		{
			return true;
		}
		
		foreach($rs as $k=>$v)
		{
			$str =  "		".__CLASS__.": ERROR! Find empty army with name '".$v['unitcontainername']."'";
			self::_out($str);
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				$this->_deleteArmy($v);
			}
		}
		

		return true;			
	}

	private function _deleteArmy($armyData)
	{
		self::$common->executeQuery('BEGIN');
		assert(empty(self::$common->errormsg));
			
		$sql = "DELETE FROM ds_unitcontaineraction WHERE id_unitcontainer = ".$armyData['id_unitcontainer'];
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "DELETE FROM ds_unitcontainerbindeventqueue WHERE id_unitcontainer = ".$armyData['id_unitcontainer'];
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "DELETE FROM ds_unitcontainer WHERE id_unitcontainer = ".$armyData['id_unitcontainer'];
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		self::$common->executeQuery('COMMIT');
		assert(empty(self::$common->errormsg));
						
		return true;
	}	
}

?>
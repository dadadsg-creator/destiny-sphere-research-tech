<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_CombBindEvents extends MP_CombBase
{
	
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		$sql = "SELECT *
				FROM ds_combbindeventqueue as cbeq
				WHERE 
					cbeq.id_comb = ".(int)$this->combID."
					AND
					NOT EXISTS (
						SELECT *
						FROM ds_eventqueue
						WHERE id_eventqueue = cbeq.id_eventqueue
					)";
		$rs =  self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		if(count($rs) == 0)
		{
			return;
		}
		
		$str =  "		".__CLASS__.": ERROR! Find ".count($rs)." not existed events binded to comb";
		self::_out($str);
		
		if(self::$onlyValidate)
		{
			$str =  "		ONLY VALIDATE!";
			self::_out($str);
		}else{
			foreach($rs as $k=>$v)
			{
				$sql = "DELETE FROM ds_combbindeventqueue
						WHERE id_eventqueue = ".$v['id_eventqueue'];
				$rs =  self::$common->getResultArray("", $sql, "", "", "");
				assert(empty(self::$common->errormsg));				
			}
		}
	}		
}

?>
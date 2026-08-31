<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_TurnCalc extends DS_Base
{
	static public function GetByID($id)
	{		
		settype($id, 'integer');
		assert($id > 0);
		
		$common = self::getCommon();
		
		$sql = "";
		
		$res = $common->getResultArray(self::FormMark($id), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		if(!$res) 
		{			
			return false;
		}else{
			return $res[0];
		}
	}//END OF METHOD
}


?>
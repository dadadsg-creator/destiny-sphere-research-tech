<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_CheckPeace extends MP_CombBase
{				
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		$sql = "DELETE FROM ds_peace_user WHERE peace_end_date < NOW()";

		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
		
}

?>
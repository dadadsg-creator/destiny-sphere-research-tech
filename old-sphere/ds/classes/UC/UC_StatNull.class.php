<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_StatNull extends UC_StatBase
{
	function __construct($data)
	{
		parent::__construct($data);
	}
	
	public function formStat()
	{
		return false;
	}
}

?>
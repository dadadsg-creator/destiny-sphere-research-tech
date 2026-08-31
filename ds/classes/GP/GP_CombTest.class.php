<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_CombTest extends GP_Patch
{
	private $combID;
	
	public function __construct($combID)
	{
		$this->combID = $combID;
	}	
	
	public function patch()
	{
		if($this->_isValid())
		{
			self::_out("    - Test result OK!");
			return true;
		}else{
			self::_out("    - Test result BAD!");	
			$this->_corect();
			return true;		
		}
	}
	
	public function _isValid()
	{
		return true;
	}
	
	public function _corect()
	{	
		self::_out("      TRY CORRECT");
		self::_out("      CORRECTED");
	}
}

?>
<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Тетсовый класс для теста сиситемы.
 *
 */

class MP_CombTest extends MP_Patch
{	
	
	public function __construct($combID)
	{
		parent::__construct($combData);
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
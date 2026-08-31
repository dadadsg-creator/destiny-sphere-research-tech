<?php $CVS_Version = '$Revision$ ; $Date$';


class GB_Float extends GB_DataType 
{
	
	static protected $className = 'GB_Float';
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		assert(is_float($value) || is_double($value));
	}	
}

?>
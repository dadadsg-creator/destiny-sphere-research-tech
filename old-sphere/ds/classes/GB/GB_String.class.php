<?php $CVS_Version = '$Revision$ ; $Date$';


class GB_String extends GB_DataType 
{
	static protected $className = 'GB_String';
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		assert(is_string($value));
	}	
}

?>
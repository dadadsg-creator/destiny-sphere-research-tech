<?php $CVS_Version = '$Revision$ ; $Date$';


class GB_StringNotEmpty extends GB_String  
{
	static protected $className = 'GB_StringNotEmpty';
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		assert(!empty($value));
	}	
}

?>
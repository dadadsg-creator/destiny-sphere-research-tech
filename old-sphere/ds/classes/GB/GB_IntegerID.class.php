<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_IntegerID extends GB_Integer
{

	static protected $className = 'GB_IntegerID';
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);		
		assert($value>0);
	}	
}
?>
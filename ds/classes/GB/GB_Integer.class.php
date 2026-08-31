<?php $CVS_Version = '$Revision$ ; $Date$';


class GB_Integer extends GB_DataType
{	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);	
		assert(is_int($value) || is_double($value));
	}	
	
	static public function Format($value, $delimiter = '')
	{
		assert(is_numeric($value));
		return number_format($value, null, '.', $delimiter);
	}
}

?>
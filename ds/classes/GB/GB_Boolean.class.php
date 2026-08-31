<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс тип булеан
 *
 */
class GB_Boolean extends GB_DataType 
{
	
	static protected $className = 'GB_Boolean';
		
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		
		switch($value)
		{
			case "t":
				$value = true;
				break;
			case "f":
				$value = false;
				break;
		};
		
		assert(is_bool($value));
	}	
	
	public function getPgFormat()
	{
		if($this->value)
		{
			return 't';
		}else{
			return 'f';
		}
	}
}

?>
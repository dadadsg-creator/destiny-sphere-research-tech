<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для описания кода типа учаситника в бою. Атакер или дефендер.
 */


class GB_BattleParticipantTypeCode extends GB_String 
{
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		assert($value == 'A' || $value == 'D'  || $value == '');
	}	
	
	public function getInversValue()
	{
		if($this->value == 'A')
		{
			return 'D';
		}else{
			return 'A';
		}
	}
}


?>
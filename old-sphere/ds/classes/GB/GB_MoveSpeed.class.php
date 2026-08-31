<?php $CVS_Version = '$Revision$ ; $Date$';

class GB_MoveSpeed extends GB_Float implements GB_MoveSpeedInterface 
{
	static protected $className = 'GB_MoveSpeed';
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		$minAcceleration = (float)DE_WorldSettings::CreateForCurentWorld()->get('MIN_ACCELERATION');
		$maxAcceleration = (float)DE_WorldSettings::CreateForCurentWorld()->get('MAX_ACCELERATION');
		//assert($value >= $minAcceleration && $value <= $maxAcceleration);
	}	
}

?>
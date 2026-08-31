<?php

class GB_SpyMoveSpeed extends GB_Float implements GB_MoveSpeedInterface 
{
	static protected $className = 'GB_SpyMoveSpeed';
	
	protected function _validateValue(&$value)
	{
		parent::_validateValue($value);
		$minAcceleration = (float)DE_WorldSettings::CreateForCurentWorld()->get('MIN_ACCELERATION');
		$maxAcceleration = (float)20.0;
		assert($value >= $minAcceleration && $value <= $maxAcceleration);
	}	
}

?>
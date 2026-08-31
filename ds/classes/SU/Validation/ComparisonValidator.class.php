<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ComparisonValidator class.
 * 
 * @author Akzhan Abdulin, Anton Fedchin DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $ 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/ValidationResult.class.php';

class ComparisonValidator
{
	function IsGreaterOrEqual($value, $testValue)
	{
		$r = TypeValidator::IsOfType($value, gettype($testValue));
		if($r->isError())
		{
			return $r;
		}
		
		if($value < $testValue)
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTBE_GE_ERROR');
		}
		return $r;
	}

}

?>
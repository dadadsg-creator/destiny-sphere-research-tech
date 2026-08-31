<?PHP

/**
 * TypeValidator class.
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

class TypeValidator
{
	function IsOfType($value, $typeString)
	{
		if(!(gettype(value) === $typeString))
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTBEOFTYPE_ERROR');
		}
		return ValidationResult::CreateOK();
	}

	function IsEmpty($value)
	{
		if(!empty($value))
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTBEEMPTY_ERROR');
		}
		return ValidationResult::CreateOK();
	}

	function IsNotEmpty($value)
	{
		if(empty($value))
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTBENOTEMPTY_ERROR');
		}
		return ValidationResult::CreateOK();
	}

	function IsInteger($value)
	{
		$r = TypeValidator::IsNotEmpty($value);
		if($r->isError())
		{
			return $r;
		}
		if(!is_integer($value))
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTBEINTEGER_ERROR');
		}
		return $r;
	}

	function IsString($value)
	{
		$r = TypeValidator::IsNotEmpty($value);
		if($r->isError())
		{
			return $r;
		}
		if(!is_string($value))
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTBESTRING_ERROR');
		}
		return $r;
	}

	function IsWantedPattern($pattern, $value)
	{
		$r = TypeValidator::IsString($pattern);
		if($r->isError())
		{
			return $r;
		}
		$r = TypeValidator::IsString($value);
		if($r->isError())
		{
			return $r;
		}
		if(preg_match($pattern, $value) < 1)
		{
			return ValidationResult::CreateError('STD_VALIDATION', 'MUSTMATCHPATTERN_ERROR');
		}
		return $r;
	}
}

?>
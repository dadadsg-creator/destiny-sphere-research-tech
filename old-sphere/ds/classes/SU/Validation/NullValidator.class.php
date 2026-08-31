<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * NullValidator class.
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

class NullValidator
{
	function IsAllOK()
	{
		return ValidationResult::CreateOK();
	}
	
	function IsAllError()
	{
		return ValidationResult::CreateError('STD_VALIDATION', 'NULL_ERROR');
	}
	
	function IsAllWarning()
	{
		return ValidationResult::CreateWarning('STD_VALIDATION', 'NULL_WARNING');
		
	}
}

?>
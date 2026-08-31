<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * DSCommonValidator class.
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
require_once constant('DS_SCRIPT_ROOT').'/DS_Common.php';

class DSCommonValidator
{
	function IsConnectionValid()
	{
		if(!array_key_exists('common', $GLOBALS))
		{
			return ValidationResult::CreateError('DSCOMMON_VALIDATION', 'DSCOMMONNOTFOUND_ERROR');
		}
		if(empty($GLOBALS['common']->conn))
		{
			return ValidationResult::CreateError('DSCOMMON_VALIDATION', 'DSCOMMONNOTCONNECTED_ERROR');
		}
		return ValidationResult::CreateOK();
	}
}

?>
<?PHP
/**
 * Assert class.
 * 
 * @author Akzhan Abdulin, DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $ 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

require_once constant('DS_SCRIPT_ROOT').'/classes/Core/LowLevelStaticVariableStorage.class.php';

/**
 * Represents error and warning accumulator/
 */ 
class AssertAccumulator 
	extends LowLevelStaticVariableStorage
{
	
	function _InitializationNeeded()
	{
		parent::_InitializationNeeded();
		AssertAccumulator::InitProperty('AssertAccumulator::ErrorCount', 0);
		AssertAccumulator::InitProperty('AssertAccumulator::WarningCount', 0);
	}

	/**
	* @return boolean
	* @desc Value determining whether errors occured or not.
	*/
	function HasErrors()
	{
		AssertAccumulator::_InitializationNeeded();
		return (AssertAccumulator::GetPropertyValue('AssertAccumulator::ErrorCount') > 0);
	}
	
	/**
	* @return boolean
	* @desc Value determining whether warnings occured or not.
	*/
	function HasWarnings()
	{
		AssertAccumulator::_InitializationNeeded();
		return (AssertAccumulator::GetPropertyValue('AssertAccumulator::WarningCount') > 0);
	}
	
	/**
	* @return void
	* @desc Clear accumulated values
	*/
	function ClearAccumulations()
	{
		AssertAccumulator::SetPropertyValue('AssertAccumulator::ErrorCount', 0);
		AssertAccumulator::SetPropertyValue('AssertAccumulator::WarningCount', 0);
	}		
	
	/**
	* @return void
	* @desc Informs accumulator about error
	*/
	function ErrorOccured()
	{
		AssertAccumulator::_InitializationNeeded();
		AssertAccumulator::SetPropertyValue(
			'AssertAccumulator::ErrorCount',
			AssertAccumulator::GetPropertyValue('AssertAccumulator::ErrorCount') + 1
			);
	}
	 
	/**
	* @return void
	* @desc Informs accumulator about warning
	*/
	function WarningOccured()
	{
		AssertAccumulator::_InitializationNeeded();
		AssertAccumulator::SetPropertyValue(
			'AssertAccumulator::WarningCount',
			AssertAccumulator::GetPropertyValue('AssertAccumulator::WarningCount') + 1
			);
	}
}


?>

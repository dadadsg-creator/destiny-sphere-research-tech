<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * StaticVariableStorage class.
 * 
 * @author Akzhan Abdulin, AntonFEdchinDestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $ 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

require_once constant('DS_SCRIPT_ROOT').'/classes/Core/PropertyStorage.class.php';

class StaticVariableStorage
{
	
	function NeedPropertyStorage(&$storage)
	{
		if(!array_key_exists('DS_StaticVariables', $GLOBALS))
		{
			$GLOBALS['DS_StaticVariables'] = new PropertyStorage();
		}
		$storage = $GLOBALS['DS_StaticVariables'];
	}
	
	/**
	* @return mixed
	* @param string $propertyName
	* @desc Gets value of specified property by its name.
	*/
	function GetPropertyValue($propertyName)
	{
		$storage = null;
		StaticVariableStorage::NeedPropertyStorage($storage);
		return $storage->getPropertyValue($propertyName);
	}
	
	/**
	* @return void
	* @param string $propertyName
	* @param mixed $value
	* @desc Sets value of specified property by its name.
	*/
	function SetPropertyValue($propertyName, $value)
	{
		$originalValue = $this->_getPropertyValue($propertyName);
		if($originalValue === $value)
		{
			return;
		}
		$cancel = false;
		$this->_onPropertyChanging($propertyName, $originalValue, $value, $cancel);
		if($cancel)
		{
			return;
		}
		$this->values[$propertyName] = $value;
		$this->_onPropertyChanged($propertyName, $originalValue, $value);
	}
}


?>
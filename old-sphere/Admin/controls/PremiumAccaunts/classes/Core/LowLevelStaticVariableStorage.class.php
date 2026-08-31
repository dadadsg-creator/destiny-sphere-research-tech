<?php

/**
 *  LowLevelStaticVariableStorage class.
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

class LowLevelStaticVariableStorage
{
	function _InitializationNeeded()
	{
	}
		
	function _GetStorageName()
	{
		return 'DS_StaticVariables_ghghgghghfjvbj';
	}
	
	function _NeedPropertyStorage()
	{
		if(!array_key_exists(LowLevelStaticVariableStorage::_GetStorageName(), $GLOBALS))
		{
			$GLOBALS[LowLevelStaticVariableStorage::_GetStorageName()] = array();
		}
	}
	
	/**
	* @return boolean
	* @param string $propertyName
	* @desc Gets value whether specified property exists or not.
	*/
	function HasProperty($propertyName)
	{
		assert(is_string($propertyName) && !empty($propertyName));
		LowLevelStaticVariableStorage::_NeedPropertyStorage();
		return array_key_exists($propertyName, $GLOBALS[LowLevelStaticVariableStorage::_GetStorageName()]);
	}
	
	/**
	* @return mixed
	* @param string $propertyName
	* @desc Gets value of specified property by its name.
	*/
	function GetPropertyValue($propertyName)
	{
		assert(is_string($propertyName) && !empty($propertyName));
		LowLevelStaticVariableStorage::_NeedPropertyStorage();
		return $GLOBALS[LowLevelStaticVariableStorage::_GetStorageName()][$propertyName];
	}
	
	/**
	* @return void
	* @param string $propertyName
	* @param mixed $value
	* @desc Sets value of specified property by its name.
	*/
	function SetPropertyValue($propertyName, $value)
	{
		assert(is_string($propertyName) && !empty($propertyName));
		LowLevelStaticVariableStorage::_NeedPropertyStorage();
		$GLOBALS[LowLevelStaticVariableStorage::_GetStorageName()][$propertyName] = $value;
	}

	/**
	* @return void
	* @param string $propertyName
	* @param mixed $defaultValue
	* @desc Initializes specified property by default value if there no property found.
	*/
	function InitProperty($propertyName, $defaultValue)
	{
		assert(is_string($propertyName) && !empty($propertyName));
		if(!LowLevelStaticVariableStorage::HasProperty($propertyName))
		{
			LowLevelStaticVariableStorage::SetPropertyValue($propertyName, $defaultValue);
		}
	}
	
}


?>
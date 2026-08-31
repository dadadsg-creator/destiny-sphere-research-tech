<?PHP

/**
 * PropertyStorage class.
 * 
 * @author Akzhan Abdulin, DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $ 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

/**
 * Represents base class of refactored Destiny Sphere server code.
 * This class supports property storage and event firing.
 */
class PropertyStorage
{
	/**
	* @var array Properties storage (associated array).
	*/
	var $values;
	
	/**
	* @return void
	* @param string $propertyName Changing property name
	* @param mixed $originalValue Original value of the specified property.
	* @param mixed $proposedValue Proposed value of the specified property.
	* @param bool& $cancel Flag of cancellation of property setting.
	* @desc Fires when specified property is changing.
	*/
	function _onPropertyChanging($propertyName, $originalValue, $proposedValue, &$cancel)
	{		
	}
	
	/**
	* @return void
	* @param string $propertyName
	* @param mixed $oldValue Old value of the specified property.
	* @param mixed $newValue New value of the specified property.
	* @desc Fires when specified property is changed.
	*/
	function _onPropertyChanged($propertyName, $oldValue, $newValue)
	{
	}
	
	/**
	* @return mixed
	* @param string $propertyName
	* @desc Gets value of specified property by its name.
	*/
	function getPropertyValue($propertyName)
	{
		return $this->values[$propertyName];
	}
	
	/**
	* @return void
	* @param string $propertyName
	* @param mixed $value
	* @desc Sets value of specified property by its name.
	*/
	function setPropertyValue($propertyName, $value)
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
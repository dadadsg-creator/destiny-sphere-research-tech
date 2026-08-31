<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ValidationResult class.
 * 
 * @author Akzhan Abdulin & Anton Fedchin, DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $ 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

/**
 * Represents result of validation check 
 */
class ValidationResult 
{
	/** @var Localized message category */
	public $category = '';
	
	/** @var Localized message code */
	public $code = '';
	
	/** @var Stack trace */
	public $trace = array();
	
	/** @var Type of validation results */ 
	public $validationResultTypes = array('ERROR', 'WARNING', 'OK');
	
	/** @var Result of validation */
	public $validationResult = 'OK';
	
	/**
	* @return void
	* @param String $validationResult Result of validation
	* @param String $category Localized message category 
	* @param String $code Localized message code
	* @param Array $trace Stack trace (optional)
	* @desc Initializes a new instance of ValidationResult class.
	*/
	function ValidationResult($validationResult, $category, $code, $trace = null)
	{		
		assert(in_array($validationResult, $this->validationResultTypes));
		assert(is_string($category));
		assert(is_string($code));
		if($validationResult != 'OK')
		{
			assert(!empty($category));
			assert(!empty($code));
		}
		assert(is_array($trace) || is_null($trace));
		
		if(is_null($trace))
		{
			$trace = debug_backtrace();
		}
		
		$this->validationResult = $validationResult;
		$this->category =  $category;
		$this->code = $code;
		$this->trace = $trace;
	}
	
	
	/**
	* @return ValidationResult
	* @desc returns OK value
	*/
	function CreateOK()
	{
		return new ValidationResult('OK', '', '');
	}
	
	/**
	* @return ValidationResult
	* @param String $category Localized message category 
	* @param String $code Localized message code
	* @desc returns ERROR value
	*/
	function CreateError($category, $code)
	{
		return new ValidationResult('ERROR', $category, $code);		
	}
	
	/**
	* @return ValidationResult
	* @param String $category Localized message category 
	* @param String $code Localized message code
	* @desc returns WARNING value
	*/
	function CreateWarning($category, $code)
	{
		return new ValidationResult('WARNING', $category, $code);		
	}
	
	/**
	* @return bool
	* @desc Tests validation result for error
	*/
	function isError()
	{
		return $this->validationResult === 'ERROR';
	}
	
	/**
	* @return bool
	* @desc Tests validation result for warning
	*/
	function isWarning()
	{
		return $this->validationResult === 'WARNING';
	}
}

?>
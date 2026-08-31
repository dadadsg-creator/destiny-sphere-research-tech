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

require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/ValidationResult.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/AssertAccumulator.class.php';

/**
 * Represents assertion pattern (all methods are static)
 */
class Assert
{
    
    
    function _GetDieOnError()
    {
        if(!array_key_exists('CLASS_ASSERT_DIEONERROR', $GLOBALS))
        {
            return true;
        }
        return $GLOBALS['CLASS_ASSERT_DIEONERROR'];
    }
    
    public static function SetDieOnError($value)
    {
        assert(is_bool($value));
        $GLOBALS['CLASS_ASSERT_DIEONERROR'] = $value;
    }
        
    function _IsValidAssertionConditionType($value)
    {       
        assert(is_string($value));
        $assertionConditionTypes = array('PRE', 'POST', 'INVARIANT');
        return in_array($value, $assertionConditionTypes);
    }
    
    function _TryDie()
    {
        if(Assert::_GetDieOnError())
        {
            exit;
        }
    }
    
    function _OnAssertionCondition($assertionConditionType, $validationResult)
    {       
        assert(Assert::_IsValidAssertionConditionType($assertionConditionType));
        assert(is_a($validationResult, 'ValidationResult'));
        
        if($validationResult->isError())
        {
            AssertAccumulator::ErrorOccured();/** @todo Error handling */
            Assert::_TryDie();
            
        }
        
        if($validationResult->isWarning())      
        {
            /** @todo Warning handling */
            AssertAccumulator::WarningOccured();
        }           
    }
    
    
    /**
    * @return void
    * @param ValidationResult $validationResult 
    * @desc Tests precondition.
    */
    function PreCondition($validationResult)
    {
        Assert::_OnAssertionCondition('PRE', $validationResult);
    }
    
    /**
    * @return void
    * @param ValidationResult $validationResult 
    * @desc Tests postcondition.
    */
    function PostCondition($validationResult)
    {
        Assert::_OnAssertionCondition('POST', $validationResult);
    }
    
    /**
    * @return void
    * @param ValidationResult $validationResult 
    * @desc Tests invariant condition.
    */
    function InvariantCondition($validationResult)
    {
        Assert::_OnAssertionCondition('INVARIANT', $validationResult);
    }       
    
    function HasErrors()
    {
        return false;
    }
    
    function HasWarnings()
    {
        return false;
    }
    
    function ClearAccumulations()
    {
        
    }       
}   
?>
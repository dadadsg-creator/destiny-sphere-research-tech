<?php
/**
 * ValidationTestCase class.
 * 
 * @author Akzhan Abdulin, Anton Fedchin DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $ 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

require_once constant('DS_SCRIPT_ROOT').'/simpletest/unit_tester.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/Assert.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/NullValidator.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/AssertAccumulator.class.php';

class ValidationTestCase 
	extends UnitTestCase 
{
	function ValidationTestCase() 
	{
        $this->UnitTestCase();
        Assert::SetDieOnError(false);
    }
    
    function testNullPreConditionOK()
    {
    	Assert::PreCondition(NullValidator::IsAllOK());
    	$this->assertFalse(AssertAccumulator::HasWarnings());
    	$this->assertFalse(AssertAccumulator::HasErrors());
    	AssertAccumulator::ClearAccumulations();
    }
    
    function testNullPreConditionWarning()
    {
    	Assert::PreCondition(NullValidator::IsAllWarning());
    	$this->assertTrue(AssertAccumulator::HasWarnings());
    	$this->assertFalse(AssertAccumulator::HasErrors());
    	AssertAccumulator::ClearAccumulations();
    }
    
    function testNullPreConditionError()
    {
    	Assert::PreCondition(NullValidator::IsAllError());
    	$this->assertFalse(AssertAccumulator::HasWarnings());
    	$this->assertTrue(AssertAccumulator::HasErrors());
    	AssertAccumulator::ClearAccumulations();
    }

}

?>
<?php
/**
 * TraceTestCase class.
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
require_once constant('DS_SCRIPT_ROOT').'/simpletest/mock_objects.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Core/Trace.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Core/TextFileTraceListener.class.php';

require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/Assert.class.php';
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/AssertAccumulator.class.php';

Mock::generate('TraceListener');


class TraceTestCase 
	extends UnitTestCase 
{
	function TraceTestCase() 
	{
        $this->UnitTestCase();
        Assert::SetDieOnError(false);
        $testListener = &new TextFileTraceListener('trace.txt');
    	Trace::AddListener($testListener);
    }
    
    function testWriteGoodMessage()
    {
		Trace::WriteMessage('TEST', 'GOOD_MESSAGE');
    	$this->assertTrue(file_exists('trace.txt'));
    	if(file_exists('trace.txt'))
    	{
    		unlink('trace.txt');
    	}
    }
    
    function testWriteWrongMessage()
    {
		Trace::WriteMessage('TEST', 'WRONG_MESSAGE');
    	$this->assertFalse(file_exists('trace.txt'));
    	if(file_exists('trace.txt'))
    	{
    		unlink('trace.txt');
    	}
    }
    
    function testOperateListener()
    {    	
    	$testListener = &new TraceListener();
    	Trace::AddListener($testListener);    	
    	Trace::RemoveListener($testListener);
    }
    


}

?>
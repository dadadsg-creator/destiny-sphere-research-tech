<?php
/**
 * InvoiceNumberGeneratorTestCase class.
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
require_once constant('DS_SCRIPT_ROOT').'/classes/Validation/TypeValidator.class.php';

include constant('DS_SCRIPT_ROOT').'/InvoiceNumberGenerator.php';



function Init()
{
	$GLOBALS['common'] = new DS_Common();
	$GLOBALS['common']->connect();
	
}

Init();






class InvoiceNumberGeneratorTestCase 
	extends UnitTestCase 
{
	var $gen;
	
	function InvoiceNumberGeneratorTestCase() 
	{
        $this->UnitTestCase();
        Assert::SetDieOnError(false);
        $testListener = &new TextFileTraceListener('trace.txt');
    	Trace::AddListener($testListener);
    }
    
    function setUp()
    {
    	$this->gen = new InvoiceNumberGenerator();
    }
    
    function tearDown()
    {
    	$this->gen = null;
    }
    
    function testSmoke()
    {    	
    	$this->assertNotNull($this->gen);
    }
    
    function testConnection()
    {
    	$this->assertFalse(DSCommonValidator::IsConnectionValid()->isError());
    	$this->assertFalse(AssertAccumulator::HasErrors());
    }

    function testGenerate()
    {   
    	$gen = & $this->gen;
    	$number = $gen->generateInvoiceNumber('R', '20051004',666);    
    	$this->assertWantedPattern('/^[A-Z]{1}[0-9]?-[0-9]{1,5}$/', $number);
 	echo $number;
    	$this->assertFalse(AssertAccumulator::HasErrors());    	
    }
    
    function testGettingLastInvoiceNumber()
    {
    	$this->assertEqual(3, $this->gen->getLastInvoiceNumberInYear(2004));
    	$this->assertFalse(AssertAccumulator::HasErrors());
    }
    
   /* function testGettingPaymentIdByOldCode()
    {
    	$gen = & $this->gen;
    	$number = $gen->getPaymentIdByCode('R-56565');
    	$this->assertTrue(is_int($number));
    	$this->assertFalse(AssertAccumulator::HasErrors());
    }*/
    
    function testGettingPaymentIdByNewCode()
    {
    	$gen = & $this->gen;
    	$number = $gen->getPaymentIdByCode('500565');
    	$this->assertTrue(is_int($number));
    	$this->assertFalse(AssertAccumulator::HasErrors());
    }
    
    function testCheckTypeInvoice()
    {
    	$gen = & $this->gen;    	
    	$this->assertFalse($gen->CheckTypeInvoice('20041014'));
    	$this->assertFalse(AssertAccumulator::HasErrors());
    }
    
}

?>
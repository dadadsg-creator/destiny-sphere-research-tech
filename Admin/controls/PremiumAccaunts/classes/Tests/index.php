<?php
/**
 * All Unit test script.
 * 
 * @author Akzhan Abdulin, Anton Fedchin DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * $History: $
 */

define('DS_SCRIPT_ROOT', '../..', true);

require_once constant('DS_SCRIPT_ROOT').'/simpletest/unit_tester.php';
require_once constant('DS_SCRIPT_ROOT').'/simpletest/reporter.php';

$test = &new GroupTest('All tests');
//$test->addTestFile('ValidationTestCase.class.php');
//$test->addTestFile('TraceTestCase.class.php');
$test->addTestFile('InvoiceNumberGeneratorTestCase.class.php');
$test->run(new HtmlReporter());

?>
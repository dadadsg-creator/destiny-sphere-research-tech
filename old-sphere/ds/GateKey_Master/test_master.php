<?php

require_once 'gate_main.php';

$data = $_REQUEST;

if(key_exists(md5('gate_key'), $data))
{
	// проверка ключа
	if(!key_exists(md5('gate_key'), $data))
	{
		die("No gate key");
	} else {
		$key = $data[md5('gate_key')];
	}
	
	// проверка gate_magic
	try
	{
		if(!key_exists(md5('gate_magic'), $data) || $data[md5('gate_magic')] != md5(constant('GATE_KEY_MAGIC').date('Y-d-m')))
		{
			throw new Exception('No magic in request');
		}
	} catch (Exceprtion $e) {
		GK_Logger::Error($e, true);
	}
	
	try 
	{
		// (bool)
		$isValid = GK_GateKeyMasterController::CheckKey($key, 1);
	} catch (Exception $e) {
		// обработка исключения
		die($e->getMessage());
	}

	if(!$isValid)
	{
		// действия при неверном ключе в запросе
		die("Key '$key' not valid");
	} else {
		die("Key '$key' is valid");
	}
} else {	
	try
	{
		$slave = new GK_Slave(1);
		$key = GK_GateKeyMasterController::GetNewKey(1);
		
		$request = new GK_HTTPRequest();
		$request->setOption('method', 'POST');
		$request->setVar(md5('gate_magic'), md5($slave->getName().date('Y-d-m')));
		$request->addHeader("Content-type: application/x-www-form-urlencoded");
		$request->setVar(md5('gate_action'), md5('gate_test_key'));
		$request->setVar(md5('gate_key'), $key);
		
		$res = $request->get('http://'.$slave->getDomain().'/ds/GateKeyProtection/test_slave.php');
		
		echo $res;
	} catch (Exception $e) {
		GK_Logger::Error($e, true);
	}
}

?>
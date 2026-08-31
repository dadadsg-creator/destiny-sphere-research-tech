<?php

/**
 * Базовый класс для всей проверки
 *
 */
class MU_InterworldBase
{
	static public function Out($msg)
	{
		$out = SU_DebugTools::GetInternalDate()." - ".$msg."<br>";		
		SU_DebugTools::WriteToLog($out, 'InterworldMoneyTransferValidate.log');			
		flush();
	}
}


?>
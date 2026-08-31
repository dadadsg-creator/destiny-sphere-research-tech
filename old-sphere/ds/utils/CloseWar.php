<?php $CVS_Version = '$Revision$ ; $Date$';

require_once '../Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

define('DEBUG_MODE', 1);

SU_DebugTools::TimerStart('TEST');

/*********** BEGIN WORK AREA **************/

class CloseWar
{
	private $action;
	
	private $common;
	
	function __construct($data)
	{
		$this->_getParams($data);

		$this->common = DS_Common::CreateForCurentDB();
		
		switch($this->action)
		{
			case 'close':
				$this->_closeAllWorlds();
				break;
			case 'open':
				$this->_openAllWorlds();
				break;
			case 'close_trade':
				$this->_closeAllTrades();
				break;
			case 'open_trade':
				$this->_openAllTrades();
				break;
		}
		
		$this->_out();
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('close', $data))
		{
			$this->action = 'close';
		}
		
		if(key_exists('open', $data))
		{
			$this->action = 'open';
		}
		
		if(key_exists('close_trade', $data))
		{
			$this->action = 'close_trade';
		}
		
		if(key_exists('open_trade', $data))
		{
			$this->action = 'open_trade';
		}
	}
	
	private function _closeAllWorlds()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($dbId == 0)
			{
				//continue;
			}

			$this->common->setDbId($dbId);
			
			$from = GB_TypeFactory::CreateConstant('GB_Date', time() - 120);
			$to = GB_TypeFactory::CreateConstant('GB_Date', time() + 31622400);
			
			$sql = "
				INSERT into ds_peacepreiods
     				(id_peaceperiod, start_date, end_date)
				SELECT
					count(id_peaceperiod) + 1,
					'".$from->getPgFormat()."' as start_date,
					'".$to->getPgFormat()."' as end_date
				FROM
					ds_peacepreiods
     				";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		echo "<h1>WAR CLOSED</h1>";
	}
	
	
	private function _openAllWorlds()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($dbId == 0)
			{
				//continue;
			}
			
			$this->common->setDbId($dbId);
			
			$sql = "DELETE FROM ds_peacepreiods";
			
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		echo "<h1>WAR OPENED</h1>";
	}
	
	private function _closeAllTrades()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($dbId == 0)
			{
				//continue;
			}

			$this->common->setDbId($dbId);
			
			$from = GB_TypeFactory::CreateConstant('GB_Date', time() - 120);
			$to = GB_TypeFactory::CreateConstant('GB_Date', time() + 31622400);
			
			$sql = "
				INSERT INTO ds_notradeperiods
     				(id_notradeperiod, start_date, end_date, id_world)
				SELECT
     				(
       					select
           					count(id_notradeperiod) + w.id_world
       					from
           					ds_notradeperiods
     				),
     				'".$from->getPgFormat()."' as start_date,
     				'".$to->getPgFormat()."' as end_date,
     				id_world
     			FROM
     				ds_world as w
     				";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		echo "<h1>TRADE CLOSED</h1>";
	}
	
	
	private function _openAllTrades()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($dbId == 0)
			{
				//continue;
			}
			
			$this->common->setDbId($dbId);
			
			$sql = "DELETE FROM ds_notradeperiods";
			
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		echo "<h1>TRADE OPENED</h1>";
	}
	
	
	private function _out()
	{
		echo '
		<table border=1>
			<tr>
				<td>WAR</td><td>TRADE</td>
			</tr>
			<tr>
				<td width="20%" align="center">
			<form action="" method="POST">
				<input type="hidden" name="close" value="1">
				<input type="submit" value="CLOSE">
			</form>
			<form action="" method="POST">
				<input type="hidden" name="open" value="1">
				<input type="submit" value="OPEN">
			</form>
				</td>
				<td width="20%"  align="center">
			<form action="" method="POST">
				<input type="hidden" name="close_trade" value="1">
				<input type="submit" value="CLOSE">
			</form>
			<form action="" method="POST">
				<input type="hidden" name="open_trade" value="1">
				<input type="submit" value="OPEN">
			</form>
				</td>
			</tr>
		</table>
			';
	}
	
}

$v = new CloseWar($_REQUEST);

/*********** END WORK AREA **************/

echo "<hr>WORK AREA TIME: ".SU_DebugTools::TimerStop('TEST');
?>
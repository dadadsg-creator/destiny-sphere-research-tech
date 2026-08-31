<?
require_once('Main.php');
ini_set('memory_limit', '32M');
import ( 'SU_DebugTools');

class GetStartCollection
{

	/** ИНтерфейсный объект сипользуемый для раоты с всеми БД */
	var $common = '';

	var $scrolScript = "<script language=\"JavaScript\">
   							<!--
       						window.scrollTo(0,10000000);
   							// -->
   							</script>";

	var $allowBases = array();

	var $outPutMode = 'text';

	var $modifiers = array();

	var $dbId;

	var $worldID;

	var $deletedShortInfo = 0;
	var $deletedFullInfo = 0;
	var $deletedOldLogs = 0;

	function GetStartCollection()
	{
		$this->common = new DS_Common();

		if ( $_SERVER['DOCUMENT_ROOT'] )
		{
			$this->outPutMode = 'html';
		}else{
			$this->outPutMode = 'text';
		}

	}//END OF METHOD

	function collect()
	{
		$str = "'START SCANING BASES'";
		$this->out($str);
		SU_DebugTools::LogAll($str, "CollectBattles.log");

		foreach ( $this->common->dbWorldAccess as $k=>$v )
		{
			$this->dbId = $k;

			if($this->allowBases && !in_array ( $k, $this->allowBases ))
			{
				continue;
			}

			/*Устанавливаем соединение с базой*/
			$this->common->setDbId($this->dbId);
			if ( !$this->common->conn ) {
				$this->error ( "Cant connect to DB ".$v['server'] );
			}


			$this->out('=====================================');
			$this->out ( "WORK WITH DB '".$v['server']."'" );


			/* Тут вызываем поочередно все методы, которые трбуется для работы в каждой базе в отдельности*/
			$this->process();

		}

		$this->out('=====================================');
		$this->out('COMPLIT');

		$str = "Deleted ShortInfo=".$this->deletedShortInfo." and FullInfo=".$this->deletedShortInfo." and OldFiles=".$this->deletedOldLogs."\n";
		$this->out($str);
		SU_DebugTools::LogAll($str, "CollectBattles.log");

	}//END OF METHOD

	function process()
	{
		$this->out('START >>>COLLECT');
		$worldList = $this->getWorldList();
		//$this->out(var_export($worldList,1));


		if ($worldList)
		{
			foreach ($worldList as $k => $v)
			{
				$this->worldID = $v['id_world'];

				$this->out('START >>>PROCESS '.$v['worldname']);

				$this->getModifiers();
				//$this->out(var_export($modifiersList,1));

				$this->processShortInfo();
				$this->processFullInfo();

				$this->out('END >>>PROCESS '.$v['worldname']);

			}



			$this->deleteOldBattles();
		}




		$this->out('END >>>COLLECT');
	}

	function deleteOldBattles()
	{
		$battleArrayIN = array();
		$battleArray = array();


		$d = dir($GLOBALS['LOGING_DEBUG_LOGS_PATH']['SLAVE']);
		while($entry=$d->read())
		{
			if( $entry != "." && $entry != ".." )
			{
				$arr = explode(".", $entry);
				if (sizeof($arr) == 2 && $arr[1] == "log")
				{
					$arr2 = explode("_",$arr[0]);
					if ($arr2[0] == "Battle")
					{
						//echo $entry."\n";
						$battleArrayIN[] = $arr2[2];
						$battleArray[$arr2[2]] = $entry;
					} else {
						continue;
					}
				}
			}
		}

		$sql = "SELECT id_battle FROM ds_battle WHERE id_battle IN (".implode(',',$battleArrayIN).")";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get id_battles in DB.");
		}

		foreach ($rs as $key => $value)
		{
			unset($battleArray[ $value['id_battle'] ]);
		}

		foreach ($battleArray as $key => $value)
		{

			if (unlink($GLOBALS['LOGING_DEBUG_LOGS_PATH']['SLAVE'].$value) )
			{
				$this->deletedOldLogs++;
			}
		}

	}

	function processShortInfo()
	{
		$this->out('START process ShortInfo');


		$days = $this->modifiers[$this->dbId][$this->worldID]['BATTLE_SHORT_STORE_PERIOD_DAYS'];

		$res = $this->getBattlesList($days);

		foreach ($res as $key => $value)
		{
			$this->common->executeQuery ( 'BEGIN' );
			if($this->common->errormsg)
			{
				$this->error ("Cant begin DB:".$this->dbId." TRANSACTION!");
			}

			$battleID = $value['id_battle'];
			$this->_deleteShortInfo($battleID);

			$this->_deleteBattleLog($value);

			$this->common->executeQuery ( 'COMMIT' );
			//$this->common->executeQuery ( 'ROLLBACK' );
			if ( $this->common->errormsg )
			{
				$this->error ("Cant commit DB:".$this->dbId." TRANSACTION!");
			}

			$str = "End Process Delete ShortInfo by battleID=".$battleID;
			SU_DebugTools::LogAll($str, "CollectBattles.log");
		}
		return;
	}

	function processFullInfo()
	{
		$this->out('START process FullInfo');

		$days = $this->modifiers[$this->dbId][$this->worldID]['BATTLE_FULL_STORE_PERIOD_DAYS'];

		$res = $this->getBattlesList($days);

		foreach ($res as $key => $value)
		{
			$this->common->executeQuery('BEGIN');
			if($this->common->errormsg)
			{
				$this->error ("Cant begin DB:".$this->dbId." TRANSACTION!");
			}

			$battleID = $value['id_battle'];
			$this->_deleteFullInfo($battleID);

			$this->common->executeQuery ( 'COMMIT' );
			//$this->common->executeQuery ( 'ROLLBACK' );
			if ( $this->common->errormsg )
			{
				$this->error ("Cant commit DB:".$this->dbId." TRANSACTION!");
			}
		}
		return;
	}

	function _deleteBattleLog($value)
	{
		$this->out('START delete file Battle Logs');

		$filename = $GLOBALS['LOGING_DEBUG_LOGS_PATH']['SLAVE']."Battle_".$value['battlestartdate']."_".$value['id_battle'].".log";

		if (file_exists($filename))
		{
			unlink($filename);
		}
	}

	function _deleteShortInfo($battleID)
	{
		$this->out('START delete ShortInfo BattleID='.$battleID);

		$this->_deleteBattleResult($battleID);
		$this->_deleteBattleRound($battleID);
		$this->_deleteBattleParcipiantHasUnitContainer($battleID);

		$this->deletedShortInfo++;
		return true;

	}

	function _deleteFullInfo($battleID)
	{
		$this->out('START delete FullInfo BattleID='.$battleID);

		$this->_deleteBattleReport($battleID);
		$this->_deleteBattleHasAttribute($battleID);
		$this->_deleteBattleParcipiant($battleID);
		$this->_deleteBattle($battleID);

		$this->deletedFullInfo++;

		return true;

	}

	function _deleteBattle($battleID)
	{
		$this->out('START delete Battle by BattleID='.$battleID);

		$sql = "DELETE FROM ds_battle WHERE id_battle = ".$battleID;
		$this->common->executeQuery ( $sql );
		if ( $this->common->errormsg )
		{
			$this->error ("Cant Execute DELETE From Battle by id:".$battleID."");
		}

		return;
	}

	function _deleteBattleParcipiant($battleID)
	{
		$this->out('START delete BattleParcipiant by BattleID='.$battleID);

		$sql = "DELETE FROM ds_battleparticipant WHERE id_battle = ".$battleID;
		$this->common->executeQuery ( $sql );
		if ( $this->common->errormsg )
		{
			$this->error ("Cant Execute DELETE From BattleParcipiant by id:".$battleID."");
		}

		return;
	}

	function _deleteBattleHasAttribute($battleID)
	{
		$this->out('START delete BattleHasAttribute by BattleID='.$battleID);

		$sql = "DELETE FROM ds_battlehasattribute WHERE id_battle = ".$battleID;
		$this->common->executeQuery ( $sql );
		if ( $this->common->errormsg )
		{
			$this->error ("Cant Execute DELETE From BattleHasAttribute by id:".$battleID."");
		}

		return;
	}



	function _deleteBattleReport($battleID)
	{
		$this->out('START delete BattleReport by BattleID='.$battleID);

		$sql = "DELETE FROM ds_battlereport WHERE id_battle = ".$battleID;
		$this->common->executeQuery ( $sql );
		if ( $this->common->errormsg )
		{
			$this->error ("Cant Execute DELETE From BattleReport by id:".$battleID."");
		}

		return;
	}

	function _deleteBattleParcipiantHasUnitContainer($battleID)
	{
		$this->out('START delete BattleParcipiantHasUnitContainer by BattleID='.$battleID);

		$sql = "
				SELECT
					bphuc.id_battleparticipanthasunitcontainer
				FROM
					ds_battleparticipant bp
					INNER JOIN ds_battle b ON (bp.id_battle=b.id_battle)
					INNER JOIN ds_battleparticipanthasunitcontainer bphuc ON (bp.id_battleparticipant=bphuc.id_battleparticipant)
				WHERE
					b.id_battle = ".$battleID."
				";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get BattleParcipiantHasUnitContainer detail.");
		}

		if (!$rs || sizeof($rs)<=0)
		{
			return;
		}

		foreach ($rs as $key => $value)
		{
			$id = $value['id_battleparticipanthasunitcontainer'];

			//$this->out('START delete BattleParcipiantHasUnitContainer by ID='.$id);
			$sql = "DELETE FROM ds_battleparticipanthasunitcontainer WHERE id_battleparticipanthasunitcontainer = ".$id;
			$this->common->executeQuery ( $sql );
			if ( $this->common->errormsg )
			{
				$this->error ("Cant Execute DELETE From BattleParcipiantHasUnitContainer by id:".$id."");
			}
		}
		return;
	}

	function _deleteBattleRound($battleID)
	{
		$this->out('START delete BattleResult by BattleID='.$battleID);

		$sql = "
				SELECT
					br.id_battleround
				FROM
					ds_battleround br
					INNER JOIN ds_battle b ON (br.id_battle=b.id_battle)
				WHERE
					b.id_battle = ".$battleID."
		";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get Battle detail. BattleResult");
		}

		if (!$rs || sizeof($rs)<=0)
		{
			return;
		}

		foreach ($rs as $key => $value)
		{
			$id = $value['id_battleround'];
			//$this->out('START delete BattleRound by ID='.$id);

			$sql = "DELETE FROM ds_battleround WHERE id_battleround = ".$id.";";
			$this->common->executeQuery ( $sql );
			if ( $this->common->errormsg )
			{
				$this->error ("Cant Execute DELETE From BattleRound by id:".$id."");
			}

		}

		return;
	}

	function _deleteBattleResult($battleID)
	{
		$this->out('START delete BattleResult by BattleID='.$battleID);

		$sql = "
				SELECT
					bres.id_battleresult
				FROM
					ds_battleround br
					INNER JOIN ds_battle b ON (br.id_battle=b.id_battle)
					INNER JOIN ds_battleresult bres ON (br.id_battleround=bres.id_battleround)
				WHERE
					b.id_battle = ".$battleID."
		";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get Battle detail. BattleResult");
		}

		if (!$rs || sizeof($rs)<=0)
		{
			return;
		}

		foreach ($rs as $key => $value)
		{
			$id = $value['id_battleresult'];
			//$this->out('START delete BattleResult by ID='.$id);

			$sql = "DELETE FROM ds_battleresult WHERE id_battleresult = ".$id.";";
			$this->common->executeQuery ( $sql );
			if ( $this->common->errormsg )
			{
				$this->error ("Cant Execute DELETE From BattleResult by id:".$id."");
			}
		}

		return;
	}

	function getModifiers()
	{

		$this->out('START get modifiers by world '.$this->worldID);
		$sql = "
				SELECT
					*
				FROM
					ds_gamemodifier gm
				WHERE
					(
						(gm.modifiername = 'BATTLE_FULL_STORE_PERIOD_DAYS') OR
						(gm.modifiername = 'BATTLE_SHORT_STORE_PERIOD_DAYS')
					)
					AND (gm.id_world = ".$this->worldID.")
  		";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get Battle list");
		}

		if (!$rs || sizeof($rs) < 2)
		{
			$this->out('Error gets modifiers by world '.$this->worldID);
			die();
		}

		foreach ($rs as $value)
		{
			$this->modifiers[$this->dbId][$this->worldID][$value['modifiername']] = $value['value'];
		}

		return;
	}

	function getBattlesList($days)
	{
		$this->out('START get BattleList by world '.$this->worldID.' and days '.$days);
		$sql = "
				SELECT DISTINCT
					b.id_battle,
					b.battlestartdate,
					ui.id_world
				FROM
					public.ds_battleparticipant bp
					INNER JOIN ds_battle b ON (bp.id_battle=b.id_battle)
					INNER JOIN public.ds_userinstance ui ON (bp.id_userinstance=ui.id_userinstance)
				WHERE
					battleenddate IS NOT NULL AND
					winner IS NOT NULL AND
					id_battleendreason IS NOT NULL AND
					ui.id_world = ".$this->worldID."
					AND battleenddate < (now() - '".$days." DAY' ::interval)
				ORDER BY b.id_battle
			";

		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get Battle list");
		}

		return $rs;
	}

	function getWorldList()
	{
		$sql = "
				SELECT
					id_world, worldname
				FROM
					ds_world
				ORDER BY id_world
		";
		$rs = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			$this->error ("Cant get world list");
		}

		if (!$rs || sizeof($rs) <= 0)
		{
			$this->out('<b>Notice</b>: No worlds in this DB!');
		}

		return $rs;
	}

	function error ( $msg )
	{
		die('ERROR! '.$msg);
	}//END OF METHOD

	function out($msg)
	{
		if($this->outPutMode == 'html')
		{
			$br = "<br>\n";
		}else{
			$msg = strip_tags($msg);
			$br = "\n";
		}

		echo date('Y-m-d H:i:s')." ".$msg.$br;

		if($this->outPutMode == 'html')
		{
			//echo $this->scrolScript;
		}

		flush();
	}//END OF METHOD

}//END OF CLASS


$a = new GetStartCollection();

$servers = @$_REQUEST['servers'];

if(!$servers){
	$servers = false;
}else{
	$servers = explode('-', $servers);
}

$a->allowBases = $servers;
$a->collect();


?>
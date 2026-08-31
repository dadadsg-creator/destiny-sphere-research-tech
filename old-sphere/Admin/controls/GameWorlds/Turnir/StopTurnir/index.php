<?php
ini_set('memory_limit', '16M');
set_time_limit(0);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";


class TurnirStop
{
	private $common;
	
	private $worldId;
	
	private $dbId;
	
	function __construct($data)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		$this->_getPostData($data);
	}
	
	private function _getPostData($data)
	{
		if(!isset($data['worldId']))
		{
			$this->_outFirstScreen();
		} else {
			list($this->dbId, $this->worldId) = explode("_", $data['worldId']);
			settype($this->dbId, 'int');
			settype($this->worldId, 'int');		
			assert($this->worldId > 0);
			assert($this->dbId >= 0);
			
			if (!$this->_stopTurnir()) {
				$this->_outFirstScreen();
			}
		}
	}
	
	private function _outFirstScreen()
	{
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$this->common->setDbId($dbId);
			$sql = "
				SELECT
					w.id_world,
					w.worldname
				FROM
					ds_world AS w
					INNER JOIN ds_gamemodifier AS gm ON (gm.id_world = w.id_world)
				WHERE
					gm.modifiername = 'TURNIR_WORLD'
					AND
					gm.value = '1'
				";
			
			$res = $this->common->getResultArray("", $sql , "", "", "");
			assert(empty($this->common->errormsg));
			
			foreach($res as $k => $world)
			{
				$worlds[$world['worldname']] = $dbId."_".$world['id_world'];
			}
		}
		
		
		if(count($worlds) == 0)
		{
			echo "<h1>No turnir worlds</h1>";
			return;
		}
		
		echo '<form action="" method="GET"><select name="worldId">'."\n";
		foreach($worlds as $worldname => $world)
		{
			if ($this->dbId.'_'.$this->worldId == $world) {
				$selected = 'selected';
			} else  {
				$selected = '';				
			}
			
			echo '<option value="'.$world.'" '.$selected.'>'.$worldname."\n";
		}
		echo '</select>&nbsp;&nbsp;<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;Stop turnir&nbsp;&nbsp;&nbsp;&nbsp;"></form>';
	}
	
	private function _stopTurnir()
	{
		$this->common->setDbId($this->dbId);
		
		// Получаем id турнира
		$turnir = TN_Search::GetNotFinishedTurnirs();
		assert(count($turnir) == 0 || count($turnir) == 1);
		
		// Если в мире нет активных турниров
		if (count($turnir) == 0) {
			ShowCMSMessage('В мире нет активных турниров', 2);
			return false;
		}
		
		$turnirID = (int)$turnir[0]['id_turnir'];
		assert($turnirID > 0);
		
		assert(is_int($this->worldId) && $this->worldId > 0);		

		$this->common->setDbId($this->dbId);	
		
		$this->common->executeQuery("begin");
		$sql = "
			UPDATE
				ds_vocationmode
			SET
				enddate = null
			WHERE
				id_userinstance IN
				(
					SELECT
						ui.id_userinstance
					FROM
						ds_userinstance AS ui
					WHERE
						ui.id_world = ".$this->worldId."
				);
			";		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$sql = "
			UPDATE
				ds_world
			SET
				world_status = (
					SELECT
						id_worldstatus
					FROM
						ds_worldstatus
					WHERE
						status_name = 'REGISTRATION_CLOSE'
				)
			WHERE
				id_world = ".$this->worldId."	
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$this->common->executeQuery("commit");
		
		// Обновляем рейтинг
		$this->_updateRanking();
		
		// Заносим его в финальную таблицу
		TN_Search::SaveTurnirRanking($turnirID, $this->worldId);
		
		// Ставим время окончания турнира
		TN_Search::SetTurnirEndDate($turnirID);
		
		$this->_createRankingCSV();
		
		echo "<h1>TURNIR CLOSED</h1>";
		
		actionLog( "Close turnir");
		
		return true;
	}
	
	private function _updateRanking()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
set STATEMENT_TIMEOUT to 0;
begin;

DELETE FROM ds_finalrankinghistory
WHERE
      adddate < (now() - '1 DAY'::interval);

INSERT INTO ds_finalrankinghistory
(thefinal, id_userinstance)
SELECT
  thefinal,
  id_userinstance
FROM ds_finalranking;

DELETE FROM ds_finalranking;

insert into ds_finalranking SELECT ds_user.username,
 ds_combname.combname, ds_alliance.alliancetag,  (ds_ring.ringnumber || '.'
 || ds_combcomplex.complexnumber || '.' || ds_comb.combnumber) as coords,
 ds_localizedtextlanguage.text as racename, qry_overallranking.thefinal,
 ds_userinstance.id_world, ds_userinstance.id_race, ds_user.id_language,
 ds_user.id_user, ds_userinstance.id_userinstance, ds_alliance.id_alliance
 FROM (((((ds_alliance RIGHT JOIN ds_userhasalliance ON
  ds_alliance.id_alliance = ds_userhasalliance.id_alliance
        AND ds_alliance.alliancetag <> '[MIMICRY]' ) RIGHT JOIN
  (((qry_overallranking INNER JOIN (ds_user INNER JOIN ds_userinstance ON
  ds_user.id_user = ds_userinstance.id_user) ON
  qry_overallranking.id_userinstance = ds_userinstance.id_userinstance) INNER
 JOIN ds_race ON
 ds_userinstance.id_race = ds_race.id_race) INNER JOIN
  (ds_localizedtext INNER JOIN ds_localizedtextlanguage ON
  ds_localizedtext.id_localizedtext =
  ds_localizedtextlanguage.id_localizedtext) ON (ds_race.id_localizedname =
  ds_localizedtext.id_localizedtext) AND (ds_user.id_language =
 ds_localizedtextlanguage.id_language)) ON ds_userhasalliance.id_userinstance
 = ds_userinstance.id_userinstance) INNER JOIN ds_comb ON
 ds_userinstance.id_userinstance = ds_comb.id_userinstance) INNER JOIN
 ds_combcomplex ON ds_comb.id_combcomplex = ds_combcomplex.id_combcomplex)
 INNER JOIN ds_ring ON ds_combcomplex.id_ring = ds_ring.id_ring) INNER JOIN
 ds_combname ON ds_comb.id_comb = ds_combname.id_comb WHERE thefinal>220
    AND ds_combcomplex.id_complextype=1 AND ds_user.username NOT IN ('Mimicry');
COMMIT;
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _createRankingCSV()
	{
		$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/ds/csv/TURNIR_FINAL_'.date("Y-d-m").'.csv', "w+");
		
		$this->common->setDbId($this->dbId);
		
		$sql = "
			SELECT 
				fr.username,
				fr.thefinal,
				fr.alliancetag,
				be.value				
			FROM 
				ds_finalranking AS fr
				LEFT JOIN ds_battlexp AS be ON (be.id_userinstance = fr.id_userinstance)
			WHERE
				fr.id_world = ".$this->worldId."
			ORDER BY fr.thefinal DESC
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		foreach($res as $k => $ranking)
		{
			$str = "$k;";
			$str .= $ranking['username'].';';
			$str .= $ranking['thefinal'].';';
			$str .= $ranking['alliancetag'].';';
			$str .= $ranking['value'];
			fwrite($fp, $str."\n");
		}
		
		fclose($fp);
	}
}

$v = new TurnirStop($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
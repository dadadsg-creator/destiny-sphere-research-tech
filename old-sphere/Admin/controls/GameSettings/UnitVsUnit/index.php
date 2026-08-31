<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";

class UnitVsUnit
{
	private $common;
	
	function UnitVsUnit()
	{
		$this->common = &ClassFactory::getInstance("DS_Common");
		
		if(!$this->common->connect())
		{
			echo "Error in DB connection";	
		} else {
			$this->proccessPostData();
		}
	}
	
	/**
 	* Выбор из базы квестовых юнитов(не принадлежащих ни к одной рассе)
 	*
 	* @return array
 	*/
	function selectUniqueUnits()
	{
		$sql = "SELECT
    				u1.id_unit, un.text
				FROM
    				ds_unit as u1
    				INNER JOIN ds_localizedtextlanguage AS un ON un.id_localizedtext = u1.id_localizedname
				WHERE
    				u1.id_unit NOT IN (SELECT id_unit FROM ds_unitcanbebuildbyrace) AND
    				un.id_language = 3
    			ORDER BY
    				u1.id_unit";
	
		return $this->common->getResultArray("", $sql, "", "", "");
	}
	
	/**
 	* Выбор юнитов одной рассы
 	*
 	* @param int $raceId
 	* @return array
 	*/
	function selectRaceUnits($raceId)
	{
		//assert($raceId);
		//global $common;
	
		$sql = "SELECT
    				u1.id_unit, un.text
				FROM
    				ds_unitcanbebuildbyrace as u1
    				INNER JOIN ds_unit AS u2 ON u1.id_unit = u2.id_unit
    				INNER JOIN ds_localizedtextlanguage AS un ON un.id_localizedtext = u2.id_localizedname
				WHERE
    				u1.id_race = $raceId AND
    				u1.id_unit NOT IN (1,2,8) AND
    				un.id_language = 3
    			ORDER BY
    				u1.id_unit";
	
		return $this->common->getResultArray("", $sql, "", "", "");
	}

	/**
 	* Выбор коэффициента атаки юнита против юнита
 	*
 	* @param int $id_unitfrom
 	* @param int $id_unitto
 	* @return array
 	*/
	function selectUnitK($id_unitfrom, $id_unitto)
	{
		//global $common;
		$sql = "SELECT
    				vs.value as k
				FROM
    				ds_unitvsunit AS vs
				WHERE
   					vs.id_unitto = $id_unitto AND
   					vs.id_unitfrom = $id_unitfrom";
	
		$k = $this->common->getResultArray("", $sql, "", "", "");
	
		if(!$k)
		{
			return 'no';
		} else {
			return $k[0]['k'];
		}
	}

	function isRecord($id_unitfrom, $id_unitto, $value)
	{
		//global $common;
		$sql = "SELECT value FROM ds_unitvsunit WHERE id_unitfrom = $id_unitfrom AND id_unitto = $id_unitto";
		$k = $this->common->getResultArray("", $sql, "", "", "");

		if($k)
		{
			if($k[0]['value'] == $value)
			{
				return -1;
			} else {
				return true;
			}
		}
		return false;
	}	
	
	function updateValues()
	{
		//global $common;
	
		if(isset($_REQUEST['unit']) && isset($_REQUEST['units']))
		{
			$id_unitfrom = (int) $_REQUEST['unit'];
			$units = $_REQUEST['units'];
		} else {
			return false;
		}
	
		if(!is_array($units))
		{
			return false;
		}

		foreach($units as $id_unitto=>$value)
		{
			if($value == 'no')
			{
				continue;
			}
		
			$rec = $this->isRecord($id_unitfrom, $id_unitto, $value);

			if($rec === -1)
			{			
				continue;
			}
		
		
			if($rec)
			{
				$sql = "UPDATE 
							ds_unitvsunit
						SET 
							value = $value
						WHERE
							id_unitfrom = $id_unitfrom AND
							id_unitto = $id_unitto";
			} else {
				$sql = "INSERT INTO
							ds_unitvsunit (id_unitfrom, id_unitto, value)
						VALUES($id_unitfrom, $id_unitto, $value)";
			}
		
			$this->common->executeQuery($sql);	
		}
	
		return true;
	}
	
	function getRaceList()
	{
		$sql = "
			SELECT
				l.text, r.id_race
			FROM
				ds_race AS r
				INNER JOIN ds_localizedtextlanguage AS l ON r.id_localizedname = l.id_localizedtext
			WHERE 
				l.id_language = 3 
			";
		
		$k = $this->common->getResultArray("", $sql, "", "", "");
		
		if($k)
		{
			foreach($k as $key => $val)
			{
				$res[$val['id_race']] = $val['text'];
			}
			
			return($res);
		}
		
		return false;
	}
	
	function proccessPostData()
	{
		$tpl = new Smarty;
		$tpl->template_dir = "./";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';	$tpl->compile_check = true;
		$tpl->debugging = false;
		
		if(isset($_REQUEST['change']))
		{
			$this->updateValues();
		}
		
		if(isset($_REQUEST['race_from']) && isset($_REQUEST['race_to']))
		{
			$raceFrom = (int) $_REQUEST['race_from'];
			$raceTo = (int) $_REQUEST['race_to'];
	
	
			$tpl->assign("RACE_FROM", $raceFrom);
			$tpl->assign("RACE_TO", $raceTo);
	
			if($raceFrom)
			{
				$units = $this->selectRaceUnits($raceFrom);
			} else {
				$units = $this->selectUniqueUnits();
			}
		
			if(isset($_REQUEST['unit']))
			{
				$unit = (int) $_REQUEST['unit'];
				$tpl->assign("SELECTED_UNIT", $unit);
				if($_REQUEST['race_to'])
				{
					$vsUnits = $this->selectRaceUnits($raceTo); 
			
				} else {
					$vsUnits = $this->selectUniqueUnits();
				}
		
				$k = array();
				foreach($vsUnits as $key=>$val)
				{
					$k[] = $this->selectUnitK($unit, $val['id_unit']);
				}
			
				$tpl->assign("VS_UNITS", $vsUnits);
				$tpl->assign("K", $k);
		
			} else {
				$tpl->assign("SELECTED_UNIT", -1);
			}
	
			$tpl->assign("UNITS", $units);	
		} else {
			$raceTo = 1;
			$raceFrom = 1;
		}
		
		$races = $this->getRaceList();
		
		$races[0] = 'квестовые';		
		
		/*$races[1] = 'воранеры';
		$races[2] = 'лиенсу';
		$races[3] = 'псолао';	*/
	
	
		$tpl->assign("RACES", $races);
		$tpl->assign("RACE_FROM", $raceFrom);
		$tpl->assign("RACE_TO", $raceTo);

		$tpl->display('UnitsVsUnits.tpl');	
	}
}

$ctrl = new UnitVsUnit();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
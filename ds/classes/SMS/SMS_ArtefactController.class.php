<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для реализации смс услуги перестановки артефактов на соте
 *
 */
class SMS_ArtefactController
{
	
	private $common;
	
	private $dbID;
	
	private $worldID;
	
	private $UIID;
	
	private $comb;
	
	private $combParam;
	
	public function __construct()
	{
		$this->_getParams();
		
		$this->common = DS_Common::CreateForCurentDB($this->dbID);
		
		$userInstance = DE_UserInstance::CreateFromID($this->UIID);
		
		$this->_getComb($userInstance->getWorldID());
		
		$this->_optimizeArtefacts();
	}
	
	private function _getParams()
	{
		assert(key_exists('uiid', $_REQUEST));
		$this->UIID = (int) $_REQUEST['uiid'];
		assert($this->UIID > 0);
		
		assert(key_exists('dbid', $_REQUEST));
		$this->dbID = (int) $_REQUEST['dbid'];
		assert($this->dbID >= 0);
		
		assert(key_exists('comb', $_REQUEST));
		assert(!empty($_REQUEST['comb']));
		$this->combParam = $_REQUEST['comb'];
		
		$_SESSION['dbId'] = $this->dbID;
	}
	
	
	private function _getComb($worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		
		if(ereg("^[1-4]\.[0-9]+\.[1-6]$", $this->combParam))
		{
			$arr = explode('.', $this->combParam);
			$ring = (int) $arr[0];
			$complex = (int) $arr[1];			
			$comb = (int) $arr[2];

			$this->comb = DE_Comb::CreateFromCoordinates($worldID, $ring, $complex, $comb);
			if(is_a($this->comb, 'DS_CombNull'))
			{
				die("0|{ART_MSG_ERROR_COORDS}");
			}
			
			if($this->comb->getUserInstanceID() != $this->UIID)
			{
				die("0|{ART_MSG_ERROR_COORDS_USER}");
			}
		} elseif(VD_CombName::Validate($this->combParam)) {
			$combId = (int)DS_Comb::GetIDByName($this->combParam);
			if($combId == 0)
			{
				die("0|{ART_MSG_ERROR_NAME}");
			}
			
			$this->comb = DE_Comb::CreateFromID($combId);
			
			if($this->comb->getUserInstanceID() != $this->UIID)
			{
				die("0|{ART_MSG_ERROR_NAME_USER}");
			}
		} else {
			die("0|{ART_MSG_ERROR_COMB}");
		}
	}
	
	private function _getAllBuildingArtefacts()
	{
		$sql = "
			SELECT
    			aab.id_artefact
			FROM
    			ds_artefactattachedbuilding AS aab
    			INNER JOIN ds_attachedbuilding AS ab ON (ab.id_attachedbuilding = aab.id_attachedbuilding)
			WHERE
    			ab.id_comb = ".$this->comb->getCombID()."
    			AND
    			(
    				SELECT
    					count(atb.*)
    				FROM
    					ds_artefacttypeforbuilding AS atb
    					INNER JOIN ds_artefactscope AS asp ON (asp.id_artefactscope = atb.id_artefactscope)
    				WHERE
    					atb.id_building = ab.id_building
    					AND
    					asp.scopename IN ('BUILDING', 'SPHERE')
    			) > 0
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _detachAllBuildingArtefactsOnComb()
	{
		$buildingArtefacts = $this->_getAllBuildingArtefacts();
		
		if(count($buildingArtefacts) == 0)
		{
			return 0;
		}
		
		foreach($buildingArtefacts as $k => $artefact)
		{
			$sql = "DELETE FROM ds_artefactattachedbuilding WHERE id_artefact = ".$artefact['id_artefact']."";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			$sql = "
				INSERT INTO ds_combhasartefact
					(id_comb, id_artefact)
				VALUES
					(".$this->comb->getCombID().", ".$artefact['id_artefact'].")
				";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		return count($buildingArtefacts);
	}
	
	
	private function _getBuildingsByResourceProduction($resource)
	{
		assert(in_array($resource, GB_ResourcesList::$allResources));
		
		$sql = "
			SELECT
				DISTINCT ab.id_attachedbuilding,
				(
					SELECT
						bha.value
					FROM
						ds_buildinghasattribute AS bha
						INNER JOIN ds_attribute AS a ON (a.id_attribute = bha.id_attribute)
						INNER JOIN ds_attributename AS an ON (an.id_attributename = a.id_attributename)
					WHERE
						an.attributename = 'ARTEFACT_SLOTS'
						AND
						bha.id_building = ab.id_building
				) as artefact_slots
			FROM
				ds_attachedbuilding AS ab
				INNER JOIN ds_buildinghasattribute AS bha ON (bha.id_building = ab.id_building)
				INNER JOIN ds_attribute AS a ON (a.id_attribute = bha.id_attribute)
				INNER JOIN ds_attributename AS an ON (an.id_attributename = a.id_attributename)
				INNER JOIN ds_attributetype AS at ON (at.id_attributetype = a.id_attributetype)
			WHERE
				ab.id_comb = ".$this->comb->getCombID()."
				AND
				at.typename = 'TURN_PRODUCTION'
				AND
				an.attributename = '$resource'
				AND 
         		ab.underconstruction = false
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _getBuildingArtefactsOnCombByResource($resource)
	{
		assert(in_array($resource, GB_ResourcesList::$allResources));
		
		$sql = "
			SELECT
				cha.id_artefact,
				ahb.value
			FROM
				ds_combhasartefact AS cha
				INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = cha.id_artefact)
				INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = ai.id_artefact)
				INNER JOIN ds_attribute AS a ON (a.id_attribute = ahb.id_attribute)
				INNER JOIN ds_attributename AS an ON (an.id_attributename = a.id_attributename)
				INNER JOIN ds_attributetype AS at ON (at.id_attributetype = a.id_attributetype)
				
			WHERE
			    cha.id_comb = ".$this->comb->getCombID()."
			    AND
			    an.attributename = '$resource'
			    AND
			    at.typename = 'BONUS_ABSOLUTE'
            ORDER BY ahb.value DESC
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _attachBuildingArtefact($id_building, $id_artefact)
	{
		assert(is_int($id_building) && $id_building > 0);
		assert(is_int($id_artefact) && $id_artefact > 0);
		
		$sql = "DELETE FROM ds_combhasartefact WHERE id_comb = ".$this->comb->getCombID()." AND id_artefact = $id_artefact";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$sql = "
			INSERT INTO ds_artefactattachedbuilding
				(id_artefact, id_attachedbuilding)
			VALUES
				($id_artefact, $id_building)
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	
	private function _attachAllBuildingArtefacts()
	{
		$totalCountAttached = 0;
		
		foreach(GB_ResourcesList::$allResources as $k => $resource) 
		{
			$buildingList = $this->_getBuildingsByResourceProduction($resource);
			
			$artefactList = $this->_getBuildingArtefactsOnCombByResource($resource);
			
			$i = 0;
			
			foreach($buildingList as $k => $building)
			{
				for($j=0; $j<$building['artefact_slots']; $j++)
				{
					if(!isset($artefactList[$i]))
					{
						continue 2;
					}
					$this->_attachBuildingArtefact((int)$building['id_attachedbuilding'], (int)$artefactList[$i]['id_artefact']);
					unset($artefactList[$i]);
					$i++;
				}
			}
			
			$totalCountAttached += $i;
		}
		
		return $totalCountAttached;
	}
	
	private function _patchCombBonus()
	{
		MP_CombBonus::$common = $this->common;
		
		$data['id_comb'] = $this->comb->getCombID();
		
		$patch = new MP_CombBonus($data);
		
		MP_CombBonus::$disableLog = true;
		
		ob_start();
		$patch->patch();
		ob_end_clean();
	}
	
	private function _getCombBonus()
	{
		$attr = $this->comb->getAttributes();
		
		$res = array();
		
		foreach(GB_ResourcesList::$allResources as $k => $resource)
		{
			$res[] = substr($resource, 0, 1)."=".$attr->getAttribute('BONUS_ABSOLUTE', $resource);
		}
		
		return implode('; ', $res);
	}
	
	private function _optimizeArtefacts()
	{
		$bonusBefore = $this->_getCombBonus();
		
		$this->common->startTrans();
		
		$detachedArts = $this->_detachAllBuildingArtefactsOnComb();
		
		$attachedArts = $this->_attachAllBuildingArtefacts();
		
		$this->_patchCombBonus();
		
		$this->common->closeTrans("COMMIT");
		
		GB_SingletonObjectStorage::DeleteObject($this->comb->getAttributes());
		$bonusAfter = $this->_getCombBonus();
		
		die("1|{ART_MSG_START}: $detachedArts {ART_MSG_ARTS} ({ART_MSG_BONUS}: $bonusBefore). {ART_MSG_END}: $attachedArts ($bonusAfter)");
	}
}

?>

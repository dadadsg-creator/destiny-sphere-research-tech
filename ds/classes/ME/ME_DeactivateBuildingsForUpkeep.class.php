<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Деактивация зданий при нехватке энергии 
 *
 */
class ME_DeactivateBuildingsForUpkeep
{
	/**
	 * Enter description here...
	 *
	 * @param DE_UserInstance $userInstance
	 * @param DE_Comb $comb
	 * @param float $needEnergy
	 * @param float $haveEnergy
	 * @param float $turns
	 */
	static public function Exec(DE_Comb $comb, $needEnergy, $haveEnergy, $turns)
	{
		assert(is_float($needEnergy) && $needEnergy > 0);
		assert(is_float($haveEnergy));
		assert($haveEnergy < $needEnergy);
		assert(is_float($turns) && $turns > 0);
				
		
		$common = DS_Common::CreateForCurentDB();	
		
		$diff = $needEnergy - $haveEnergy;		

		$userInstance = $comb->getUserInstance();
			
		$deact = new UA_DeactivateBuilding;

		$id_attributename = DS_Misc::GetResourceIDByName('STASIS_ENERGY');

		// список зданий для деактивации
		$sql = "SELECT
      				ab.id_attachedbuilding,
      				ab.id_building,
      				bha.value as upkeep,
      				COALESCE(bdp.priority, 10000000) as priority,
      				COALESCE
      				(
        				(
          					SELECT SUM(ahb.value) AS bonus
          					FROM
            					ds_artefactattachedbuilding AS aab
            					INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = aab.id_artefact)
            					INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = ai.id_artefact)
          					WHERE
            					aab.id_attachedbuilding = ab.id_attachedbuilding
        				),
        				0
      				) as bonus
				FROM
    				ds_attachedbuilding AS ab
    				LEFT JOIN ds_buildingdeactivatepriority AS bdp ON (bdp.id_building = ab.id_building)
    				INNER JOIN ds_attachedbuildinghasattribute AS abha ON (abha.id_attachedbuilding = ab.id_attachedbuilding)
    				INNER JOIN ds_buildinghasattribute AS bha ON (bha.id_building = ab.id_building)
				WHERE
     				ab.id_comb = ".$comb->getID()."
     				AND
     				abha.id_attribute = 42 -- CURRENT_FLAG => DEACTIVEABLE
     				AND
     				bha.id_attribute = 7 -- TURN_UPKEEP => STASIS_ENERGY
     				AND
     				ab.active = true
				ORDER BY priority, bonus";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		foreach($res as $k=>$v)
		{			

			$diff = $diff - $v['upkeep'] * $turns;

			$deact->todeact[$v['id_attachedbuilding']] = $v['id_building'];
			$deact->getBuildingToDeact($v['id_building'], $comb->getID());

			if($diff <= 0)
			{
				break;
			}
		}

		// деактивируем
		if(sizeof($deact->todeact > 0))
		{
			if(!$deact->deactivate($deact->todeact, $comb->GetID(), $userInstance->getID() , $userInstance->getUser()->getLanguageID()))
			{
				ds_error($deact->errormsg);
			}
		}

		//Рестартим вид домашней соты
		CR_CombView::CreateFromCombID((int)$comb->getID())->resetBuildings(1);

		//Рестартим вид центра
		CR_CombView::CreateFromCombID((int)$comb->getID())->resetBuildings(2);

		//Рестартим вид нейтрала
		CR_CombView::CreateFromCombID((int)$comb->getID())->resetBuildings(3);
		
	}
}





?>
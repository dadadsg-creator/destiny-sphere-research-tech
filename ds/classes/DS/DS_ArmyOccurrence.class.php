<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Статический класс бибилотека запросов связанная с событиями армий.
 */
class DS_ArmyOccurrence extends DS_Base
{
	/**
	* @return Возвращяет события, связанные с армией и конкретным событием	
	* @param ID армии $armyId
	* @desc Возвращяет сведения по армии по ID
	*/
	static public function GetArmysOccurrencesAction($armyID, $occurrenceName)
	{
		settype ($armyID, 'integer');
		assert ($armyID > 0);
		
		$common = self::getCommon();
						
		$sql = "SELECT
					*
				FROM
					ds_unitcontaineroccurrence uco
					LEFT JOIN ds_occurrence o on (o.id_occurrence = uco.id_occurrence)
					LEFT JOIN ds_occurrenceaction oa on (oa.id_occurrenceaction = uco.id_occurrenceaction)
				WHERE
					uco.id_unitcontainer = $armyID
					 AND
					o.occurrence_name = '".$occurrenceName."'";
			
		
		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($common->errormsg));

		return $res;
	}
	
	
	/**
	* @return Удаление события
	* @param ID события $unitcontainerOccurrenceId
	* @desc 
	*/
	static public function DeleteOccurrence($unitcontainerOccurrenceId)
	{
		settype ($unitcontainerOccurrenceId, 'integer');
		assert ($unitcontainerOccurrenceId > 0);
		
		$common = self::getCommon();
						
		$sql = "DELETE
				FROM
					ds_unitcontaineroccurrence 
				WHERE
					id_unitcontaineroccurrence = $unitcontainerOccurrenceId";
			
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	/**
	* @return Создание события	
	* @param ID армии $armyId
	* @desc Возвращяет сведения по армии по ID
	*/	
	
	static public function CreateOccurrence(DE_army $army, $occurrenceName, $occurrenceActionName)
	{
		assert(!empty($occurrenceName));
		assert(!empty($occurrenceActionName));
		
		$common = self::getCommon();
						
		$sql = "
				INSERT INTO
					ds_unitcontaineroccurrence
						(id_unitcontainer, id_occurrence, id_occurrenceaction)
					VALUES
					(".$army->getID().", 
						(SELECT 
							id_occurrence 
						FROM 
							ds_occurrence 
						WHERE 
							occurrence_name = '".$occurrenceName."') ,
							 
						(SELECT 
							id_occurrenceaction 
						FROM 
							ds_occurrenceaction 
						WHERE 
							occurrenceaction_name = '".$occurrenceActionName."'))";
		
			
		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}

	static public function GetArmysOccurrenceByOccurrenceAndAction(DE_army $army, $occurrenceName, $occurrenceActionName)
	{
		assert(!empty($occurrenceName));
		assert(!empty($occurrenceActionName));
		
		$common = self::getCommon();
						
		$sql = "SELECT
					*
				FROM
					ds_unitcontaineroccurrence uco
					LEFT JOIN ds_occurrence o on (o.id_occurrence = uco.id_occurrence)
					LEFT JOIN ds_occurrenceaction oa on (oa.id_occurrenceaction = uco.id_occurrenceaction)
				WHERE
					uco.id_unitcontainer = ".$army->getID()."
					 AND
					o.occurrence_name = '".$occurrenceName."'
					 AND
					oa.occurrenceaction_name = '".$occurrenceActionName."'";
		
			
		$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty($common->errormsg));

		return $res;
	}

}//END OF CLASS


?>
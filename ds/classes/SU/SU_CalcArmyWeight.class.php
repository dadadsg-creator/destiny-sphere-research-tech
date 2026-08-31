<?php $CVS_Version = '$Revision$ ; $Date$';
// Расчет веса армий всех игроков


class SU_CalcArmyWeight
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $dbId;
	
	//static private $worldId;
	
	static public function Exec($dbId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		
		self::$dbId = $dbId;
		
		self::_out("===================================");
		self::_out("START CALC DB ".self::$dbId);
		
		if(GameDown(self::$dbId))
		{
			self::_out("GAME IS CLOSED");
			return false;
		}
		
		self::$common = DS_Common::CreateForCurentDB(self::$dbId);
		
		/* Расчет продовеса армий без учета трансфера и рабочих, необходимых для постройки юнита
		$sql = "
			INSERT INTO 
				ds_army_weight 
				(id_userinstance, army_weight_built, army_weight_lost)
					SELECT
					    ui.id_userinstance,
					    sum(uha.value * r.exchangerate * sa.sum)/10 as prod_value,
					    0  
					FROM
					    ds_unit AS u
					    JOIN subqry_armyranking AS sa ON (u.id_unit = sa.id_unit)
					    JOIN ds_userinstance AS ui ON (sa.id_userinstance = ui.id_userinstance)
					    JOIN ds_unithasattribute AS uha ON (u.id_unit = uha.id_unit)
					    JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
					    JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
					WHERE
					    uha.id_attribute IN (1,2,3,4,5)
					GROUP BY ui.id_userinstance
			";*/
		
		$sql = '
		CREATE OR REPLACE VIEW "public"."subqry_armyranking_with_transport" (
		    sum,
		    id_unit,
		    id_userinstance)
		AS
		SELECT sum(ds_unitcontainerhasunit.amount) AS sum,
		    ds_unitcontainerhasunit.id_unit, ds_unitcontainer.id_userinstance
		FROM ds_unitcontainerhasunit, ds_unitcontainer
		WHERE (ds_unitcontainerhasunit.id_army = ds_unitcontainer.id_unitcontainer)
		    AND (ds_unitcontainer.id_unitcontainertype = 1)
		GROUP BY ds_unitcontainerhasunit.id_unit, ds_unitcontainer.id_userinstance;
		
		delete from ds_army_weight;
		
		INSERT INTO 
                ds_army_weight 
                (id_userinstance, army_weight_built, army_weight_lost)
                        
                     SELECT
                         ui.id_userinstance,
                        (
                            COALESCE(sum(uha.value * r.exchangerate * sa.sum), 0)
                         +                       
                             COALESCE((
                                 SELECT 
                                    sum(uha.value * r.exchangerate * eho4.value)
                                FROM ds_eventqueue eq  
                                LEFT JOIN ds_eventhasoption eho ON 
                                (eho.id_eventqueue = eq.id_eventqueue AND eho.id_eventoption = 179) -- ID_CATEGORY
                                LEFT JOIN ds_eventhasoption eho2 ON 
                                (eho2.id_eventqueue = eq.id_eventqueue AND eho2.id_eventoption = 180) -- ID_ARTICLE
                                LEFT JOIN ds_eventhasoption eho3 ON 
                                (eho3.id_eventqueue = eq.id_eventqueue AND eho3.id_eventoption = 178) -- ID_UserInctance
                                LEFT JOIN ds_eventhasoption eho4 ON 
                                (eho4.id_eventqueue = eq.id_eventqueue AND eho4.id_eventoption = 181) -- Amount
                                LEFT JOIN ds_unithasattribute AS uha ON (eho2.value = uha.id_unit)
                                LEFT JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                                LEFT JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
                                WHERE 
                                    eq.id_event = 26 -- TRANSFER_RESOURCE
                                     AND 
                                    eho.value = 2 -- Type is units
                                     AND
                                    eho3.value = ui.id_userinstance -- Id_userinstance
                                     AND  
                                    uha.id_attribute IN (1,2,3,4,5,6)
                                ),0)
                        )/10,
                        0  
                    FROM
                        ds_unit AS u
                        JOIN subqry_armyranking_with_transport AS sa ON (u.id_unit = sa.id_unit)
                        JOIN ds_userinstance AS ui ON (sa.id_userinstance = ui.id_userinstance)
                        JOIN ds_unithasattribute AS uha ON (u.id_unit = uha.id_unit)
                        JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                        JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
                    WHERE
                        uha.id_attribute IN (1,2,3,4,5,6)
                    GROUP BY ui.id_userinstance';
		
		// Пересчет построенного продовеса 
		/*
		UPDATE ds_army_weight
					SET army_weight_built =  
					(
					 SELECT
                        -- ui.id_userinstance,
                        (
                            COALESCE(sum(uha.value * r.exchangerate * sa.sum), 0)
                         +                       
                             COALESCE((
                                 SELECT 
                                    sum(uha.value * r.exchangerate * eho4.value)
                                FROM ds_eventqueue eq  
                                LEFT JOIN ds_eventhasoption eho ON 
                                (eho.id_eventqueue = eq.id_eventqueue AND eho.id_eventoption = 179) -- ID_CATEGORY
                                LEFT JOIN ds_eventhasoption eho2 ON 
                                (eho2.id_eventqueue = eq.id_eventqueue AND eho2.id_eventoption = 180) -- ID_ARTICLE
                                LEFT JOIN ds_eventhasoption eho3 ON 
                                (eho3.id_eventqueue = eq.id_eventqueue AND eho3.id_eventoption = 178) -- ID_UserInctance
                                LEFT JOIN ds_eventhasoption eho4 ON 
                                (eho4.id_eventqueue = eq.id_eventqueue AND eho4.id_eventoption = 181) -- Amount
                                LEFT JOIN ds_unithasattribute AS uha ON (eho2.value = uha.id_unit)
                                LEFT JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                                LEFT JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
                                WHERE 
                                    eq.id_event = 26 -- TRANSFER_RESOURCE
                                     AND 
                                    eho.value = 2 -- Type is units
                                     AND
                                    eho3.value = ui.id_userinstance -- Id_userinstance
                                     AND  
                                    uha.id_attribute IN (1,2,3,4,5,6)
                                ),0)
                        )/10
                    FROM
                        ds_unit AS u
                        JOIN subqry_armyranking_with_transport AS sa ON (u.id_unit = sa.id_unit)
                        JOIN ds_userinstance AS ui ON (sa.id_userinstance = ui.id_userinstance)
                        JOIN ds_unithasattribute AS uha ON (u.id_unit = uha.id_unit)
                        JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                        JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
                    WHERE
                        uha.id_attribute IN (1,2,3,4,5,6)
                        AND ds_army_weight.id_userinstance = ui.id_userinstance
                    GROUP BY ui.id_userinstance
                    
                
 		)
		*/
		// Расчет без транспортных юнитов
		/*$sql = "
			INSERT INTO 
				ds_army_weight 
				(id_userinstance, army_weight_built, army_weight_lost)
						
					 SELECT
					 	ui.id_userinstance,
					    (
					    	COALESCE(sum(uha.value * r.exchangerate * sa.sum), 0)
					     +                       
					     	COALESCE((
					     		SELECT 
									sum(uha.value * r.exchangerate * eho4.value)
								FROM ds_eventqueue eq  
								LEFT JOIN ds_eventhasoption eho ON 
								(eho.id_eventqueue = eq.id_eventqueue AND eho.id_eventoption = 179) -- ID_CATEGORY
								LEFT JOIN ds_eventhasoption eho2 ON 
								(eho2.id_eventqueue = eq.id_eventqueue AND eho2.id_eventoption = 180) -- ID_ARTICLE
								LEFT JOIN ds_eventhasoption eho3 ON 
								(eho3.id_eventqueue = eq.id_eventqueue AND eho3.id_eventoption = 178) -- ID_UserInctance
								LEFT JOIN ds_eventhasoption eho4 ON 
								(eho4.id_eventqueue = eq.id_eventqueue AND eho4.id_eventoption = 181) -- Amount
								LEFT JOIN ds_unithasattribute AS uha ON (eho2.value = uha.id_unit)
								LEFT JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
								LEFT JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
								WHERE 
									eq.id_event = 26 -- TRANSFER_RESOURCE
									 AND 
									eho.value = 2 -- Type is units
									 AND
									eho3.value = ui.id_userinstance -- Id_userinstance
									 AND  
									uha.id_attribute IN (1,2,3,4,5,6)
								),0)
						)/10,
                        0  
                    FROM
                        ds_unit AS u
                        JOIN subqry_armyranking_with_transport AS sa ON (u.id_unit = sa.id_unit)
                        JOIN ds_userinstance AS ui ON (sa.id_userinstance = ui.id_userinstance)
                        JOIN ds_unithasattribute AS uha ON (u.id_unit = uha.id_unit)
                        JOIN ds_attribute AS a ON (uha.id_attribute = a.id_attribute)
                        JOIN ds_resource AS r ON (a.id_attributename = r.id_attributename)
                    WHERE
                        uha.id_attribute IN (1,2,3,4,5,6)
                    GROUP BY ui.id_userinstance
			";
		$res = self::$common->getresultArray("", $sql, "", "", "");		
		assert(empty(self::$common->errormsg));*/
		
		self::_out("DONE");
		self::_out("===================================");
	}

	private function _out($msg = '')
	{		
		$str = date('Y-m-d H:i:s')." ".$msg."\n";
		
		if(php_sapi_name() == 'apache')
		{	
			echo $str;
			flush(); 
		}
		
		SU_DebugTools::WriteToLog($str, 'CalcArmyWeight.log');
	}
}
?>
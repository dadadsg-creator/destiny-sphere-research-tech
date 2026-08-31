<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * 
 */
class DS_Terraforming extends DS_Base
{
	static public function GetAttributesByID($terraformingID)
	{
		settype ( $terraformingID, 'integer' );
		assert ( $terraformingID > 0 );
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					att.typename,
    				atn.attributename,
    				ta.value 
				FROM 
					ds_terraforminghasattribute as ta
					INNER JOIN ds_attribute as at ON (ta.id_attribute = at.id_attribute)
    				INNER JOIN ds_attributename as atn ON (at.id_attributename = atn.id_attributename)
    				INNER JOIN ds_attributetype as att ON (at.id_attributetype = att.id_attributetype)
				WHERE ta.id_terraforming = ".$terraformingID;		
		$res = $common->getResultArray(parent::FormMark(__METHOD__, $terraformingID), $sql, "", "", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		
		
		return $res;		
	}
	
	static public function GetTerraformingInfoByID($terraformingID)
	{
		settype ( $terraformingID, 'integer' );
		assert ( $terraformingID > 0 );
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					*
				FROM 
					ds_terraforming as t					
				WHERE t.id_terraforming = ".$terraformingID;		
		$res = $common->getResultArray(parent::FormMark(__METHOD__, $terraformingID), $sql, "", "", "GLOBAL_CACHE");
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		
		return $res[0];		
	}
	
	static public function GetAllowFormingsByCellIDAndUIID($cellID, $uiid)
	{
		settype($cellID, 'integer');
		assert($cellID > 0);
		
		settype($uiid, 'integer');
		assert($uiid > 0);
		
		$common = self::getCommon();
		
		$sql = "
			SELECT
    			t.*
			FROM
    			ds_cell as c
    			INNER JOIN ds_terraforming as t On (c.id_groundtype = t.id_groundtypefrom)
			WHERE
     			c.id_cell = ".$cellID."     			
     			AND
				(
					(
						SELECT COUNT(sc.id_science)
						FROM
							ds_science sc,
							ds_terraformingneedscience bns
						WHERE
							sc.id_science = bns.id_science
							AND 
							bns.id_terraforming = t.id_terraforming
					)
					=
					(
						SELECT COUNT(bns.id_science)
						FROM
							ds_terraformingneedscience bns,
							ds_userhasscience uhs,
							ds_userinstance ui
						WHERE
							bns.id_terraforming = t.id_terraforming
							AND 
							bns.id_science = uhs.id_science
							AND 
							uhs.id_userinstance = ui.id_userinstance
							AND 
							ui.id_userinstance = ".$uiid."
							AND 
							uhs.inresearch = false
					)
				) IS true";		
		$res = $common->getResultArray("", $sql, "", "", "");
		
		assert(empty($common->errormsg));

		return $res;		
	}
			
	static public function GetGroudTypeByID($groundTypeID)
	{
		settype ( $groundTypeID, 'integer' );
		assert ( $groundTypeID > 0 );
		
		$common = self::getCommon();
		
		$sql = "SELECT *
				FROM ds_groundtype
				WHERE id_groundtype = ".$groundTypeID;		
		$res = $common->getResultArray(parent::FormMark(__METHOD__, $groundTypeID), $sql, "", "", "GLOBAL_CACHE");
		assert(empty($common->errormsg));

		assert(count($res) == 1);	
		
		return $res[0];		
	}
	
	static public function ValidateCellIDInAction($cellID, $combID)
	{
		settype($cellID, 'integer');
		assert($cellID > 0);
		
		settype($combID, 'integer');
		assert($combID > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					c.id_groundtype
				FROM 
					ds_attachedbuilding as ab
					INNER JOIN ds_cell as c ON (c.id_cell = ab.id_cell)
				WHERE 
					ab.id_comb = ".$combID."
					AND
					ab.id_building = 64
					AND
					ab.id_cell = ".$cellID."
					";		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		
		if(count($res) == 0)
		{	
			return 0;
		}else{
			return (int)$res[0]['id_groundtype'];
		}
	}
	
	static public function ValidateTerraformingType($groundTypeIDFrom, $groundTypeNameTo, $uiid)
	{
		settype($groundTypeIDFrom, 'integer');
		assert($groundTypeIDFrom > 0);
		
		settype($uiid, 'integer');
		assert($uiid > 0);
		
		assert(!empty($groundTypeNameTo));
		
		$common = self::getCommon();
		
		$sql = "
				SELECT t.id_terraforming
				FROM ds_terraforming as t
				WHERE
     				t.id_groundtypefrom = ".$groundTypeIDFrom."
     				AND
     				t.id_groundtypeto = 
     				(
         				SELECT gt1.id_groundtype
         				FROM ds_groundtype as gt1
         				WHERE gt1.groundname = '".$groundTypeNameTo."'
     				)
     				AND
	 				(
						(
							SELECT COUNT(sc.id_science)
							FROM
								ds_science sc,
								ds_terraformingneedscience bns
							WHERE
								sc.id_science = bns.id_science
								AND
								bns.id_terraforming = t.id_terraforming
						)
						=
						(
							SELECT COUNT(bns.id_science)
							FROM
								ds_terraformingneedscience bns,
								ds_userhasscience uhs,
								ds_userinstance ui
							WHERE
								bns.id_terraforming = t.id_terraforming
								AND
								bns.id_science = uhs.id_science
								AND
								uhs.id_userinstance = ui.id_userinstance
								AND
								ui.id_userinstance = ".$uiid."
								AND
								uhs.inresearch = false
						)
					) IS true";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if(count($res) == 1)
		{
			return (int)$res[0]['id_terraforming'];
		}else{
			return 0;
		}				
	}
	
	
	static public function HaveTerraformingOnCell($cellID)
	{
		settype($cellID, 'integer');
		assert($cellID > 0);
						
		$common = self::getCommon();
		
		$sql = "SELECT *
				FROM ds_terraformingbindeventqueue
				WHERE id_cell = $cellID";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	
		assert(count($res) == 1 || count($res) == 0);
		
		if(count($res) == 1)
		{
			return (int)$res[0]['id_eventqueue'];
		}else{
			return 0;
		}				
	}
	
	static public function SetNewGroundTypeOnCellByTerraformingID($terraformingID, $cellID)
	{
		settype($terraformingID, 'integer');
		assert($terraformingID > 0);
		
		settype($cellID, 'integer');
		assert($cellID > 0);		
		
		$common = self::getCommon();
		
		$sql = "UPDATE ds_cell
				SET id_groundtype = 
					(
						SELECT t.id_groundtypeto
						FROM ds_terraforming as t
						WHERE t.id_terraforming = ".$terraformingID."
					)
				WHERE id_cell = ".$cellID;
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}	
	
	static public function SetOldGroundTypeOnCellByTerraformingID($terraformingID, $cellID)
	{
		settype($terraformingID, 'integer');
		assert($terraformingID > 0);
		
		settype($cellID, 'integer');
		assert($cellID > 0);		
		
		$common = self::getCommon();
		
		$sql = "UPDATE ds_cell
				SET id_groundtype = 
					(
						SELECT t.id_groundtypefrom
						FROM ds_terraforming as t
						WHERE t.id_terraforming = ".$terraformingID."
					)
				WHERE id_cell = ".$cellID;
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function BindEventQueue($cellID, $eventQueueID)
	{		
		settype($cellID, 'integer');
		assert($cellID > 0);
		
		settype($eventQueueID, 'integer');
		assert($eventQueueID > 0);
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO  ds_terraformingbindeventqueue
				(id_cell, id_eventqueue)
				VALUES
				($cellID, $eventQueueID)";
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function UnBindEventQueue($cellID)
	{		
		settype($cellID, 'integer');
		assert($cellID > 0);		
		
		$common = self::getCommon();
		
		$sql = "DELETE FROM  ds_terraformingbindeventqueue
				WHERE id_cell = $cellID";
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	
	
	static public function DeleteTerraformer($cellID)
	{		
		settype($cellID, 'integer');
		assert($cellID > 0);			
		
		$common = self::getCommon();
		
		$sql = "DELETE FROM ds_attachedbuilding WHERE id_cell = ".$cellID;
		$res = $common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
}//END OF CLASS

?>
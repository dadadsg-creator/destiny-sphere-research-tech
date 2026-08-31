<?php

class AddArtefactSearch
{
	static public function GetWorldList(DS_Common $common)
	{
		$sql = "	
			SELECT
				*
			FROM
				ds_worldcollection
			WHERE
				world_identifier != 'UC'
			";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetArtefactScopes(DS_Common $common)
	{
		$sql = "
			SELECT
				ap.id_artefactscope as id,
				COALESCE(ltl.text, ap.scopename) AS name
			FROM
				ds_artefactscope AS ap
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ap.id_localizedscope AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
			";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetArtefactBonuses(DS_Common $common)
	{
		$sql = "
			SELECT DISTINCT
      			att.id_attributename as id,
      			COALESCE(ltl.text, an.attributename) as name
			FROM
    			ds_artefact AS a
    			INNER JOIN ds_artefacthasbonus AS ahb ON (a.id_artefact = ahb.id_artefact)
    			INNER JOIN ds_attribute AS att ON (ahb.id_attribute = att.id_attribute)
    			INNER JOIN ds_attributename AS an ON (att.id_attributename = an.id_attributename)
    			LEFT JOIN ds_localizedtextlanguage AS ltl ON (an.id_localizedname = ltl.id_localizedtext AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
			";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetArtefactSizes(DS_Common $common)
	{
		$sql = "
			SELECT
      			apa.id_artefactprobability as id,
      			COALESCE(ltl.text, apa.prefix) as name
			FROM
    			ds_artefactprobability AS apa
    			LEFT JOIN ds_localizedtextlanguage AS ltl ON (apa.id_localizedprefix = ltl.id_localizedtext AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
			ORDER BY
				apa.id_artefactprobability
			";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetArtefactList(DS_Common $common, ArtFilter $filter)
	{
		$where = 'true';
		
		if($filter->currentScope > 0)
		{
			$where .= " AND a.id_artefactscope = ".$filter->currentScope;
		}
		
		if($filter->currentBonus > 0)
		{
			$where .= " AND at.id_attributename = ".$filter->currentBonus;
		}
		
		if($filter->currentSize > 0)
		{
			$where .= " AND a.id_artefactprobability = ".$filter->currentSize;
		}
		
		$sql = "
			SELECT
				a.id_artefact,
				COALESCE(ltl.text, a.artefactname) AS artefact_name
			FROM
				ds_artefact AS a
				LEFT JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
				LEFT JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = a.id_localizedname AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
			WHERE
				$where
			ORDER BY a.id_artefactscope, a.id_artefactprobability, a.id_artefacttype
			";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function AddArtefacts(DS_Common $common, DE_User $user, ArtList $addArtefacts, $worldID, $lifeTime)
	{
		assert(is_int($worldID) && $worldID > 0);
		
		$common->startTrans();
		
		foreach ($addArtefacts->getArtListForAdd() as $k => $art)
		{
			for ($i=0; $i<$art['count']; $i++)
			{
				$sql = "
					SELECT
						add_artefact_to_comb(c.id_comb, ".$art['id_artefact'].", $lifeTime)
					FROM
						ds_comb AS c
						INNER JOIN ds_userinstance AS ui USING (id_userinstance)
						INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
						INNER JOIN ds_ring AS r USING (id_ring) 
					WHERE
						ui.id_world = ".$worldID."
						AND
						ui.id_user = ".$user->getID()."
						AND
						r.ringnumber != 4
					";
				$common->executeQuery($sql);
				assert(empty($common->errormsg));
			}
		}
		
		$common->closeTrans('COMMIT');
	}
	
	static public function IsUserRegisteredInWorld(DS_Common $common, DE_User $user, $worldID)
	{
		assert(is_int($worldID) && $worldID > 0);
		
		$sql = "SELECT 
					id_userinstance
				FROM 
					ds_userinstance
				WHERE 
					id_user = ".$user->getID()." 
					 AND 
					id_world = ".$worldID."
					";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		if(count($res) > 0)
		{
			return true;
		}

		return false;
	}
}

?>
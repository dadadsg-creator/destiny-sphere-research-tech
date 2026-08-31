<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/12/20 08:20:42 $';

class MP_DBAllianceMembers extends MP_Patch
{
	public function __construct()
	{
		echo "CONSTRUCT !!!";
	}
	
	public function patch()
	{
		$countAlWithInvalidCount = $this->_getCountInvalidAlliances();
		
		if($countAlWithInvalidCount > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".$countAlWithInvalidCount." alliances with wrong members amount.";
			self::_out($str);				
			if(self::$onlyValidate)
			{
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
				echo $str;
			} else {
				$this->_patchMembersCount();
			}
		}
		
		$countEmptyAlliances = $this->_getCountEmptyAlliances();
		
		if($countEmptyAlliances > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".$countEmptyAlliances." empty alliances.";
			self::_out($str);				
			if(self::$onlyValidate)
			{
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			} else {
				$this->_patchEmptyAlliances();
			}
		}
		
		$countAllianceWithoutLeaders = $this->_getCountAlliancesWithoutLeader();
		
		if($countAllianceWithoutLeaders > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".$countAllianceWithoutLeaders." without leaders.";
			self::_out($str);
			if(self::$onlyValidate)
			{
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			} else {
				$this->_patchAllianceLeaders();
			}
		}
	}
	
	private function _getCountAlliancesWithoutLeader()
	{
		$sql = "	
			SELECT
				count(*)
			FROM
				ds_alliance AS al
			WHERE
				NOT EXISTS (
					SELECT
						uhas.id_userinstance
					FROM
						ds_userhasalliancestatus AS uhas
					WHERE
						uhas.id_alliance = al.id_alliance
						AND
						uhas.id_status = 1
				)
				AND al.id_global_alliance != NULL 
			";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _patchAllianceLeaders()
	{
		$sql = "
			INSERT INTO ds_userhasalliancestatus
				(id_status, id_alliance, id_userinstance)
			SELECT
				(
					SELECT
						id_status
					FROM
						ds_useralliancestatus
					WHERE
						statusname = 'LEADER'						
				),
				al.id_alliance,
				uha.id_userinstance
			FROM
				ds_alliance AS al
				INNER JOIN ds_userhasalliance AS uha USING (id_alliance)
			WHERE
				NOT EXISTS (
					SELECT
						uhas.id_userinstance
					FROM
						ds_userhasalliancestatus AS uhas
					WHERE
						uhas.id_alliance = al.id_alliance
						AND
						uhas.id_status = 1
				)
				AND
				uha.joindate =
                (
                    SELECT
                        min(uha1.joindate)
                    FROM
                        ds_userhasalliance AS uha1
                    WHERE
                        uha1.id_alliance = al.id_alliance
                    LIMIT 1
				)
				AND al.id_global_alliance != NULL
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	private function _getCountInvalidAlliances()
	{
		$sql = "
			SELECT
				count(*)
			FROM
				ds_alliance AS al
			WHERE
				al.members_amount != 
				(
					SELECT
						count(*)
					FROM
						ds_userhasalliance AS uha
					WHERE
						uha.id_alliance = al.id_alliance
				)
			";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _getCountEmptyAlliances()
	{
		$sql = "
			SELECT
				count(*)
			FROM
				ds_alliance AS al
			WHERE
				(
					SELECT
						count(*)
					FROM
						ds_userhasalliance AS uha
					WHERE
						uha.id_alliance = al.id_alliance
				) = 0
			";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _patchMembersCount()
	{
		// патчим кол-во участников альянсов
		$sql = "
			UPDATE 
				ds_alliance 
			SET 
				members_amount = (
					SELECT
						count(*)
					FROM
						ds_userhasalliance AS uha
					WHERE
						uha.id_alliance = ds_alliance.id_alliance
				)
			WHERE
				members_amount != (
					SELECT
						count(*)
					FROM
						ds_userhasalliance AS uha
					WHERE
						uha.id_alliance = ds_alliance.id_alliance
				)
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	private function _patchEmptyAlliances()
	{
		// удаляем пустые альянсы
		$sql = "
			DELETE FROM
				ds_alliance
			WHERE
			id_alliance IN
				(
					SELECT
						id_alliance
					FROM
				    	ds_alliance as a
					WHERE
						 NOT EXISTS (
                             SELECT uha.id_userinstance
                             FROM ds_userhasalliance AS uha
                             WHERE uha.id_alliance = a.id_alliance
                                   )
			)
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
}

?>
<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * НАуки пользователя которые числяться в изученеи а ивента нет
 */

class MP_DBScienceResearchWithoutEvent  extends MP_Patch 
{			
	public function patch()
	{
		$sql = "
			SELECT
      			uhs.id_userinstance,
      			uhs.id_science
			FROM
    			ds_userhasscience AS uhs
			WHERE
     			uhs.inresearch = true
     			AND NOT EXISTS
     			(
      				SELECT *
      				FROM ds_userhassciencebindeventqueue AS uhsbeq
      				WHERE
           				uhsbeq.id_userinstance = uhs.id_userinstance
           				AND
           				uhsbeq.id_science = uhs.id_science
     			)";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
	

		if(count($res) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($res)." Sciences in in research without events.";
			self::_out($str);	
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				$this->_repair($res);
			}	
		}				
	}

	private function _repair($res)
	{
		assert(is_array($res) && count($res) > 0);
		
		foreach($res as $k=>$data)
		{			
			$sql = "UPDATE ds_userhasscience 
					SET 
						inresearch = false						
					WHERE 
						id_userinstance = ".$data['id_userinstance']."
						AND
						id_science = ".$data['id_science'];
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
		}
	}
}

?>
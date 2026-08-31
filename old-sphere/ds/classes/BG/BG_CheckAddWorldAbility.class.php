<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для предоставления возможности игроку играть ещё в 1 мире
 * кроме своего основного
 *
 */
class BG_CheckAddWorldAbility extends BG_Common 
{
	public function __construct($data)
	{
		parent::__construct($data);		
		if(!$this->checkIncomingInfo())
		{
			$this->outputAnswer();
			return;
		}

		$this->common = DS_Common::CreateForCurentDB();
	}

	public function check()
	{
		if($this->error_status != 0)
		{
			return;
		}
		
		if( $this->checkIfUserRegisteredInWorld($this->incoming_info['worldname']) && 
			 $this->checkWorldType($this->incoming_info['worldname']) &&
			 $this->checkOnUserAlreadyHasAbility())
			$this->addToResult('checking_result','add_ability_ok');

		$this->outputAnswer(); 
	}

	private function checkIncomingInfo()
	{
		if(	!array_key_exists('id_user',$this->incoming_info) ||
			!array_key_exists('worldname',$this->incoming_info)) 
		{
			$this->error_status = 1;
			return false;
		}

		if(!is_numeric($this->incoming_info['id_user']) ||
			(int)$this->incoming_info['id_user'] <= 0)
		{
			$this->error_status = 2;
			return false;
		}

		return true;
	}

	private function checkIfUserRegisteredInWorld($worldname)
	{
		if(!$this->common->setDbId(0))
		{
			$this->error_status = 3;
			return false;
		}

		$sql = "SELECT
				      count(*)
				FROM
				    ds_worldcollection wc,
				    ds_userinstancecollection uic
				WHERE
				     wc.worldname = '".addslashes($worldname)."'
				     AND wc.id_world = uic.id_world
				     AND uic.id_user =".$this->incoming_info['id_user'];

		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 4;
			return false;
		}

		if((int)$res[0]['count'] != 0)
		{
			$this->addToResult('checking_result','user_registered_in_world');
			return false;
		}
		return true;
	}


	private function checkWorldType($worldname)
	{
		if(!$this->common->setDbId(0))
		{
			$this->error_status = 5;
			return false;
		}

		$sql = "
			SELECT
			    wc.profi,
			    wc.dbname,
			    wc.id_world
			FROM
			    ds_worldcollection wc
			WHERE
				wc.worldname = '".addslashes($worldname)."'
			";
		
		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");			
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 6;
			return false;
		}
		
		if(!$this->common->setDbId($this->common->getDbIdByName($res[0]['dbname'])))
		{
			$this->error_status = 10;
			return false;
		}
		
		$sql = "
			SELECT 
				value
			FROM
				ds_gamemodifier
			WHERE
				id_world = ".$res[0]['id_world']."
				AND
				modifiername = 'TURNIR_WORLD'
			";
		$res2 = $this->common->getResultArray("",$sql,"","","NO_CACHE");			
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 11;
			return false;
		}
		
		if($res2[0]['value'] == 1)
		{
			$this->addToResult('checking_result','new_world_is_turneer');
			return false;
		} elseif (strcmp($res[0]['profi'], 't') == 0 &&
				!$this->checkIfUserProfi())
		{
			$this->addToResult('checking_result','new_world_is_profy');
			return false;
		}

		return true;
	}

	

	private function checkIfUserProfi()
	{
		return  DS_Premium::checkIfUserProfi($this->incoming_info['id_user']);
	}

	private function checkOnUserAlreadyHasAbility()
	{
		if($this->checkMultiworldAccess() ||
			$this->checkUsersAllowed())
			{
				$this->addToResult('checking_result', 'user_already_has_ability');
				return false;
			}

		return true;
	}

	private function checkMultiworldAccess()
	{
		return true;
		if(!$this->common->setDbId(0))
		{
			$this->error_status = 7;
			return false;
		}

		$sql = "SELECT
				      count(*)
				FROM
				    ds_multiworldaccess m
				WHERE
				     m.id_user =".$this->incoming_info['id_user'];

		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 8;
			return false;
		}

		return $res[0]['count'];
	}

	// here commit
	private function checkUsersAllowed()
	{
		if(!$this->connectToNeededDB())
			return false;

		$sql = "SELECT
				      count(*)
				FROM
				    ds_usersallowed ua
				WHERE
				     ua.id_user = ".$this->incoming_info['id_user']."
				     AND ua.id_world =".$this->id_world;

		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 9;
			return false;
		}

		return $res[0]['count'];
	}
}
?>
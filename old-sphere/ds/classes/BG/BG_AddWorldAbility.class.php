<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:45:28 $';

/**
 * * $Author: annita $
 * $Date: 2008/07/01 08:45:28 $
 * $Revision: 1.6 $
 * 
 * Класс гейта для предоставления возможности игроку играть ещё в 1 мире
 * кроме своего основного
 *
 */
class BG_AddWorldAbility extends BG_Common 
{
	private $count = 1;
	
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
	
	public function add()
	{
		if($this->error_status != 0)
		{
			return;
		}
		
		if( $this->addToUsersAllowed() &&
			$this->addToUsersCollection())
		{
			$this->addToResult('ability_ok', 1);
		}
		
		$this->outputAnswer(); 
	}
	
	private function addToUsersAllowed()
	{
		if(!$this->connectToNeededDB())
			return false;
			
		$sql = "
			SELECT
				count
			FROM
				ds_usersallowed
			WHERE
				id_user = ".$this->incoming_info['id_user']."
				AND
				id_world = ".$this->id_world."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 13;
			return false;
		}
			
		if(count($res) > 0)
		{
			$sql = "
				UPDATE 
					ds_usersallowed
				SET
					count = count + 1
				WHERE
					id_user = ".$this->incoming_info['id_user']."
					AND
					id_world = ".$this->id_world."				
				";
			$this->count = (int)$res[0]['count'] + 1;
		} else {
			$sql = "
				INSERT INTO	ds_usersallowed
					(id_user, id_world, count)
				VALUES
					(".$this->incoming_info['id_user'].", ".$this->id_world.", 1)";
		}
		
		$this->common->executeQuery($sql);	
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 10;
			return false;
		}
		
		return true; 
	}
	
	private function addToUsersCollection()
	{
		if(!$this->common->setDbId(0))
		{
			$this->error_status = 11;
			return false;
		}
		
		if(!$this->loadWorldInfo())
			return false;
			
		$sql = "INSERT INTO ds_usersallowedcollection
           			(dbname, id_world, id_user, id_worldcollection, count)
				VALUES
      				(
      					'".$GLOBALS['dbWorldAccess'][$this->db_id]['server']."',
      					".$this->id_world.",
      					".$this->incoming_info['id_user'].",
      					".$this->id_worldcollection.",
      					".$this->count."
      				)";	
		$this->common->executeQuery($sql);	
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 12;
			return false;
		}
		
		return true; 
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
		if(!$this->connectToNeededDB())
		{
			return false;
		}
		
		$_SESSION['world'] = $this->id_world;
		$_SESSION['dbId'] = $this->db_id;
		$turnir = (int)DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_WORLD');
		if($turnir == 1)
		{
			return true;
		}
			
		if(!$this->common->setDbId(0))
		{
			$this->error_status = 3;
			return false;
		}
		
		$sql = "
			SELECT
				count(*)
			FROM
			    ds_worldcollection wc,
			    ds_userinstancecollection uic
			WHERE
				wc.worldname = '".addslashes($worldname)."'
				AND 
				wc.id_worldcollection = uic.id_worldcollection
				AND 
				uic.id_user =".$this->incoming_info['id_user'];
		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 4;
			return false;
		}
		
		if((int)$res[0]['count'] !== 0)
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
		
		/*
		$_SESSION['world'] = $this->id_world;
		$_SESSION['dbId'] = $this->db_id;
		$turnir = (int)DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_WORLD');
		if($turnir == 1)
		{		
			$this->addToResult('checking_result','new_world_is_turneer');
			return false;
		} else */
		if (strcmp($res[0]['profi'], 't') == 0 &&
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
	
	
	private function checkUsersAllowed()
	{
		if(!$this->connectToNeededDB())
			return false;

		$_SESSION['world'] = $this->id_world;
		$_SESSION['dbId'] = $this->db_id;
		$turnir = (int)DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_WORLD');
		if($turnir == 1)
		{
			return 0;
		}
	
		$sql = "SELECT
				    count(*)
				FROM
				    ds_usersallowed ua
				WHERE
				    ua.id_user = ".$this->incoming_info['id_user']."
				    AND ua.id_world = ".$this->id_world."
			";
		
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
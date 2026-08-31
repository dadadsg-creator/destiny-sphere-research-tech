<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для проверки возможности и резервирования соты
 *
 */

require_once constant('INCLUDE_PATH').'/GameDown.inc.php';

class BG_CombReserv extends BG_Common 
{	
	private $ring;
	private $complexnumber;
	private $combnumber;
	
	public function __construct($data)
	{
		parent::__construct($data);	
		
		if(!$this->checkIncomingInfo())
			$this->outputAnswer();
		return;
		
		list($this->ring, $this->complexnumber, $this->combnumber) = split('\.', $this->incoming_info['comb_coords']);					
			
		$this->common = DS_Common::CreateForCurentDB();
	}
	
	private function checkIncomingInfo()
	{
	
		if($this->error_status != 0)
		{
			return;
		}
		
		if(	!array_key_exists('id_user',$this->incoming_info) ||
			!array_key_exists('worldname',$this->incoming_info) ||
			!array_key_exists('comb_coords',$this->incoming_info)) 
		{
			$this->error_status = 13;
			return false;
		}
		
		if (!preg_match("/^[0-9]{1,4}\.[0-9]{1,4}\.[0-9]{1,4}$/", 
			$this->incoming_info['comb_coords']) )
		{
			$this->error_status = 8;
			return false;
		}
		
		return true;
	}
		
	public function check()
	{	
		if($this->error_status != 0)
		{
			return;
		}
	
		if( $this->checkIfNewCoordsFree() &&
			$this->checkIfUserCanRegInWorld() &&
			$this->checkIfReservedCombNotAColony() )
				$this->addToResult('checking_result','reserve_comb_ok');
					
		$this->outputAnswer(); 
		return;
	}

	public function reserv()
	{		
		if($this->reservComb())
			$this->addToResult('reserve_comb_ok',1);
			
		$this->outputAnswer();
		return;
	}	

	
	private function reservComb()
	{
		if($this->error_status != 0)
		{
			return;
		}
		
		if(!$this->connectToNeededDB())
			return false;
			
		settype($this->incoming_info['id_user'], 'integer');
		$user = DE_User::CreateFromID($this->incoming_info['id_user']);
		$username = $user->getName();
		
		$id_comb = $this->getCombIDByCordinates();
		if(!$id_comb)
			return false;
					
		$sql = "insert into 
							ds_reservedcomb
							(username, id_comb)
						values 
							('".$username."', ".$id_comb.")";
		
		$this->common->executeQuery($sql);
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 10;
			return false;
		}
		
		return true;
	}
	
	
	private function checkIfReservedCombNotAColony()
	{
		if($this->ring == 4)
		{
			$this->addToResult('checking_result','comb_is_colony');
			return false;
		}
		
		return true;
	}
	
	private function checkIfUserCanRegInWorld()
	{   
		settype($this->incoming_info['id_user'], 'integer');
		$user = DE_User::CreateFromID($this->incoming_info['id_user']);
			
		if(!$this->loadWorldInfo())
			return false;
			
		$isProfi = DS_Premium::checkIfUserProfi($this->incoming_info['id_user']);
		
		//Антоха, мать-мать, нафиг туда комон совать, почему он его сам для 
		// себя  не может создать
		$this->common->setDbId(0);
		GE_SearchStart::SetCommon($this->common);
		
		if(!GE_LogicCanRegInWorld::Test($user, $isProfi, $this->db_id, $this->id_world))
		{
			$this->addToResult('checking_result','cant_reg_in_world');
			return false;
		}
		
		return true;
	}
		
	private function checkIfNewCoordsFree()
	{	
		if(!$this->connectToNeededDB())
			return false;
					
		$sql = "SELECT
				    count(*)
				FROM
				    ds_comb c,
				    ds_combcomplex cc,
				    ds_ring r
				WHERE
				    c.id_userinstance is NULL
				    AND c.combnumber = ".$this->combnumber."
				    AND c.id_combcomplex = cc.id_combcomplex
				    AND cc.complexnumber = ".$this->complexnumber."
				    AND cc.id_ring = r.id_ring
				    AND r.ringnumber = ".$this->ring."
				    AND r.id_world = ".$this->id_world."
                    AND
			 			NOT EXISTS 
			 			(
			 				SELECT id_comb
			 				FROM ds_combname
			 				WHERE id_comb = c.id_comb
	 					)
	 				AND
     					NOT EXISTS 
     					(
		         			SELECT id_comb
				 			FROM ds_reservedcomb
				 			WHERE id_comb = c.id_comb
     					)";
		
		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	

		if ((int)$res[0]['count'] != 1)
		{
			return false;
		}
		
		return true;		
	}
	
	private function getCombIDByCordinates()
	{
		if(!$this->connectToNeededDB())
			return false;
			
		$sql = "SELECT
		       			c.id_comb
		 			FROM
		     			(ds_comb AS c INNER JOIN ds_combcomplex AS cc ON ( c.id_combcomplex = cc.id_combcomplex )
		              		INNER JOIN ds_ring AS r ON ( cc.id_ring = r.id_ring ))
					WHERE
						r.id_world = ".$this->id_world." AND
		     			r.ringnumber = ".$this->ring." AND
		     			cc.complexnumber = ".$this->complexnumber." AND
		     			c.combnumber = ".$this->combnumber;
		
		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	
		if(!$res)
		{
			$this->error_status = 9;
			return false;
		}

		return $res[0]['id_comb'];

	}

}
?>
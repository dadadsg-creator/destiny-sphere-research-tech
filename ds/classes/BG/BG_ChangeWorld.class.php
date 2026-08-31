<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для перемещения соты на свободное место внутри кольца 

 *

 */

class BG_ChangeWorld extends BG_Common 

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
		
		if(!$this->checkIfUserRegisteredInWorld($this->incoming_info['worldname']))

		{

			$this->addToResult('checking_result','user_not_registered_in_world');

		}

		elseif ($this->checkIfUserRegisteredInWorld($this->incoming_info['new_worldname']))

		{

			$this->addToResult('checking_result','user_already_registered_in_new_world');

		}

		elseif( $this->checkWorldType($this->incoming_info['new_worldname']) &&

				$this->checkIfFreeCombsExists() )

				$this->addToResult('checking_result','move_user_ok');

					

		$this->outputAnswer(); 

	}

	

	public function move()

	{

		//here

		//$userInstanceResult = SI_ControlStoreUserInstance::Exec($this->fromUIID);

	}

	

	private function checkIncomingInfo()

	{

		if(	!array_key_exists('id_user',$this->incoming_info) ||

			!array_key_exists('worldname',$this->incoming_info) ||

			!array_key_exists('new_worldname',$this->incoming_info)) 

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

			$this->addToResult('setDbId',$this->common->errormsg);

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

		

		return $res[0]['count'];

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

	

	private function checkIfFreeCombsExists()

	{

		$needed_combs = $this->getNumberOfNeededCombs();

		if(!$needed_combs)

			return false;

			

		foreach ($needed_combs as $val)

		{

			if(!$this->checkNumberOfFreeCombsOnRing($val['ringnumber'], $val['combs_count']))

				return false;

		}

		

		return true;

	}

	

	private function getNumberOfNeededCombs()

	{

		if(!$this->connectToNeededDB($this->incoming_info['worldname']))

			return false;

			

		$sql = "SELECT

				    r.ringnumber,

				    count(*) as combs_count

				FROM

				    ds_userinstance ui,

				    ds_comb c,

				    ds_ring r,

				    ds_combcomplex cc

				WHERE

				     ui.id_user = ".$this->incoming_info['id_user']."

				     AND ui.id_userinstance = c.id_userinstance

				     AND c.id_combcomplex = cc.id_combcomplex

				     AND cc.id_ring = r.id_ring

				     AND r.id_world = ".$this->id_world."

				GROUP BY r.ringnumber";

		

		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");	

		if(!empty($this->common->errormsg) || !$res)

		{

			$this->error_status = 7;

			return false;

		}

		

		return $res;

	}

	

	private function checkNumberOfFreeCombsOnRing($ringnumber, $needed_combs)

	{

		if(!$this->connectToNeededDB($this->incoming_info['new_worldname']))

			return false;

			

		$sql = "SELECT

				      count(*) as free_count

				FROM

				    ds_comb c,

				    ds_combcomplex cc,

				    ds_ring r

				WHERE

				     c.id_userinstance is NULL

				     AND c.id_combcomplex = cc.id_combcomplex

				     AND cc.id_ring = r.id_ring

				     AND r.ringnumber = ".$ringnumber."

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

		if(!empty($this->common->errormsg) || !$res)

		{

			$this->error_status = 8;

			return false;

		}

		

		if((int)$res[0]['free_count'] < $needed_combs)

		{

			$this->addToResult('checking_result','no_places');

			return false;

		}

		

		return true;

	}

}

?>
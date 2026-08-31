<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Класс гейта для предоставления возможности игроку играть во всех мирах
 *
 */

class BG_AddAllWorldAbility extends BG_Common 

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
		
		if($this->checkMultiworldAccess())
		{
			$this->addToResult('checking_result','add_ability_ok');
		}
					
		$this->outputAnswer(); 
	}

	

	public function add()

	{
		if($this->error_status != 0)
		{
			return;
		}

		if(!$this->common->setDbId(0))

		{

			$this->error_status = 3;

			return false;

		}

		

		$sql = "INSERT INTO

				           ds_multiworldaccess

				           (id_user)

					VALUES

							(".$this->incoming_info['id_user'].")";

		

		$res = $this->common->executeQuery($sql);	

		

		empty($this->common->errormsg) ?

			$this->addToResult('ability_ok', 1):

			$this->addToResult('ability_ok', 0);		

		

		$this->outputAnswer(); 

	}

	

	private function checkIncomingInfo()

	{

		if(	!array_key_exists('id_user',$this->incoming_info)) 

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

		

		if((int)$res[0]['count'] != 0)

		{

			$this->addToResult('checking_result','already_has');

			return false;

		}

		

		return true;

	}

}

?>
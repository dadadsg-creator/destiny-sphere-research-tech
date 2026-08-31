<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 *
 * Класс гейта для проверки возможности переезда соты на свободное место
 * внутри кольца
 *
 */

class BG_CheckCombMovingInsideRing extends BG_Common
{

	public function __construct($data)
	{
		parent::__construct($data);
	}

	public function proceed()
	{
    if($this->error_status != 0)
		{
			return;
		}

		if(!array_key_exists('new_comb_coords',$this->incoming_info) ||
			!array_key_exists('id_user',$this->incoming_info) ||
			!array_key_exists('worldname',$this->incoming_info) ||
			!array_key_exists('comb_coords',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}

		if($this->checkIfUserOwnerOfComb() &&
			$this->checkIfNewCoordsFree() &&
			 $this->_reserveComb())
				$this->addToResult('checking_result','moving_ok');

		$this->outputAnswer();
		return;
	}

	private function checkIfNewCoordsFree()
	{
		if(!$this->connectToNeededDB())
			return false;

		if (!preg_match("/^[0-9]{1,4}\.[0-9]{1,4}\.[0-9]{1,4}$/",
			$this->incoming_info['new_comb_coords']) ||
			empty($this->id_world))
		{
			$this->error_status = 8;
			return false;
		}

		$userName = DE_User::CreateFromID((int)$this->incoming_info['id_user'])->getName();

		list($ring, $complexnumber, $combnumber) = split('\.', $this->incoming_info['new_comb_coords']);

		$sql = "SELECT
				      count(*)
				FROM
				    ds_comb c,
				    ds_combcomplex cc,
				    ds_ring r
				WHERE
				     c.id_userinstance is NULL
				     AND c.combnumber = ".$combnumber."
				     AND c.id_combcomplex = cc.id_combcomplex
				     AND cc.complexnumber = ".$complexnumber."
				     AND cc.id_ring = r.id_ring
				     AND r.ringnumber = ".$ring."
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
				 			WHERE
				 				id_comb = c.id_comb
				 				AND
				 				username != '$userName'
     					)";

		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");
		if(!$res)
		{
			$this->error_status = 5;
			return false;
		}
		elseif ((int)$res[0]['count'] != 1)
		{
			$this->addToResult('checking_result','coords_busy');
			return false;
		}

		return true;
	}

	private function checkIfUserOwnerOfComb()
	{
		if(!is_numeric($this->incoming_info['id_user']) ||
			(int)$this->incoming_info['id_user'] <= 0 ||
			!preg_match("/^[0-9]{1,4}\.[0-9]{1,4}\.[0-9]{1,4}$/", $this->incoming_info['comb_coords']))
			{
				$this->error_status = 7;
				return false;
			}

		if(!$this->connectToNeededDB())
			return false;

		list($ring, $complexnumber, $combnumber) = split('\.', $this->incoming_info['comb_coords']);

		$sql = "SELECT
				      count(*)
				FROM
				    ds_user u,
				    ds_userinstance ui,
				    ds_comb c,
				    ds_combcomplex cc,
				    ds_ring r
				WHERE
				     u.id_user = ".$this->incoming_info['id_user']."
				     AND u.id_user = ui.id_user
				     AND ui.id_userinstance = c.id_userinstance
				     AND c.combnumber = ".$combnumber."
				     AND c.id_combcomplex = cc.id_combcomplex
				     AND cc.complexnumber = ".$complexnumber."
				     AND cc.id_ring = r.id_ring
				     AND r.ringnumber = ".$ring."
				     AND r.id_world =".$this->id_world;

		$res = $this->common->getResultArray("",$sql,"","","NO_CACHE");
		if(!$res)
		{
			$this->error_status = 10;
			return false;
		}
		elseif ((int)$res[0]['count'] != 1)
		{
			$this->addToResult('checking_result','comb_not_yours');
			return false;
		}

		return true;
	}

	private function _reserveComb()
	{
		if(!$this->connectToNeededDB())
			return false;

		if (!preg_match("/^[0-9]{1,4}\.[0-9]{1,4}\.[0-9]{1,4}$/",
			$this->incoming_info['new_comb_coords']) ||
			empty($this->id_world))
		{
			$this->error_status = 8;
			return false;
		}

		list($ring, $complexnumber, $combnumber) = split('\.', $this->incoming_info['new_comb_coords']);

		$comb = DE_Comb::CreateFromCoordinates((int)$this->id_world, (int)$ring, (int)$complexnumber, (int)$combnumber);
		if($comb instanceof DE_CombNull)
		{
			$this->error_status = 9;
			return false;
		}

		$user = DE_User::CreateFromID((int)$this->incoming_info['id_user']);

		$sql = "
			INSERT INTO ds_reservedcomb
				(id_comb, username, comment)
			VALUES
				(".$comb->getID().", '".$user->getName()."', 'RESERVE FOR TRANSFER FROM BILLING')
			";
		$this->common->executeQuery($sql);
		if(!empty($this->common->errormsg))
		{
			$this->error_status = 10;
			return false;
		}

		return true;
	}
}
?>
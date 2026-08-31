<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */
class BG_MoveToAnotherWorld extends BG_Common
{
	private $userID;
	
	private $worldNameFrom;
	
	private $worldNameTo;
	
	private $userName;
	
	private $worldDataFrom;
	
	private $worldDataTo;
	
	public function __construct($data)
	{
		parent::__construct($data);
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		assert(key_exists('id_user', $data));
		$this->userID = (int)$data['id_user'];
		
		assert(key_exists('world_name_from', $data));
		$this->worldNameFrom = $data['world_name_from'];
		
		assert(key_exists('world_name_to', $data));
		$this->worldNameTo = $data['world_name_to'];
	}
	
	public function check()
	{
		// 'forbidden' => 
		// 0 - можно
		// 1 - нет мест
		// 2 - есть сота
		// 3 - нет соты в мире откуда едет
		// 4 - переезд между выбранными мирами запрещен
		// 5 - только для премиумов
		// 6 - есть незавершенный переезд из заказанного мира
		// 7 - есть незавершенный переезд в мир который заказывает

		$this->addToResult('forbidden', $this->_checkTransfer());
		$this->outputAnswer();

		return;
	}

	private function _checkTransfer()
	{
		$res = 0;
		
		$this->worldDataFrom = $this->_getInstanceInWorld($this->worldNameFrom);
		$this->worldDataTo = $this->_getInstanceInWorld($this->worldNameTo);
		
		// есть сота в мире откуда едем
		if((int)$this->worldDataFrom['id_userinstance'] > 0)
		{
			$res = 0;
		}
		
		// в мире куда едем нет соты у игрока
		if($res == 0 && (int)$this->worldDataTo['id_userinstance'] > 0)
		{
			$res = 2;
		}
		
		// есть свободные места в мире куда идем
		if($res == 0 && (int)$this->worldDataTo['max_users'] <= 0)
		{
			$res = 1;
		}
		
		if($res == 0)
		{
			$res = $this->_checkTransferRelation();
		}
		
		if($res == 0)
		{
			$res = $this->_checkExistingTransfer();
		}
		
		
		return $res;
	}
	
	private function _checkExistingTransfer()
	{
		$sql = "
			SELECT 
				id_user
			FROM
				ds_worldtransfer
			WHERE
				id_user = ".$this->userID."
				AND
				db_from = '".$this->worldDataFrom['dbname']."'
				AND
				id_world_from = ".$this->worldDataFrom['id_world']."
				AND
				status = 1
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) > 0)
		{
			return 6;
		}
		
		$sql = "
			SELECT 
				id_user
			FROM
				ds_worldtransfer
			WHERE
				id_user = ".$this->userID."
				AND
				db_to = '".$this->worldDataTo['dbname']."'
				AND
				id_world_to = ".$this->worldDataTo['id_world']."
				AND
				status = 1
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) > 0)
		{
			return 7;
		}
		
		return 0;
	}
	
	private function _addTransferInfo()
	{
		$sql = "
			INSERT INTO ds_worldtransfer
				(id_user, db_from, id_world_from, db_to, id_world_to, transfer_type, status)
			VALUES
				(
					".$this->userID.",
					'".$this->worldDataFrom['dbname']."',
					".$this->worldDataFrom['id_world'].",
					'".$this->worldDataTo['dbname']."',
					".$this->worldDataTo['id_world'].",
					2,
					1
				)
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		return true;
	}
	
	public function move()
	{
		$res = 0;
		
		if($this->_checkTransfer() == 0)
		{
			if($this->_addTransferInfo())
			{
				$res = 1;
			}
		}
		
		$this->addToResult('moved', $res);
		$this->outputAnswer();
		
		return;
	}
	
	private function _getInstanceInWorld($worldName)
	{
		$sql = "
			SELECT
      			wc.dbname,
      			wc.id_world,
      			wc.max_users,
      			wtwr.id_worldtransfer_world,
      			(
          			SELECT
               			uic.id_userinstance
          			FROM
               			ds_userinstancecollection AS uic
          			WHERE
               			uic.id_user = ".$this->userID."
               			AND
               			uic.id_worldcollection = wc.id_worldcollection
      			) as id_userinstance
			FROM
      			ds_worldcollection AS wc
      			INNER JOIN ds_worldtransfer_worlds_list AS wtwr ON (wtwr.world_identifier = wc.world_identifier)
			WHERE
      			wc.worldname = '".$worldName."'
			";	
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		assert(count($res) == 1);
		
		return $res[0];
	}
	
	private function _checkTransferRelation()
	{
		$sql = "
			SELECT
				*
			FROM
				ds_worldtransfer_worlds_relation AS wtwr
			WHERE
				id_world_from = ".$this->worldDataFrom['id_worldtransfer_world']."
				AND
				id_world_to = ".$this->worldDataTo['id_worldtransfer_world']."
			";	
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) == 0)
		{
			return 4;
		}
		
		if($res[0]['only_for_premium'] == 't')
		{
			if(!DS_User::IsPremium($this->userID))
			{
				return 5;
			}
		}
		
		return 0;
	}
	
}

?>
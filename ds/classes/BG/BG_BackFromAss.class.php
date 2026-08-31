<?php
/**
 * $Author: 40min $
 * $Date: 2008/10/13 12:33:04 $
 * $Revision: 1.13 $
 * Класс для возвращения игрока из мира чёрная дыра
 * */

session_start();

class BG_BackFromAss extends BG_Common
{
	private $dbNameFrom;
	
	private $worldIdFrom;
	
	private $dbIdFrom;
	
	private $worldAssId;
	
	private $dbAssId;
	
	private $dbAssName;

	private $worldAssIdentifier = 'HEL';
	
	public function __construct($data)
	{
		parent::__construct($data);
		$this->common = DS_Common::CreateForCurentDB();
	}
	
	/**
	 * Функция проверок возможности вылезания из зада
	 * */
	public function check(){
		
		$this->_check();		
		$this->outputAnswer();
		return;
	}

	/**
	 * Предоставление возможности
	 * */
	public function proceed()
	{
		$this->_check();	
		
		if($this->error_status !== 0){
			
			$this->outputAnswer();
			return;
		}
		
		$_SESSION['dbId'] = 0;
		
		$sql = "
				INSERT INTO ds_worldtransfer
					(id_user, db_from, db_to, id_world_from, id_world_to, transfer_type, status, transfer_date)
				VALUES
					(". $this->incoming_info['id_user'] .", '". $this->dbAssName ."', '". $this->dbNameFrom . 
					"', ". $this->worldAssId .", ". $this->worldIdFrom .", 0, 1, now())
			";

		$this->common->executeQuery($sql);
		if (!empty($this->common->errormsg))
		{
			$this->error_status = 21;
			$this->outputAnswer();
			return false;
		}
		
		$this->outputAnswer();
		
		return true;
	}
	
	private function _check(){
		
		if ( empty($this->incoming_info['id_user']) ) {
			$this->error_status = 1;
			return ;
		}
		
		if(!$this->_collectTransferData()){
			return ;
		}
		
		if (!$this->_checkDBs()){
			$this->error_status = 2;
		}
		elseif ( !$this->_checkOnCombInAss()){
			$this->error_status = 3;
		}
		elseif ( !$this->_checkIsBackingInProgress()){
			$this->error_status = 4;
		}
		elseif ( !$this->_checkIsUserRegisteredInOldWorld()){
			$this->error_status = 5;
		}
		else {
			$this->error_status = 0;
		}
		
		return ;
	}
	
	private function _collectTransferData(){
		
		if(!$worldAssData = DS_World::GetWorldFromCollectionByShortName($this->worldAssIdentifier)){
			$this->error_status = 10;
			return false;
		}
		
		$this->dbAssName = $worldAssData['dbname'];
		$this->worldAssId = $worldAssData['id_world'];
		if(!$this->dbAssId = $this->common->getDBIDByName($this->dbAssName)){
			$this->error_status = 11;
			return false;
		}
		
		if(!$worldFromData = DS_WorldTransfer::getLastWorldTransferStatByUserID($this->incoming_info['id_user'])){	
			
			if(!$worldFromData = $this->_checkOnHandMadeTransfer()){
					
				// нету записи в логах => нету соты в заднице
				$this->error_status = 3;
				return false;
			}
		}
		$this->dbNameFrom = $worldFromData['db_from'];
		$this->worldIdFrom = $worldFromData['id_world_from'];
		if(!$this->dbIdFrom = $this->common->getDBIDByName($this->dbNameFrom)){
			$this->error_status = 11;
			return false;
		}
		
		return true;
	}
	
	/**
	 * Поиск по логам в случае, если пользователь был перевезен не автоматом
	 *
	 */
	private function _checkOnHandMadeTransfer(){
		
		$sql = "SELECT 
						db_name_from as db_from,
						id_world_from
				  FROM 
					   	ds_storeuserdata 
				  WHERE
					   db_name_to = 'hell'
					   AND id_world_to = ".$this->worldAssId."
					   AND id_user = ".$this->incoming_info['id_user']."
				ORDER BY store_date DESC
				LIMIT 1";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");	
		assert(empty($this->common->errormsg));

		return $res[0];
	}
	
	private function _checkDBs(){
		
		//Проверка на даун базы, куда будем восстанавливать
		if(GameDown($this->dbIdFrom, $this->worldIdFrom)){			
			return false;
		}
		
		//Проверка на гейм даун черной дыры
		if(GameDown($this->dbAssId, $this->worldAssId)){			
			return false;
		}
		
		return true;
		
	}
	
	private function _checkOnCombInAss(){
		
		$_SESSION['dbId'] = $this->dbAssId;
		if(!$this->common->setDbId($this->dbAssId)){
			return false;
		}
		
		//Получаем соты, перевезенные пулемётом
		$sql = "
			SELECT 
				c.id_comb
			FROM 
				ds_user u
			 LEFT JOIN ds_userinstance ui ON (ui.id_user = u.id_user)
			 LEFT JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
			WHERE 
				u.id_user = '". $this->incoming_info['id_user'] ."'
				AND
				ui.id_world = ". $this->worldAssId ."
			";

		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		if(sizeof($res) > 0)
			return true;
			
		return false;
	}
	
	private function _checkIsBackingInProgress(){
		
		//Смотрим, не заказал ли уже игрок восстановление соты
		$info = DS_WorldTransfer::getTransferInfo((int)$this->dbAssId, (int)$this->worldAssId, (int)$this->incoming_info['id_user']);
		//Если есть записи о переездах в этом мире	
		if (count($info) > 0) {
			//Проходимся по всем переездам		
			foreach ($info as $k => $trans){
				//Если переезд был заказан
				if ($trans['status'] == 1) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	private function _checkIsUserRegisteredInOldWorld(){
		
		$_SESSION['dbId'] = 0;
		if(!$this->common->setDbId(0)){
			return false;
		}
		
		if(DS_UserInstance::GetUserInstanceIDByUserIDAndWorldID((int)$this->incoming_info['id_user'], (int)$this->worldIdFrom)){
			return false;
		}
		
		return true;
	}
}

?>
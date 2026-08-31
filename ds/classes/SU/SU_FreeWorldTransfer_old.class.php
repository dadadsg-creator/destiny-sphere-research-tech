<?php $CVS_Version = '$Revision$ ; $Date$';
/*
* transfer_type
*   0 - free
*   1 - admin
*   2 - billing
*	5 - reciever auto
*/
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

class SU_FreeWorldTransfer
{
	private $dbIdFrom;

	private $dbIdTo;

	private $worldIdFrom;

	private $worldIdTo;

	private $common;

	private $uid;

	private $action;

	private $error;

	private $profi = false;

	private $worlds = false;

	private $worldFromSelected = false;
	
	private $combCoordsError = '';
	
	private $reserveComb = false;
	
	private $reserveCombMels = false;
	
	private $transferOrdered = false;
	
	// через сколько часов возможен следующий бесплатный переезд
	private $freeTransferPeriod = 168;

	public function __construct($data)
	{
		$this->_getParams($data);

		switch($this->action)
		{
			case 'add':
				$this->_transfer();
				break;
			case 'cancel':
				$this->_cancelTransfer();
				break;
			default:
				$worldsToEnter = $this->_getUserWorlds();

				if(count($worldsToEnter) == 1)
				{
					$this->worldIdFrom = (int)$worldsToEnter[0]['id_world'];
					$this->dbIdFrom = (int)$worldsToEnter[0]['dbId'];
				} elseif(!$this->worldFromSelected) {
					$this->worlds = $worldsToEnter;
				}
		}

		$this->_out();
	}

	private function _getParams($data)
	{
		assert(is_array($data));

		if(key_exists('world_from', $data))
		{
			$arr = explode('_', $data['world_from']);
			assert(count($arr) == 2);

			$this->worldIdFrom = (int)$arr[0];
			$this->dbIdFrom = (int)$arr[1];

			assert($this->worldIdFrom > 0);
			assert($this->dbIdFrom >= 0);

			$this->worldFromSelected = true;
		}

		$this->common = DS_Common::CreateForCurentDB(0);

		assert(key_exists('uid', $_SESSION));
		$this->uid = (int) $_SESSION['uid'];

		$this->profi = DS_User::IsPremium($this->uid);

		if(key_exists('world', $data))
		{
			$arr = explode("_", $data['world']);
			assert(count($arr) == 2);

			$this->dbIdTo = (int)$arr[0];
			assert($this->dbIdTo >= 0);

			$this->worldIdTo = (int)$arr[1];
			assert($this->worldIdTo > 0);

			$this->action = 'add';
		}
		
		if(key_exists('complex_mels', $data) && key_exists('comb_mels', $data))
		{
			if(!empty($data['complex_mels']) && !empty($data['comb_mels']))
			{
				$ring = 4;
				$complex = (int)$data['complex_mels'];
				$comb = (int)$data['comb_mels'];
				
				if($comb > 6 || $comb < 1)
				{
					$this->error = 'NO_COMB';
					$this->action = '';
					return;
				}
				
				$oldDbId = $_SESSION['dbId'];
				$_SESSION['dbId'] = $this->dbIdTo;
				$targetComb = DE_Comb::CreateFromCoordinates($this->worldIdTo, $ring, $complex, $comb);
				
				if($targetComb instanceof DE_CombNull)
				{
					$this->error = 'NO_COMB';
					$_SESSION['dbId'] = $oldDbId;
					$this->action = '';
					return;
				}
				
				if($targetComb->getUserInstanceId() != 0)
				{
					$this->error = 'NOT_EMPTY';
					$_SESSION['dbId'] = $oldDbId;
					$this->action = '';
					return;
				}
				
				if(!DS_WorldTransfer::checkCombReservation($this->dbIdTo, $targetComb->getID()))
				{
					$this->error = 'RESERVED';
					$_SESSION['dbId'] = $oldDbId;
					$this->action = '';
					return;
				}
				
				$this->reserveCombMels = $targetComb;
				$_SESSION['dbId'] = $oldDbId;
			}
		}
		
		if(key_exists('complex', $data) && key_exists('comb', $data))
		{
			if(!empty($data['complex']) && !empty($data['comb']))
			{
				$ring = DS_WorldTransfer::getUserBaseRing($this->dbIdFrom, $this->uid);
				$complex = (int)$data['complex'];
				$comb = (int)$data['comb'];
				
				if($comb > 6 || $comb < 1)
				{
					$this->error = 'NO_COMB';
					$this->action = '';
					return;
				}
				
				$oldDbId = $_SESSION['dbId'];
				$_SESSION['dbId'] = $this->dbIdTo;
				$targetComb = DE_Comb::CreateFromCoordinates($this->worldIdTo, $ring, $complex, $comb);
				
				if($targetComb instanceof DE_CombNull)
				{
					$this->error = 'NO_COMB';
					$_SESSION['dbId'] = $oldDbId;
					$this->action = '';
					return;
				}
				
				if($targetComb->getUserInstanceId() != 0)
				{
					$this->error = 'NOT_EMPTY';
					$_SESSION['dbId'] = $oldDbId;
					$this->action = '';
					return;
				}
				
				if(!DS_WorldTransfer::checkCombReservation($this->dbIdTo, $targetComb->getID()))
				{
					$this->error = 'RESERVED';
					$_SESSION['dbId'] = $oldDbId;
					$this->action = '';
					return;
				}
				
				$this->reserveComb = $targetComb;
				$_SESSION['dbId'] = $oldDbId;
			}
		}

		if(key_exists('cancel', $data))
		{
			$this->action = 'cancel';
		}

	}

	private function _transfer()
	{
		if($this->_checkSourceWorldForTransfer() && $this->_checkTargetWorldForTransfer($this->dbIdTo, $this->worldIdTo))
		{
			$this->_setTransferInfo();
		}
	}

	private function _checkSourceWorldForTransfer()
	{
		// есть ли истанс в мире
		if(!DS_WorldTransfer::getPresenceInWorld($this->dbIdFrom, $this->worldIdFrom, $this->uid))
		{
			return false;
		}
		
		// нет ли уже переездов из выбранного мира
		$info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
		if(count($info) > 0)
		{
			if($info[0]['status'] > 0)
			{
				return false;
			}
		}
		
		if(DS_WorldTransfer::isTransferFromWorldDisabled($this->dbIdFrom, $this->worldIdFrom, $this->uid))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Проверка можно ли переехать в определенный мир
	 *
	 * @param int $dbId
	 * @param int $worldId
	 * @return bool
	 */
	private function _checkTargetWorldForTransfer($dbIdTo, $worldIdTo)
	{
		// есть ли истанс в мире
		if(DS_WorldTransfer::getPresenceInWorld((int)$dbIdTo, (int)$worldIdTo, $this->uid))
		{
			return false;
		}
	
		// разрешен ли переезд
		$res = DS_WorldTransfer::getInterWorldTransferInfo((int)$this->dbIdFrom, (int)$this->worldIdFrom, (int)$dbIdTo, (int)$worldIdTo);

		if(count($res) == 0)
		{
			return false;
		} else {
			// только для премиумов, а игрок без премиума
			if($res[0]['only_for_premium'] == 't' && !$this->profi)
			{
				return false;
			}

			// преезд только за бабло или по разрешению
			if($res[0]['free'] == 'f' && !DS_WorldTransfer::isUserAllowedWorldTransfer((int)$this->dbIdFrom, $this->uid))
			{
				return false;
			}
		}
		
		// не заказал ли уже переезд в этот мир
		$res = DS_WorldTransfer::getUserTransferToWorld((int)$dbIdTo, (int)$worldIdTo, $this->uid);
		if(count($res) > 0)
		{
			return false;
		}
		
		// проверяем кол-во колоний
		$countCombs = DS_WorldTransfer::getCountUserCombs((int)$this->dbIdFrom, (int)$this->worldIdFrom, $this->uid);
		$oldDbId = $_SESSION['dbId'];
		$oldWorld = 0;
		if(key_exists('world', $_SESSION))
		{
			$oldWorld = $_SESSION['world'];
		}
		
		$_SESSION['dbId'] = $dbIdTo;
		$_SESSION['world'] = $worldIdTo;
		$countCombsAllowed = DE_WorldSettings::CreateForCurentWorld()->get('MAX_COLONIES') + 1;
		$_SESSION['dbId'] = $oldDbId;
		$_SESSION['world'] = $oldWorld;
		
		if($countCombs > $countCombsAllowed)
		{
			return false;
		}
		
		return true;
	}

	private function _getWorldList()
	{
		//GE_SearchStart::SetCommon($this->common);
		//$worlds = GE_SearchStart::GetWorldCollection(constant('DEFAULT_LANGUAGE'));
		$worlds = DS_WorldTransfer::getWorldCollection();
		
		foreach($worlds as $k => $world)
		{
			$dbId = $this->common->getDbIDByName($world['dbname']);
			$worlds[$k]['dbId'] = $dbId;

			$canTransfer = $this->_checkTargetWorldForTransfer($dbId, $world['id_world']);
			
			if(($this->dbIdFrom == $dbId && $this->worldIdFrom == $world['id_world']) ||
				!$canTransfer /*|| $world['max_users'] == 0*/)
			{
				unset($worlds[$k]);
			}
		}

		return $worlds;
	}

	private function _canCancelTransfer()
	{
		$info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
		if(count($info > 0) && $info[0]['status'] == 1)
		{
			return (int)$info[0]['id_world_to'];
		}
		
		return 0;
	}

	private function _cancelTransfer()
	{
		$res = array();

		$info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
		
		$idWorldTo = (int)$info[0]['id_world_to'];
		
		if(!$idWorldTo)
		{
			$this->error = 'NO_TRANSFER';
			return false;
		}

		$sql = "
			UPDATE 
				ds_worldtransfer
			SET
				status = 0
			WHERE
				id_user = ".$this->uid."
				AND
				transfer_type = 0 -- бесплатный переезд
				AND
				status = 1
				AND
				db_from = '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."'
				AND
				id_world_from = ".$this->worldIdFrom."
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$dbIdTo = -1;
		
		foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($db['server'] == $info[0]['db_to'])
			{
				$dbIdTo = $dbId;
			}
		}
		
		if($dbIdTo == -1)
		{
			return;
		}
		
		$sql = "
			DELETE FROM
				ds_reservedcomb
			WHERE
				username = '".DE_User::CreateFromID($this->uid)->getName()."'
				AND
				(
					SELECT
						r.id_world
					FROM
						ds_combcomplex AS cc
						INNER JOIN ds_comb AS c USING (id_combcomplex)
						INNER JOIN ds_ring AS r USING (id_ring)
					WHERE
						c.id_comb = ds_reservedcomb.id_comb
				) = ".$idWorldTo."
			";
		$this->common->setDbId($dbIdTo);
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}

	private function _setTransferInfo()
	{
		$info = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);
		
		if(count($info) > 0)
		{
			if($info[0]['status'] == 0)
			{
				$sql = "
					UPDATE
						ds_worldtransfer
					SET
						db_to = '".$GLOBALS['dbWorldAccess'][$this->dbIdTo]['server']."',
						id_world_to = ".$this->worldIdTo.",
						status = 1,
						transfer_date = now()
					WHERE
						id_user = ".$this->uid."
						AND
						transfer_type = 0
						AND
						db_from = '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."'
						AND
						id_world_from = ".$this->worldIdFrom."
				";
			} else {
				return false;
			}
		} else {
			$sql = "
				INSERT INTO ds_worldtransfer
					(id_user, db_from, db_to, id_world_from, id_world_to, transfer_type, status, transfer_date)
				VALUES
					(".$this->uid.", '".$GLOBALS['dbWorldAccess'][$this->dbIdFrom]['server']."', '".$GLOBALS['dbWorldAccess'][$this->dbIdTo]['server']."', ".$this->worldIdFrom.", ".$this->worldIdTo.", 0, 1, now())
			";
		}
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));

		if($this->reserveComb !== false)
		{
			$sql = "
				INSERT INTO ds_reservedcomb
					(id_comb, username, comment)
				VALUES
					(".$this->reserveComb->getID().", '".DE_User::CreateFromID($this->uid)->getName()."', 'free world transfer')
				";
			$this->common->setDbId($this->dbIdTo);
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		if($this->reserveCombMels !== false)
		{
			$sql = "
				INSERT INTO ds_reservedcomb
					(id_comb, username, comment)
				VALUES
					(".$this->reserveCombMels->getID().", '".DE_User::CreateFromID($this->uid)->getName()."', 'free world transfer')
				";
			$this->common->setDbId($this->dbIdTo);
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		$this->transferOrdered = true;
		
		return true;
	}

	private function _getWorldUserTransferTo()
	{
		$res = DS_WorldTransfer::getTransferInfo($this->dbIdFrom, $this->worldIdFrom, $this->uid);

		if(count($res) == 0)
		{
			return $res;
		}

		return $res[0];
	}

	private function _getUserWorlds()
	{
		GE_SearchBase::SetCommon($this->common);
		$worldsToEnter = GE_SearchStart::GetWorldsWhereUserIsRegistered((int)$this->uid);

		foreach ($worldsToEnter as $k => $world)
		{
			$worldsToEnter[$k]['dbId'] = $this->common->getDbIdByName($world['dbname']);
		}

		return $worldsToEnter;
	}

	private function _checkTimeLimit()
	{
		$count = DS_WorldTransfer::checkFreeTransferTime($this->uid, $this->freeTransferPeriod);
		if($count > 0)
		{
			return false;
		}
		
		return true;
	}
	
	private function _out()
	{
		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;

		if($this->transferOrdered) {
			$this->error = 'TRANSFER_ORDERED';
		} elseif(!$this->_checkTimeLimit()) {
			$this->error = 'TRANSFER_TIME_LIMIT';
		} elseif($this->worlds !== false) {
			$tpl->assign('USER_WORLDS', $this->worlds);
		} elseif(DS_WorldTransfer::isTransferFromWorldDisabled($this->dbIdFrom, $this->worldIdFrom, $this->uid)) {
			$this->error = 'TRANSFER_DISABLED';
		} else {
			$tpl->assign('CAN_CANCEL', $this->_canCancelTransfer());
			
			$worlds = $this->_getWorldList();
			
			if(count($worlds) == 0 || !$this->_checkSourceWorldForTransfer())
			{
				$tpl->assign('CAN_TRANSFER', 0);
			} else {
				$tpl->assign('CAN_TRANSFER', 1);
			}
			
			$worldTo = $this->_getWorldUserTransferTo();
			
			if(count($worldTo) > 0 && $worldTo['status'] > 0)
			{
				$tpl->assign('WORLD_TO', $worldTo['worldname']);
			} else {
				$tpl->assign('WORLD_TO', '');
			}

			$tpl->assign('FROM_WORLD', $this->worldIdFrom.'_'.$this->dbIdFrom);
			
			$tpl->assign('RING_NUMBER', DS_WorldTransfer::getUserBaseRing($this->dbIdFrom, $this->uid));

			$tpl->assign('WORLDS', $worlds);
		}
		
		// подключение файла с локализацией
		include $_SERVER['DOCUMENT_ROOT'].'/ds/language/'.constant('DEFAULT_LANGUAGE_SHORT').'/worldtransfer.lang';
		assert(is_array($lang));
		$tpl->assign('lang', $lang);
		unset($lang);
		
		$tpl->assign('MSG', $this->error);

		$tpl->display('worldtransfer.tpl');
	}
}
?>
<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2008/07/01 08:54:56 $';

require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

class SU_FreeTransfer
{
	private $uid;
	
	private $uiid;
	
	private $dbId = false;
	
	private $oldDbId;
	
	private $common;
	
	private $combToId;
	
	private $worldId = false;
	
	private $errorMsg;
	
	private $action;
	
	private $exchangeUID = 0;
	
	private $exchangeUserName;
	
	private $colonies;
	
	private $worlds = false;

	private $worldFromSelected = false;
	
	private $usedFreeTransfer = false;
	
	private $transferOrdered = false;
	
	private $combId;
	
	public function __construct(array $data)
	{
		SU_GameSessionWork::SessionContinue();
		
		if(key_exists('dbId', $_SESSION))
		{
			$this->oldDbId = $_SESSION['dbId'];
		} else {
			$this->oldDbId = 0;
		}
			
		if(GameDown())
		{
			header('Location: index_down.html');
			exit;
		}
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->_getParams($data);

		switch($this->action)
		{
			case 'transfer': 
				$this->_transfer();
				break;
			case 'exchange':
				$this->_exchange();
				break;
			case 'cancel':
				$this->_cancelTransfer();
				break;
			case 'swap':
				$this->_swapColonies();
				break;
			case 'exchange_swap':
				$this->_excahgeSwapColonies();
				break;

			default:
				$worldsToEnter = $this->_getUserWorlds();

				if(count($worldsToEnter) == 1)
				{
					$this->worldId = (int)$worldsToEnter[0]['id_world'];
					$this->dbId = (int)$worldsToEnter[0]['dbId'];
					$this->worldFromSelected = true;
					if(GameDown($this->dbId, $this->worldId))
					{
						header('Location: index_down.html');
						exit;
					}
					
					$_SESSION['dbId'] = $this->dbId;
					$_SESSION['world'] = $this->worldId;
					$this->common = DS_Common::CreateForCurentDB($this->dbId);	
					/*
					$comb = DS_Comb::GetByID($this->_getCombIdByUID($this->uid));
					$ring = $comb['ringnumber'];
					*/
				} elseif(!$this->worldFromSelected) {
					$this->worlds = $worldsToEnter;
				}
		}

		$this->_out();
		
		$_SESSION['dbId'] = $this->oldDbId;
	}
	
	private function _getParams(array $data)
	{		
		if(key_exists('world', $data))
		{
			$arr = explode('_', $data['world']);
			$this->dbId = (int)$arr[0];
			$this->worldId = (int)$arr[1];
			assert($this->dbId >= 0);
			assert($this->worldId > 0);
			
			$this->worldFromSelected = true;
		}
	
		assert(key_exists('uid', $_SESSION));
		$this->uid = $_SESSION['uid'];
		
		if($this->worldFromSelected)
		{
			if(GameDown($this->dbId, $this->worldId))
			{
				header('Location: index_down.html');
				exit;
			}
			
			$_SESSION['dbId'] = $this->dbId;
			$_SESSION['world'] = $this->worldId;
		
			$this->common = DS_Common::CreateForCurentDB($this->dbId);	
			$comb = DS_Comb::GetByID($this->_getCombIdByUID($this->uid));
			$ring = $comb['ringnumber'];
		}
		
		if(key_exists('id_comb', $data))
		{
			$this->combId = (int)$data['id_comb'];
			if ($this->combId <= 0) {
				$this->errorMsg = 'NO_COMB_TO_EXCHANGE';
				return;
			}
		}
		
		if(key_exists('comb_coords', $data))
		{
			assert(is_array($data['comb_coords']));
			$ring = (int) $data['comb_coords']['ring'];
			assert($ring > 0 && $ring <= 4);

			$complex = (int) $data['comb_coords']['complex'];
			if($complex <= 0)
			{
				$this->errorMsg = 'WRONG_COMPLEX';
				return;
			}
			
			$comb = (int) $data['comb_coords']['comb'];
			if(!($comb > 0 && $comb < 7))
			{
				$this->errorMsg = 'WRONG_COMB';
				return;
			}
		
			$this->combToId = (int)DS_Comb::GetIDByCordinates($this->worldId, $ring, $complex, $comb);
			if(!$this->combToId)
			{
				$this->errorMsg = 'COMB_NOT_EXISTS';
				return;
			}
		}
		
		if(key_exists('exchange_with', $data))
		{
			$this->exchangeUserName = $data['exchange_with'];
			if(!VD_UserName::Validate($this->exchangeUserName))
			{
				$this->errorMsg = VD_UserName::FetchErrorCode();
				return;
			}
			
			$this->exchangeUID = (int)VD_UserName::CheckIfExists($this->exchangeUserName, $this->common);
			
			if($this->exchangeUID == $this->uid)
			{
				$this->errorMsg = 'SELF_EXHANGE';
				return;
			}	
			
			if(!$this->exchangeUID)
			{
				$this->errorMsg = 'WRONG_EXCHANGE_USER_NAME';
				return;
			}
			
			if (key_exists('exchange_comb_coords', $data)) {
				

				$ring = (int) $data['exchange_comb_coords']['ring'];
				assert($ring > 0 && $ring <= 4);

				$complex = (int) $data['exchange_comb_coords']['complex'];
				if($complex <= 0)
				{
					$this->errorMsg = 'WRONG_COMPLEX';
					return;
				}

				$comb = (int) $data['exchange_comb_coords']['comb'];
				if(!($comb > 0 && $comb < 7))
				{
					$this->errorMsg = 'WRONG_COMB';
					return;
				}
					
				$combToData = $this->_getCombDataByCoordinates($this->worldId, $ring, $complex, $comb);
			
				// Если данная сота не принадлежит данному игроку 
				if(count($combToData) == 0 || $combToData['id_user'] != $this->exchangeUID)
				{ 
					$ring = (int) $data['exchange_comb_coords']['ring'];
					$this->errorMsg = 'NO_USER_AND_COMB_EXCHANGE';
					return;
				}
				$this->combToId = $combToData['id_comb'];				
			} else {
				$this->combToId = (int)$this->_getCombIdByUID($this->exchangeUID);
			}
			
			if($this->combToId == 0)
			{ 
				$this->errorMsg = 'NO_COMB_TO_EXCHANGE';
				return;
			}
			
			$combTo = DS_Comb::GetByID($this->combToId);
			if($ring != $combTo['ringnumber'])
			{
				$this->errorMsg = 'NO_COMB_TO_EXCHANGE';
				return;
			}
		}
		
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
	}
	
	private function _getCombIdByUID($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				c.id_comb,
				ui.id_userinstance
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = c.id_userinstance)
			WHERE
				NOT r.ringnumber = 4
				AND
				ui.id_user = ".$uid."
				AND
     			r.id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		if(count($res) != 1)
		{
			return 0;
		}
		
		$this->uiid = (int)$res[0]['id_userinstance'];

		return (int)$res[0]['id_comb'];
	}
	
	private function _getUserTransferInfo()
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				wu.exchange_with,
				wu.transfer_to,
				wu.transfer_complite,
				c.combnumber,
				cc.complexnumber,
				r.ringnumber,
				u.username
			FROM
				ds_free_ring_transfer AS wu
				INNER JOIN ds_comb AS c ON (c.id_comb = wu.transfer_to)
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
					LEFT JOIN ds_user AS u ON (u.id_user = wu.exchange_with)
					LEFT JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
			WHERE
				wu.id_user = ".$this->uid."
				AND
				wu.id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		if(count($res) != 1)
		{
			return array();
		}
		
		return $res[0];
	}
	
	private function _isUsedTransfer($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$limit = $this->_getFreeTransferLimit();
		
		$sql = "
			SELECT
				used_free_transfer
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$uid."
				AND
				id_world = ".$this->worldId."
				AND
				transfer_from IS NULL
				AND
				transfer_date > now() - interval '$limit days'
			";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		if(count($res) == 1 && $res[0]['used_free_transfer'] == 't')
		{
			return true;
		} else {
			return false;
		}
	}
	
	private function _isTransferOrdered($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				used_free_transfer
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$uid."
				AND
				id_world = ".$this->worldId."
				AND
				NOT transfer_complite IS TRUE				
			";
			
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		if(count($res) == 1 && $res[0]['used_free_transfer'] == 't')
		{
			return true;
		} else {
			return false;
		}
	}
	
	private function _isSomeoneExchange($uid)
	{
		assert(is_int($uid));
		assert($uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				u.username
			FROM
				ds_free_ring_transfer AS wu
				INNER JOIN ds_user AS u ON (u.id_user = wu.id_user)
			WHERE
				wu.exchange_with = ".$uid."
				AND
                wu.transfer_from IS NULL
				AND
				wu.id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _isSomeoneExchangeMels($uid)
	{
		assert(is_int($uid));
		assert($uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				u.username
			FROM
				ds_free_ring_transfer AS wu
				INNER JOIN ds_user AS u ON (u.id_user = wu.id_user)
			WHERE
				wu.exchange_with = ".$uid."
				AND
                NOT wu.transfer_from IS NULL
				AND
				wu.id_world = ".$this->worldId."
			";
	
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _isUsedTransferMels($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				used_free_transfer
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$uid."
				AND
				id_world = ".$this->worldId."
				AND
				NOT transfer_from IS NULL
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		if(count($res) == 1 && $res[0]['used_free_transfer'] == 't')
		{
			return true;
		} else {
			return false;
		}
	}
	
	
	private function _isExchangingWith($uid, $withUid)
	{
		assert(is_int($uid) && $uid > 0);
		assert(is_int($withUid) && $withUid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				id_user
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$uid."
				AND
				exchange_with = ".$withUid."
				AND
				id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		if(count($res) == 1)
		{
			return true;
		} 
		return false;
	}
	
	private function _out()
	{
		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = constant('SMARTY_CACHE_PATH');
		$tpl->compile_check = true;
		$tpl->debugging = false;
		
		
		$tpl->assign('WORLD_SELECTED', $this->worldFromSelected);
		
		if(!$this->worldFromSelected)
		{
			$tpl->assign('WORLDS', $this->worlds);
		} else {
			$transferOrdered  = $this->_isTransferOrdered($this->uid);
			$usedTransfer = $this->_isUsedTransfer($this->uid);
			
			$worldTransferAllowed = false;
			$tpl->assign('TRANSFER_ALLOWED', $this->_checkTransferAllow());
			$tpl->assign('TRANSFER_LIMIT', $this->_getFreeTransferLimit());
			$tpl->assign('WORLD', $this->dbId.'_'.$this->worldId);
			$tpl->assign('USED_TRANSFER', $usedTransfer);
			$tpl->assign('TRANSFER_ORDERED', $transferOrdered);

			if(!$usedTransfer && !$transferOrdered)
			{
				$comb = $this->_getCombData($this->_getCombIdByUID($this->uid));
				$exchange = $this->_isSomeoneExchange($this->uid);
				$tpl->assign('COMB', $comb);
				$tpl->assign('EXCHANGE', $exchange);
				
				$exchangeMels = $this->_isSomeoneExchangeMels($this->uid);
				$tpl->assign('EXCHANGE_MELS', $exchangeMels);
			} else {			
				// info
				$info = $this->_getUserTransferInfo();
				if(count($info) > 0)
				{
					$tpl->assign('INFO', $info);
				}
			}

			if(!$transferOrdered)
			{// Смотрим, может ли игрок заказать переезд соты по кольцу
				$tpl->assign('CAN_SWAP', false);
				// Смотрим, разрешены ли переезды по мельсиону
				$allowMels = DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_TRANSFER_COLONIES');			
				if ($allowMels == 1) {
					// Смотрим, не использовал ли игрок уже свой переезд
					if ($this->_canSwapColonies()) {
						// Получаем колонии игрока
						$colonies = self::_getUserColonys($this->uid);
						if (count($colonies) > 0) {
							foreach ($colonies as $kolKey => $kolData)
							{
								$userColonies[] = $this->_getCombData($kolData['id_comb']);
								
							}
							$tpl->assign('COLONIES', $userColonies);
							$tpl->assign('CAN_SWAP', true);
						}
					}
				}
			}
		}

		// подключение файла с локализацией
		include $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/transfer.lang";
//		assert(is_array($lang));		
		$tpl->assign('lang', $lang);
		$tpl->assign('MSG', $this->errorMsg);
		unset($lang);
				
		$tpl->display("transfer.tpl");
	}
	
	private function _checkTransferAllow()
	{
		if(key_exists('world', $_SESSION))
		{
			$oldWorld = $_SESSION['world'];
		} else {
			$oldWorld = 0;
		}
		
		$_SESSION['world'] = $this->worldId;
		$allowed = (int)DE_WorldSettings::CreateForCurentWorld()->get('FREE_RING_TRANSFER_ALLOWED');
		
		$_SESSION['world'] = $oldWorld;
		
		return $allowed;
	}
	
	private function _getFreeTransferLimit()
	{
		if(key_exists('world', $_SESSION))
		{
			$oldWorld = $_SESSION['world'];
		} else {
			$oldWorld = 0;
		}
		
		$_SESSION['world'] = $this->worldId;
		$limit = (int)DE_WorldSettings::CreateForCurentWorld()->get('FREE_RING_TRANSFER_LIMIT');
		
		$_SESSION['world'] = $oldWorld;
		
		return $limit;
	}
	
	private function _checkFreeTransferLimit()
	{
		$limit = $this->_getFreeTransferLimit();
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_free_ring_transfer AS rt
			WHERE
				rt.id_user = ".$this->uid."
				AND
				rt.id_world = ".$this->worldId."
				AND
				rt.transfer_date < now() - '$limit DAY'::interval
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return (int)$res[0]['count'];
	}
	
	private function _getCombData($combId)
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				cn.combname,
				c.id_userinstance,
				cc.complexnumber,
				r.ringnumber,
				c.combnumber,
				c.id_comb
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
			WHERE
				c.id_comb = ".$combId."				
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res[0];
	}
	
	
	private function _checkCombForTransfer()
	{
		$combData = $this->_getCombData($this->combToId);//DS_Comb::GetByID($this->combToId);
		
		if((int)$combData['id_userinstance'] > 0 || !empty($combData['combname']))
		{
			$this->errorMsg = 'NOT_EMPTY_COMB';
			return false;
		}
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				u.id_user
			FROM
				ds_reservedcomb AS rc
				INNER JOIN ds_user AS u ON (u.username = rc.username)
			WHERE
				id_comb = ".$this->combToId."	
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) > 0)
		{
			if((int)$res[0]['id_user'] != $this->uid)
			{
				$this->errorMsg = 'COMB_RESERVED';
				return false;
			}
		}
		
		return true;
	}
	
	private function _checkCombForExchange()
	{
		$combData = $this->_getCombData($this->combToId);//DS_Comb::GetByID($this->combToId);
		
		if((int)$combData['id_userinstance'] > 0 && !empty($combData['combname']))
		{
			if($this->_isUsedTransfer($this->exchangeUID) && !$this->_isExchangingWith($this->exchangeUID, $this->uid))
			{
				$this->errorMsg = 'OPONENT_USED_TRASFER';
				return false;
			}	
		} else {
			$this->errorMsg = 'EMPTY_COMB';
			return false;
		}
		
		return true;
	}
	
	private function _setTransferInfo()
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$this->uid."
				AND
				id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if (!empty($this->combId)) {
			$fromComb = $this->combId;
		} else {
			$fromComb = 'NULL';
		}

		if($res[0]['count'] > 0)
		{
			$sql = "
				UPDATE ds_free_ring_transfer
				SET
					transfer_to = ".$this->combToId.",
					exchange_with = ".$this->exchangeUID.",
					used_free_transfer = true,
					id_world = ".$this->worldId.",
					transfer_from = $fromComb,
					transfer_complite = 'f'
				WHERE
					id_user = ".$this->uid."		
					AND
					id_world = ".$this->worldId."				
			";
		} else {
			$sql = "
				INSERT INTO ds_free_ring_transfer
					(transfer_to, exchange_with, used_free_transfer, id_user, id_userinstance, id_world, transfer_from, transfer_complite)
				VALUES
					(".$this->combToId.", ".$this->exchangeUID.", true, ".$this->uid.", ".$this->uiid.", ".$this->worldId.", $fromComb, 'f')
				";
		}
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$this->usedFreeTransfer = true;
	}
	
	private function _reserveComb()
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
			INSERT INTO ds_reservedcomb
				(id_comb, username, comment)
			SELECT
				".$this->combToId." as id_comb,
				username,
				'free ring transfer'
			FROM
				ds_user
			WHERE
				id_user = ".$this->uid."
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _unReserveOldComb()
	{
		assert($this->combId > 0);
		
		$this->common->setDbID($this->dbId);
				
		$sql = "DELETE FROM ds_reservedcomb WHERE id_comb = ".$this->combId." AND comment = 'free ring transfer'";

		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _transfer()
	{
		if($this->_isUsedTransfer($this->uid))
		{
			$this->errorMsg = 'TRANSFER_USED';
			return false;
		}
		
		if(!$this->_checkCombForTransfer())
		{
			return false;
		}
		
		$this->_reserveComb();
		
		$this->_setTransferInfo();
		
		$this->transferOrdered = true;
		
		return true;
	}
	
	private function _swapColonies()
	{
		if(!$this->_canSwapColonies())
		{
			$this->errorMsg = 'TRANSFER_USED';
			return false;
		}
		
		if(!$this->_checkCombForTransfer())
		{
			return false;
		}
		
		$this->_reserveComb();
		
		$this->_setTransferInfo();
		
		$this->_updateSwapInfo();
		
		// Проверка на то, чтобы старая сота игрока не осталась зарезервирована за ним
		$this->_unReserveOldComb();
		
		return true;
	}
	
	private function _exchange()
	{
		if($this->_isUsedTransfer($this->uid))
		{
			$this->errorMsg = 'TRANSFER_USED';
			return false;
		}
		
		if(!$this->_checkCombForExchange())
		{
			return false;
		}
			
		$this->_setTransferInfo();
		
		$this->transferOrdered = true;
		
		return true;
	} 
	
	private function _excahgeSwapColonies()
	{
		if(!$this->_canSwapColonies())
		{
			$this->errorMsg = 'TRANSFER_USED';
			return false;
		}
		
		if(!$this->_checkCombForExchange())
		{
			return false;
		}
			
		$this->_setTransferInfo();
		
		$this->transferOrdered = true;
		
		$this->_updateSwapInfo();
		
		return true;
	} 
	
	private function _cancelTransfer()
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				*
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = ".$this->uid."
				AND
				id_world = ".$this->worldId."
			";

		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) == 0)
		{
			$this->errorMsg = 'NO_TRANSFER';
			return;
		}
		
		if($res[0]['transfer_complite'] == 't')
		{
			$this->errorMsg = 'CANT_CANCEL_USED_TRANSFER';
			return;
		}
		
		$sql = "DELETE FROM ds_reservedcomb WHERE id_comb = ".$res[0]['transfer_to']."";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		$sql = "
			UPDATE ds_free_ring_transfer
			SET
				used_free_transfer = false,
				transfer_to = 0,
				exchange_with = 0
			WHERE
				id_user = ".$this->uid."
				AND
				id_world = ".$this->worldId."
			";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		if (!empty($res[0]['transfer_from'])) {
			$sql = "DELETE FROM used_transfer_colony WHERE id_user = ".$this->uid."";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
		}
		
		//$this->_out();
	}

	private function _getWorldNameByWorldId()
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				worldname
			FROM
				ds_world
			WHERE
				id_world = ".$this->worldId."
			";
		$res = $this->common->getresultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		assert(count($res) == 1);
	
		return $res[0]['worldname'];
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
	
	private function _canSwapColonies()
	{
		$sql = "
			SELECT
				*
			FROM
				used_transfer_colony
			WHERE
				id_user = ".$this->uid."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res) == 1)
		{
			return false;
		}
		return true;
	}
	
	private function _updateSwapInfo()
	{
		$sql = "INSERT INTO  used_transfer_colony (id_user) VALUES (".$this->uid.")";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _getUserColonys($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$this->common->setDbID($this->dbId);
		
		$sql = "
			SELECT
				c.id_comb,
				ui.id_userinstance
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = c.id_userinstance)
			WHERE
				r.ringnumber = 4
				AND
				ui.id_user = ".$uid."
				AND
     			r.id_world = ".$this->worldId."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "");		
		assert(empty($this->common->errormsg));
		
		$this->uiid = (int)$res[0]['id_userinstance'];

		return $res;
	}
	
	private function _getCombDataByCoordinates($worldId, $ring, $complex, $comb)
	{
		$this->common->setDbID($this->dbId);
		
		$sql = "
				SELECT
				cn.combname,
				c.id_userinstance,
				c.id_comb,
				u.id_user
			FROM
				ds_comb AS c
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
				INNER JOIN ds_combname AS cn ON (cn.id_comb = c.id_comb)
				INNER JOIN ds_userinstance ui ON (ui.id_userinstance = c.id_userinstance)
                INNER JOIN ds_user u ON (u.id_user = ui.id_user)
			WHERE
				r.ringnumber = $ring
				AND
				cc.complexnumber = $complex
				AND
				c.combnumber = $comb
				AND
     			r.id_world = ".$worldId."			
			";		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res[0];
	}
}

?>
<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_ExecFreeTransfer
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $dbId;
	
	static private $worldId;
	
	/**
	 * Enter description here...
	 *
	 * @var SU_ChangCombCoords
	 */
	static private $changeControler;
	
	static private $logFile;
	
	static private $exchanged = array();
	
	static public function Exec($dbId, $worldId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		self::$dbId = $dbId;
		self::$worldId = $worldId;
		
		self::$logFile = fopen($GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN'].'/FreeTransfer.log', 'a+');
		
		self::_out("START MOVE DB ".self::$dbId." WORLD ".self::$worldId);
		
		if(GameDown(self::$dbId, self::$worldId))
		{
			self::_out("GAME IS CLOSED");
			return false;
		}
		
		self::$common = DS_Common::CreateForCurentDB(self::$dbId);
		
		$worldName = self::_getWorldNameByWorldId();
		
		self::_out(" ======== ".$worldName." ========= ");
		
		self::$changeControler = new SU_ChangCombCoords($worldName);
		
		//self::$common->startTrans();
		
		$transferList = self::_getCombsToMove();
		
		self::_out("READY TO MOVE : ".count($transferList));
		self::_out("===================================");
		
		foreach($transferList as $k => $data)
		{
			self::_out($k."  =  USER ID : ".$data['id_user'].";  EXCHANGE WITH : ".$data['exchange_with']);
			if(!self::_move($data))
			{
				self::_out("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< ERROR : ".self::$changeControler->error_status);
			}
			self::_out("===================================");
		} 
		
		self::_clearOldTransfers();
		
		//self::$common->closeTrans('COMMIT');
		
		self::_out("DONE");
		self::_out("===================================");
		fclose(self::$logFile);
	}	
	
	
	private static function _getWorldNameByWorldId()
	{
		$sql = "
			SELECT
				worldname
			FROM
				ds_world
			WHERE
				id_world = ".self::$worldId."
			";
		$res = self::$common->getresultArray("", $sql, "", "", "");		
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
	
		return $res[0]['worldname'];
	}
	
	private static function _getCombIdByUID($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$sql = "
			SELECT
				c.id_comb
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
     			r.id_world = ".self::$worldId."
			";
		$res = self::$common->getresultArray("", $sql, "", "", "");		
		assert(empty(self::$common->errormsg));
		if(count($res) != 1)
		{
			return 0;
		}

		return (int)$res[0]['id_comb'];
	}
	
	static private function _getCombsToMove()
	{
		$sql = "
			SELECT
				wu.id_user,
				wu.transfer_from,
				wu.transfer_to,
				wu.exchange_with
			FROM
				ds_free_ring_transfer AS wu
			WHERE
				wu.used_free_transfer is true
				AND
				NOT wu.transfer_complite is true
				AND
				wu.id_world = ".self::$worldId."
			";
		$res = self::$common->getresultArray("", $sql, "", "", "");		
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _checkExchange($id_user, $excnhange_with)
	{
		assert(is_int($id_user) && $id_user > 0);
		assert(is_int($excnhange_with) && $excnhange_with > 0);
		
		$sql = "
			SELECT
				id_user
			FROM
				ds_free_ring_transfer
			WHERE
				id_user = $excnhange_with
				AND
				exchange_with = $id_user
				AND
				id_world = ".self::$worldId."
				AND
				NOT transfer_complite is true
			";
		
		$res = self::$common->getresultArray("", $sql, "", "", "");		
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 1)
		{
			return true;
		}
		
		return false;
	}
	
	static private function _checkUserExist($id_user)
	{
		assert(is_int($id_user) && $id_user > 0);
		
		$sql = "
			SELECT
			      *
			FROM
			    ds_userinstance
			WHERE 
			     id_user = $id_user
			     AND 
			     id_world =  ".self::$worldId."
			";
		
		$res = self::$common->getresultArray("", $sql, "", "", "");		
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 1)
		{
			return true;
		}
		
		return false;
	}
	
	static private function _updateInfo($uid)
	{
		assert(is_int($uid) && $uid > 0);
		
		$sql = "
			UPDATE ds_free_ring_transfer
			SET
				transfer_complite = true,
				transfer_date = now()
			WHERE
				id_user = $uid
				AND
				id_world = ".self::$worldId."
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	static private function _move($data)
	{
		assert(is_array($data));
		assert(key_exists('id_user', $data));
		assert(key_exists('transfer_to', $data));
		assert(key_exists('exchange_with', $data));
		
		if((int)$data['exchange_with'] > 0)
		{
			if(!self::_checkExchange((int)$data['id_user'], (int)$data['exchange_with']))
			{
				self::_out("USER DON'T HAVE PAIR TO EXCHANGE");
				return true;
			} elseif(in_array((int)$data['id_user'], self::$exchanged)) {
				self::_out("ALREADY EXCHANGED");
				return true;
			}
		}
		
		// ѕровер€ем наличие игрока в данном мире
		if(!self::_checkUserExist((int)$data['id_user']))
		{
			self::_out("USER DON'T EXIST IN THIS WORLD");
			return true;
		}
		
		if (empty($data['transfer_from'])) 
		{
			$combFromId = (int)self::_getCombIdByUID((int)$data['id_user']);
		} else {
			$combFromId = (int)$data['transfer_from'];
		}

		$combFrom = DS_Comb::GetByID($combFromId);
		$combFromCoords = $combFrom['ringnumber'].".".$combFrom['complexnumber'].".".$combFrom['combnumber'];
		
		$combToId = (int)$data['transfer_to'];
		$combTo = DS_Comb::GetByID($combToId);
		$combToCoords = $combTo['ringnumber'].".".$combTo['complexnumber'].".".$combTo['combnumber'];
		
		self::_out("CHANGE COMB COORDS $combFromCoords => $combToCoords");
		$res = self::$changeControler->change($combToCoords, $combFromCoords);
		
		if($res)
		{
			self::$common->startTrans();
			
			self::_updateInfo((int)$data['id_user']);
			if((int)$data['exchange_with'] > 0)
			{
				self::_updateInfo((int)$data['exchange_with']);
				self::$exchanged[] = (int)$data['exchange_with'];
			}
			
			self::$common->closeTrans('COMMIT');
			self::_out("OK");
			return true;
		} 
		
		return false;
	}
	
	private function _clearTransferWithoutPair()
	{
		$sql = "
			UPDATE ds_free_ring_transfer 
			SET
				used_free_transfer = false,
				transfer_to = 0,
				exchange_with = 0
			WHERE
				transfer_complite IS FALSE
				AND
				exchange_with IN 
				(
					SELECT
						id_user
					FROM
						ds_free_ring_transfer
					WHERE
						transfer_complite IS TRUE
						AND
						id_world = ".self::$worldId."
				)
				AND
				id_world = ".self::$worldId."
			";
	}
	
	private function _clearOldTransfers()
	{
		$_SESSION['world'] = self::$worldId;
		$_SESSION['dbId'] = self::$dbId;
		$limit = (int)DE_WorldSettings::CreateForCurentWorld()->get('FREE_RING_TRANSFER_LIMIT');
		
		$sql = "
			DELETE FROM 
				ds_free_ring_transfer 
			WHERE
				id_world = ".self::$worldId."
				AND
				transfer_date < now() - '$limit DAY'::interval				
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	private function _out($msg)
	{
		$msg = date("Y-d-m H:i")."  -  ".$msg."\n";
		
		echo $msg;
		
		fwrite(self::$logFile, $msg);
		fflush(self::$logFile);
	}
}
?>
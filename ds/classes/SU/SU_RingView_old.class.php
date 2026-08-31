<?php $CVS_Version = '$Revision$ ; $Date$';
require_once constant('INCLUDE_PATH')."/Smarty/Smarty.class.php";

define('COMPLEX_PER_PAGE', 60);

class SU_RingView
{
	private $rind;
	
	private $worldID;
	
	private $common;
	
	private $offset = 0;
	
	private $uid;
	
	private $user;
	
	private $uiid;
	
	private $dbId;
	
	private $dbId_WorldUnion = 0;
	
	private $msg = '';
	
	function __construct($dbId, $world, $data, $ring = 4)
	{
		assert(is_int($ring) && $ring > 0 && $ring < 5);
		assert(is_int($world) && $world > 0);
		assert(is_int($dbId) && $dbId >= 0);
		$this->ring = $ring;
		$this->worldID = $world;
		
		$this->dbId_WorldUnion = $dbId;
		
		SU_GameSessionWork::SessionContinue();
		
		if(GameDown($this->dbId_WorldUnion, $this->worldID))
		{
			die("Game closed");
		}
		
		$this->common = DS_Common::CreateForCurentDB($this->dbId_WorldUnion);
		
		$this->_getParams($data);
		
		$this->_outComplexMap();
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		assert(key_exists('uid', $_SESSION));
		$this->uid = (int)$_SESSION['uid'];
		assert($this->uid > 0);
		
		$this->common->setDbId(0);
		GE_SearchStart::SetCommon($this->common);
		$this->user = DE_User::CreateFromID($this->uid);
		
		assert(key_exists('profi_game', $_SESSION));
		
		if(key_exists('offset', $data))
		{
			$this->offset = ((int) $data['offset']) - 1;
			if($this->offset < 0)
			{
				$this->offset = 0;				
			}
			//assert($this->offset >= 0);
			$maxComplex = $this->_getCountOfComplex();
			
			if($this->offset > $maxComplex - 1)
			{
				$this->offset = $maxComplex - 10;
			}
		}

		if(key_exists('uiid', $data))
		{
			$this->_setWorldUnionUserInfo($data['uiid']);
		}
		
		if(key_exists('res_comb', $data))
		{
			$this->_userHasInstanceInWorldsToMerge();
			$this->_reserveComb((int)$data['res_comb']);			
		}	
		
		if(key_exists('priority', $data) && key_exists('id_comb', $data))
		{
			foreach($data['priority'] as $id_comb => $priority)
			$this->_setReservedCombsPriority((int)$id_comb, (int)$priority);
		}
		
		if(key_exists('clear', $data))
		{
			$this->_clearReserve();
		}
	}

	private function _getComplexData()
	{
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "
            SELECT
				u.username,
      			co.combscore,
      			co.id_comb,
      			cn.combname,
      			co.combnumber,
      			cc.complexnumber,
      			ri.ringnumber,
      			rc.username AS reserved_by,
      			rc.priority
			FROM
    			ds_comb AS co
            	LEFT JOIN ds_combname AS cn ON (co.id_comb = cn.id_comb)
              	INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                INNER JOIN ds_ring AS ri ON (cc.id_ring = ri.id_ring)
				LEFT JOIN ds_userinstance AS ui ON (ui.id_userinstance = co.id_userinstance)
				LEFT JOIN ds_user AS u ON (u.id_user = ui.id_user)
				LEFT JOIN ds_reservedcomb AS rc ON (rc.id_comb = co.id_comb)
			WHERE
				ri.ringnumber = ".$this->ring."
				AND
				ri.id_world = ".$this->worldID."
			ORDER BY cc.complexnumber, co.combnumber
			OFFSET ".($this->offset*6)."
			LIMIT ".constant('COMPLEX_PER_PAGE')."
		";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		return $res;
	}
	
	private function _getCountOfComplex()
	{
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_combcomplex AS cc 
				INNER JOIN ds_ring As r ON (r.id_ring = cc.id_ring)
			WHERE
				r.id_world = ".$this->worldID."
				AND
				r.ringnumber = ".$this->ring."
			";
		$res = $this->common->getResultArray("", $sql, "", "", "GLOBAL_CACHE");
		assert(empty($this->common->errormsg));
		
		return $res[0]['count'];
	}
	
	private function _getUserCombsData()
	{
		$this->common->setDbId($this->dbId);
		
		$sql = "
			SELECT
				co.id_comb,
				cn.combname,
				ri.ringnumber,
				cc.complexnumber,
				co.combnumber
			FROM
				ds_comb AS co
            	LEFT JOIN ds_combname AS cn ON (co.id_comb = cn.id_comb)
              	INNER JOIN ds_combcomplex AS cc ON (co.id_combcomplex = cc.id_combcomplex)
                INNER JOIN ds_ring AS ri ON (cc.id_ring = ri.id_ring)
			WHERE
				co.id_userinstance = ".$this->uiid."
				AND
				ri.ringnumber = 4
			ORDER BY 
				cc.complexnumber,
				co.combnumber
			";
		$res = $this->common->getResultArray("userinstance_colonyes_".$this->uiid.$this->dbId, $sql, "", "", "GLOBAL_CACHE");
		assert(empty($this->common->errormsg));

		$this->common->setDbId($this->dbId_WorldUnion);
		
		return $res;
	}
	
	private function _getReservedCombsWithPriority()
	{
		assert(key_exists('s_login', $_SESSION));
		
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "
			SELECT
				priority
			FROM
				ds_reservedcomb
			WHERE
				username = '".$_SESSION['s_login']."'
				AND
				NOT priority = 0
			";		
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		$priority = array();
		
		foreach($res as $k => $reserved)
		{
			$priority[$k] = $reserved['priority'];
		}
		
		return $priority;
	}
	
	
	private function _setReservedCombsPriority($id_comb, $priority)
	{
		assert(is_int($priority) && $priority >= 0);
		assert(is_int($id_comb) && $id_comb > 0);		
		assert(key_exists('s_login', $_SESSION));
		
		$this->common->setDbId($this->dbId_WorldUnion);
		
		if($priority > 0)
		{
			$sql = "
				SELECT
					priority
				FROM
					ds_reservedcomb
				WHERE
					username = '".$_SESSION['s_login']."'
					AND
					NOT id_comb = $id_comb
					AND
					priority = $priority
				";
			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
			if(count($res) > 0)
			{
				$this->msg = '';
				return;
			}
		}
			
		$sql = "
			UPDATE 
				ds_reservedcomb
			SET
				priority = ".$priority."
			WHERE
				id_comb = ".$id_comb."
			";
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _setWorldUnionUserInfo($uiid)
	{
		$tmpArr = explode("_", $uiid);
		assert(count($tmpArr) == 3);
		$dbId = (int) $tmpArr[0];
		$worldId = (int) $tmpArr[1];
		$uiid = (int) $tmpArr[2];
		
		assert($dbId >= 0);
		assert($worldId > 0);
		assert($uiid > 0);
		
		$server = $GLOBALS['dbWorldAccess'][$dbId]['server'];
		
		$userInfo = $this->_getWorldUnionUserInfo();
		
		$this->common->setDbId($this->dbId_WorldUnion);
		
		if(count($userInfo) == 0)
		{
			$sql = "
				INSERT INTO ds_worldunionuserinfo
					(id_user, id_userinstance, id_world, server_name)
				VALUES
					(".$this->uid.", ".$uiid.", ".$worldId.", '".$server."')
				";
		} else {
			$sql = "
				UPDATE ds_worldunionuserinfo
				SET
					id_userinstance = ".$uiid.",
					id_world = ".$worldId.",
					server_name = '".$server."'
				WHERE
					id_user = ".$this->uid."
				";
		}
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _getWorldUnionUserInfo()
	{
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "
			SELECT
				*
			FROM
				ds_worldunionuserinfo
			WHERE
				id_user = ".$this->uid."	
			";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return $res;
	}
	
	private function _outComplexMap()
	{
		// создаем объект смарти
		$tpl = new Smarty;
		$tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$tpl->compile_check = true;
		$tpl->debugging = false;
		
		$combComplexData = $this->_getComplexData();
		
		$complexNumber = $combComplexData[0]['complexnumber'];
		
		$combComplex = array();
		
		foreach($combComplexData as $k => $comb)
		{
			if($complexNumber != $comb['complexnumber'])
			{
				$complexNumber = $comb['complexnumber'];
			}		
			$combComplex[$complexNumber][$comb['combnumber']] = $comb;
		}
		
		$tpl->assign('COMPLEX_DATA', $combComplex);
		$tpl->assign('COMPLEX_COUNT', $complexNumber + 1);
		$tpl->assign('OFFSET', $this->offset); 
		
		$instances = $this->_userHasInstanceInWorldsToMerge();
		$tpl->assign('USER_INSTANCES', $instances);
		
		$tpl->assign('USERNAME', $_SESSION['s_login']);
		
		if(count($instances) == 1)
		{
			$userCombs = array();
			$combs = $this->_getUserCombsData();
			foreach($combs as $k => $comb)
			{
				$userCombs[$k+1] = $comb;
			}
			$tpl->assign('USER_COMBS', $userCombs);	
			
			$reserved = $this->_getCountReservedCombs();
			
			$tpl->assign('COMBS_WITH_PRIORITY', $this->_getReservedCombsWithPriority());
			
			$canReserve = count($combs) - $reserved;
				
			if($canReserve > 0)
			{
				$tpl->assign('CAN_RESERVE', true);
			} else {
				$tpl->assign('CAN_RESERVE', false);
			}
			
			$tpl->assign('RESERVED', $reserved);
		}
		
		// подключение файла с локализацией
		include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".constant('DEFAULT_LANGUAGE_SHORT')."/ring_view.lang";
		assert(is_array($lang));		
		$tpl->assign('lang', $lang);
		unset($lang);
		
		$tpl->display("ring_view.tpl");
	}
	
	
	
	private function _userHasInstanceInWorldsToMerge()
	{
		//'master_2', 'master_31',
		$worldsToMerge = $GLOBALS['WORLDS_UNION'];
		
		$instances = array();

		$userInfo = $this->_getWorldUnionUserInfo();
		if(count($userInfo) > 0)
		{
			foreach($GLOBALS['dbWorldAccess'] as $k => $db)
			{
				if($db['server'] == $userInfo[0]['server_name'])
				{
					$dbId = $k;
					break;
				}
			}
			
			$this->uiid = (int)$userInfo[0]['id_userinstance'];
			$this->dbId = (int)$dbId;
			
			$instances[] = $dbId."_".$userInfo[0]['id_world']."_".$userInfo[0]['id_userinstance'];
			
			return $instances;
		}
		
		$this->common->setDbId(0);
		$worldList = GE_LogicWorldsList::GetWorldsToEnter($this->user, $_SESSION['profi_game']);
		
		foreach($worldList as $k => $world)
		{
			$worldId = $world['dbname']."_".$world['id_world'];
			
			if(in_array($worldId, $worldsToMerge))
			{
				$instances[] = $world;
			}
		}
		
		if(count($instances) == 1)
		{
			$this->uiid = (int)$instances[0]['id_userinstance'];
			$this->dbId = (int)$instances[0]['dbId'];
		}
				
		return $instances;
	}
	
	private function _clearReserve()
	{
		assert(key_exists('s_login', $_SESSION));
		
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "DELETE FROM ds_reservedcomb	WHERE username = '".$_SESSION['s_login']."'";
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _reserveComb($resComb)
	{		
		assert($resComb > 0);
		
		$combs = $this->_getUserCombsData();

		if(count($combs) <= $this->_getCountReservedCombs())
		{
			return;
		}
		
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "
			SELECT
				*
			FROM
				ds_reservedcomb
			WHERE
				id_comb = ".$resComb."
				";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		if(count($res) > 0)
		{
			return;
		}
		
		$sql = "
			INSERT INTO ds_reservedcomb
				(id_comb, username)
			VALUES
				(".$resComb.", '".$this->user->getName()."')
			";
		
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
	}
	
	private function _getCountReservedCombs()
	{
		$this->common->setDbId($this->dbId_WorldUnion);
		
		$sql = "
			SELECT
				count(*)
			FROM
				ds_reservedcomb AS rc
				INNER JOIN ds_comb AS c ON (c.id_comb = rc.id_comb)
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
			WHERE
				username = '".$this->user->getName()."'
				AND
				r.id_world = ".$this->worldID."
			";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		if(count($res))
		{
			return($res[0]['count']);
		}
		
		return 0;
	}
}
?>
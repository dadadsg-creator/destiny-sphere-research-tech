<?php

/**
 * Продажа ресурсов за реальное бабло.
 *
 */
class BG_BuyResources extends BG_Common
{
	
	private $userID;
	
	/**
	 * инстанс, которому рисуем ресы
	 *
	 * @var int
	 */
	private $UIID;

	/**
	 * ИД базы
	 *
	 * @param int
	 */
	private $dbID;
	
	/**
	 * ИД мира
	 *
	 * @var int
	 */
	private $worldID;
	
	/**
	 * количество ресурсов
	 *
	 * @var int
	 */
	private $count;

	
	public function __construct($data)
	{
		parent::__construct($data);
	}
	
	/**
	 * проверка входных параметров
	 *
	 */
	private function _checkParams()
	{
		if($this->error_status != 0)
		{
			return $this->error_status;
		}
		
		if(!key_exists('id_user',$this->incoming_info) ||
			!key_exists('worldname',$this->incoming_info) ||
			!key_exists('res',$this->incoming_info) ||
			!key_exists('count',$this->incoming_info)) 
		{
			return 1;
		}
		
		$this->userID = (int)$this->incoming_info['id_user'];
		if($this->userID <= 0)
		{
			return 2;
		}
		
		$this->res = $this->incoming_info['res'];
		if(!in_array($this->res, GB_ResourcesList::$allResources))
		{
			return 3;
		}

		$this->count = (int)$this->incoming_info['count'];
		if($this->count <= 0)
		{
			return 4;
		}
		
		return 0;
	}
	
	private function _getUIID()
	{		
		$res = DS_BillingBuyResource::getUIIDByUIDAndWorldName($this->userID, $this->incoming_info['worldname']);
		
		if(count($res) != 1)
		{
			return false;
		}
				
		$this->uiid = (int)$res[0]['id_userinstance'];
		$this->worldID = (int)$res[0]['id_world'];
		
		$this->dbID = -99;
		
		foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			if($db['server'] == $res[0]['dbname'])
			{
				$this->dbID = (int)$dbId;
				break;
			}
		}
		
		if($this->dbID == -99)
		{
			return false;
		}
				
		return true;
	}
	
	/**
	 * Проверка возможности продажи ресурсов в выбранном мире
	 *
	 * @return unknown
	 */
	private function _checkPermission()
	{
		$_SESSION['dbId'] = $this->dbID;
		$_SESSION['world'] = $this->worldID;
		
		$buyAllowed = (int)DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_FOR_MONEY_ALLOWED');
		
		if($buyAllowed == 1)
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Проверка на ограничение на количество купленных ресурсов
	 *
	 * @return bool
	 */
	private function _checkUserLimit()
	{
		$_SESSION['dbId'] = $this->dbID;
		$_SESSION['world'] = $this->worldID;
		
		$boughtRes = DE_UserBoughtResources::CreateFromID($this->userID.'_'.$this->worldID);
		
		$new = $boughtRes->get($this->res) + $this->count;
		
		$max = DE_WorldSettings::CreateForCurentWorld()->get('BUY_RESOURCE_'.$this->res.'_LIMIT');
		
		if($new > $max)
		{
			// посылаем в ответе сколько еще может игрок купить ресов
			$this->addToResult('res_available', max(0, $max - $boughtRes->get($this->res)));
			return false;
		}
		
		return true;
	}
	
	/**
	 * Проверка возможности добавления ресурсов
	 *
	 * @param bool $fromCheck
	 * @return bool
	 */
	public function check($fromCheck = true)
	{
		$res = $this->_checkParams();
		if($res != 0)
		{
			$this->_returnResult($res, false);
			return false;
		}
		
		if(!$this->_getUIID())
		{
			$this->_returnResult('1_NO_USER_IN_WORLD', $fromCheck);
			return false;
		}
		
		if(!$this->_checkPermission())
		{
			$this->_returnResult('2_TRADE_NOT_ALLOWED_IN_WORLD', $fromCheck);
			return false;
		}
		
		if(!$this->_checkUserLimit())
		{
			$this->_returnResult('3_USER_LIMIT_EXCEEDED', $fromCheck);
			return false;
		}
		
		if($fromCheck)
		{
			$this->_returnResult('OK');
		}
		
		return true;
	}
	
	/**
	 * Рисуем ресурсы
	 *
	 */
	public function proceed()
	{
		if(!$this->check(false))
		{
			return;
		}
		
		$_SESSION['dbId'] = $this->dbID;
		$_SESSION['world'] = $this->worldID;
		
		$common = DS_Common::CreateForCurentDB($this->dbID);
		$common->startTrans();
		
		$combAttr = DE_CombAttributes::CreateFromCombID(DS_BillingBuyResource::getCombID($this->dbID, $this->uiid));
		$boughtRes = DE_UserBoughtResources::CreateFromID($this->userID.'_'.$this->worldID);
		
		$combAttr->deltaAttribute('CURRENT_AMOUNT', $this->res, $this->count);
		$boughtRes->delta($this->res, $this->count);
		
		$combAttr->commit();
		$boughtRes->commit();
		
		$common->closeTrans('COMMIT');
		
		$this->_returnResult('OK');
	}
	
	private function _returnResult($res, $check = true)
	{
		if($check)
		{
			$this->addToResult('res_added', $res);
		} else {
			$this->error_status = $res;
		}
		
		$this->outputAnswer();
	}
}

?>
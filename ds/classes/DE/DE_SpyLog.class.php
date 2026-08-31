<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Лог разведки
 *
 */
class DE_SpyLog extends DE_Base 
{
	/**
	 * Дата боя
	 *
	 * @var GB_Date
	 */
	private $date;
	
	/**
	 * С какой соты летели
	 *
	 * @var DE_Comb
	 */
	private $combFrom;
	
	/**
	 * На какую соту
	 *
	 * @var DE_Comb
	 */
	private $combTo;
	
	/**
	 * Тескт лога
	 *
	 * @var string ??
	 */
	private $text;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_User
	 */
	private $userFrom;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_User
	 */
	private $userTo;
	
	/**
	 * Действие
	 *
	 * @var DE_SpyAction
	 */
	private $action;
	
	private $title;
	
	private $head;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	private $accuracy;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_UserInstance
	 */
	private $owner;
	
	public function __construct($id, $data = null)
	{
		assert(is_int($id) && $id > 0);
		
		parent::__construct($id);
		
		if(is_null($data))
		{
			$data = DS_SpyBattle::GetLogDataByID($id);
		}
		assert(is_array($data));
				
		assert(key_exists('log_date', $data));
		$this->date = GB_TypeFactory::CreateBase('GB_Date', $data['log_date']);
		
		assert(key_exists('id_spy_action', $data));
		$this->action = DE_SpyAction::CreateFromID((int)$data['id_spy_action']);
		
		assert(key_exists('id_owner', $data));
		$this->owner = DE_UserInstance::CreateFromID((int)$data['id_owner']);
		
		assert(key_exists('id_comb_to', $data));
		if(!empty($data['id_comb_to']))
		{
			$this->combTo = DE_Comb::CreateFromID((int)$data['id_comb_to']);
		}
		
		assert(key_exists('id_comb_from', $data));
		if(!empty($data['id_comb_from']))
		{
			$this->combFrom = DE_Comb::CreateFromID((int)$data['id_comb_from']);
		}
		
		assert(key_exists('id_user_from', $data));
		if(!empty($data['id_user_from']))
		{
			$this->userFrom = DE_User::CreateFromID((int)$data['id_user_from']);
		}
		
		assert(key_exists('id_user_to', $data));
		if(!empty($data['id_user_to']))
		{
			$this->userTo = DE_User::CreateFromID((int)$data['id_user_to']);
		}
				
		assert(key_exists('log_text', $data));
		$this->text = (string)$data['log_text'];
		
		assert(key_exists('accuracy', $data));
		$this->accuracy = (int)$data['accuracy'];
		
		assert(key_exists('id_unit', $data));
		if(!empty($data['id_unit']))
		{
			$this->unit = DE_Unit::CreateFromID((int)$data['id_unit']);
		}
		
		$this->_initTitle();
		
		$this->_initHead();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $id
	 * @return DE_SpyLog
	 */
	static public function CreateFromID($id, $data = null)
	{
		assert(is_int($id) && $id > 0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data, 'WORLD_CACHE');
	}
	
	
	/**
	 * Enter description here...
	 *
	 * @param DE_SpyAction $action
	 * @param string $text
	 * @param DE_Comb $combFrom
	 * @param DE_Comb $combTo
	 * @param DE_User $userFrom
	 * @param DE_User $userTo
	 * @return DE_SpyLog
	 */
	static public function CreateInDB(DE_UserInstance $owner, DE_SpyAction $action, $text, GB_Date $date, DE_Unit $unit, $combFrom = null, $combTo = null, $userFrom = null, $userTo = null, $accuracy = 0)
	{
		assert(is_int($accuracy) && $accuracy >= 0);
		
		$data['log_date'] = $date->value;
		$data['id_owner'] = $owner->getID();
		$data['id_spy_action'] = $action->getID();
		$data['log_text'] = pg_escape_string($text);
		$data['accuracy'] = $accuracy;
		$data['id_unit'] = $unit->getID();
		
		if($combTo instanceof DE_Comb)
		{
			$data['id_comb_to'] = $combTo->getCombID();
		}

		if($combFrom instanceof DE_Comb)
		{
			$data['id_comb_from'] = $combFrom->getCombID();
		}
		
		if($userTo instanceof DE_User)
		{
			$data['id_user_to'] = $userTo->getID();
		}
		
		if($userFrom instanceof DE_User)
		{
			$data['id_user_from'] = $userFrom->getID();
		}
		
		$id = DS_SpyBattle::GetNextLogID();	
		$log = self::CreateFromID($id, $data);
		
		DS_SpyBattle::InsertLog($log);
		
		return $log;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_UserInstance
	 */
	public function getOwner()
	{
		return $this->owner;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombFrom()
	{
		return $this->combFrom;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Comb
	 */
	public function getCombTo()
	{
		return $this->combTo;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_User
	 */
	public function getUserFrom()
	{
		return $this->userFrom;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_User
	 */
	public function getUserTo()
	{
		return $this->userTo;
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function getCombFromFormated()
	{
		$combFrom = ' ';
		
		if($this->combFrom instanceof DE_Comb)
		{
			$combFrom .= $this->combFrom->getFullName();
		} 
		
		if($this->userFrom instanceof DE_User) 
		{
			$combFrom .= " ".$this->userFrom->getName();
		}
		
		return $combFrom.' ';
	}
	
	
	/**
	 * Enter description here...
	 *
	 */
	public function getCombToFormated()
	{
		$combTo = ' ';
		
		if($this->combTo instanceof DE_Comb)
		{
			$combTo .= $this->combTo->getFullName();
		} 
		
		if($this->userTo instanceof DE_User) 
		{
			$combTo .= " ".$this->userTo->getName();
		}
		
		return $combTo.' ';
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_Date
	 */
	public function getDate()
	{
		return $this->date;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_SpyAction
	 */
	public function getAction()
	{
		return $this->action;
	}
	
	private function _initTitle()
	{
		$messparams = array();
		$messparams[0] = $this->action->getLocalizedName();
		if($this->action->getName() == 'RESEARCH')
		{
			$messparams[1] = $this->combTo->getFormatedAddress();
		} else {
			$messparams[1] = $this->combTo->getFormatedAddress();
		}
		
		$this->title = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('SPY_LOG_TITLE', $messparams, null, null, false);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	private function _initHead()
	{
		$messparams = array();
		if($this->action->getName() == 'RESEARCH')
		{
			$messparams[0] = $this->date->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
			$messparams[1] = $this->combTo->getFullName();
			$messparams[2] = $this->combTo->getUserInstance()->getUser()->getName();
			//$messparams[3] = $this->combFrom->getFullName();
			$msg = 'SPY_LOG_RESEARCH_HEAD';
		} else {
			$messparams[0] = $this->date->getFormatedFull(DE_User::CreateForCurrentUser()->getTimeDelta());
			$messparams[1] = $this->combTo->getFullName();
			$msg = 'SPY_LOG_DEFEND_HEAD';
		}
		
		$this->head = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($msg, $messparams, null, null, false);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function getHeader()
	{
		return $this->head;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getLogText()
	{
		return $this->text;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return int
	 */
	public function getAccuracy()
	{
		return $this->accuracy;
	}
	
	/**
	 * 
	 *
	 * @return DE_Unit
	 */
	public function getUnit()
	{
		return $this->unit;
	}
}

?>
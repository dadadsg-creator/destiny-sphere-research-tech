<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * ОБъект с командой (данными) для совершения рефреша.
 *
 */
class CR_RefreshUserInstanceCommand extends BS_Base
{
	/**
	 * Инстанс который рефрешим
	 *
	 * @var DE_UserInstance
	 */
	private $userInstance;	
	
	/**
	 * Флаг того что ефреш будет с ивентами
	 *
	 * @var boolean
	 */
	private $withEvents = true;
	
	/**
	 * Рефреш с обсчетом ресурсов
	 *
	 * @var boolean
	 */
	private $withTurnCalc = true;
	
	/**
	 * Внешний ли сейча срефреш
	 *
	 * @var boolean
	 */
	private $isExternalRefresh = false;
	
	/**
	 * Частичный ли сейчас рефреш
	 *
	 * @var boolean
	 */
	private $isPartialRefresh = false;
	
	/**
	 * Дата момента внешнего рейреша
	 *
	 * @var GB_Date
	 */
	private $checkDate;
	
	/**
	 * Внешний ивент вызвавший рефреш соты
	 *
	 * @var DE_EventQueue
	 */
	private $externalEvent;
	
	public function __construct(DE_UserInstance $userInstance)
	{
		$this->userInstance = $userInstance;
	}
	
	public function setRefreshType($withEvents, $withTurnCalc)
	{
		assert(is_bool($withEvents));
		assert(is_bool($withTurnCalc));
		
		$this->withEvents = $withEvents;
		$this->withTurnCalc = $withTurnCalc;
	}
	
	public function setExternalRefreshMode(GB_Date $checkDate, DE_EventQueue $externalEvent)
	{
		$this->isExternalRefresh = true;
		
		$this->checkDate = $checkDate;
		$this->externalEvent = $externalEvent;
	}	
	
	public function setPartialRefreshMode(GB_Date $checkDate)
	{
		$this->isPartialRefresh = true;
		
		$this->checkDate = $checkDate;
	}
	
	public function getUserInstance()
	{
		return $this->userInstance;
	}
	
	public function getWithEvents()
	{
		return $this->withEvents;
	}
	
	public function getWithTurnCalc()
	{
		return $this->withTurnCalc;
	}
	
	public function getIsExternalRefresh()
	{
		return $this->isExternalRefresh;
	}
	
	public function getIsPartialRefresh()
	{
		return $this->isPartialRefresh;
	}
	
	public function getCheckDate()
	{
		assert($this->isExternalRefresh);
		
		return $this->checkDate;
	}
	
	public function getExternalEvent()
	{
		assert($this->isExternalRefresh);
		
		return $this->externalEvent;
	}
}

?>
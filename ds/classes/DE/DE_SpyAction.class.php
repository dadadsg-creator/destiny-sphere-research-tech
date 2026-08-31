<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Объект тип шпионского действия
 *
 */
class DE_SpyAction extends DE_Base
{
	/**
	 * Название
	 *
	 * @var unknown_type
	 */
	private $actionName;
	
	/**
	 * Нужны ли координаты
	 * (используется для меню)
	 * 
	 * @var unknown_type
	 */
	private $needCoordinates;
	
	/**
	 * Нужно ли время
	 * (используется для меню)
	 * 
	 * @var unknown_type
	 */
	private $needTime;
	
	/**
	 * ИД локализованного названия
	 *
	 * @var unknown_type
	 */
	private $localizedNameID;
	
	/**
	 * Ид евента который создается при действии
	 *
	 * @var unknown_type
	 */
	private $producedEventID;
	
	function __construct($id)
	{
		assert(is_int($id) && $id > 0);
		
		parent::__construct($id);	
		
		$data = DS_SpyAction::GetSpyActionDataByID($id);
		assert(is_array($data) && count($data) > 0);
		
		assert(key_exists('action_name', $data));
		$this->actionName = $data['action_name'];
		assert(!empty($this->actionName));
		
		assert(key_exists('need_coordinates', $data));
		$this->needCoordinates = $data['need_coordinates'] == 't' ? true : false;
		
		assert(key_exists('need_time', $data));
		$this->needTime = $data['need_time'] == 't' ? true : false;
		
		assert(key_exists('id_localizedname', $data));
		$this->localizedNameID = (int)$data['id_localizedname'];
		
		assert(key_exists('id_produced_event', $data));
		$this->producedEventID = (int)$data['id_produced_event'];
		assert($this->producedEventID > 0);
	}
	
	/**
	 * Создание на основании ID
	 *
	 * @param int $id
	 * @return DE_SpyAction
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id > 0);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, null, 'GLOBAL_CACHE');
	}
	
	/**
	 * Создание по имени
	 *
	 * @param string $name
	 * @return DE_SpyAction
	 */
	static public function CreateFromName($name)
	{		
		assert(!empty($name));
		assert(preg_match('/^[A-Z_]+$/', $name));
		
		$id = DS_SpyAction::GetSpyActionIDByName($name);
		assert(is_int($id) && $id > 0);
		
		return self::CreateFromID($id);
	}
	
	public function getName()
	{
		return $this->actionName;
	}
	
	public function needCoordinates()
	{
		return $this->needCoordinates;
	}
	
	public function needTime()
	{
		return $this->needTime;
	}
	
	public function getLocalizedName()
	{
		return DE_Localization::CreateForCurentLang()->get($this->localizedNameID);
	}
	
	/**
	 * Евент которые создается при этом типе действия
	 *
	 * @return DE_Event
	 */
	public function getEvent()
	{
		return DE_Event::CreateFromID($this->producedEventID);
	}
}

?>
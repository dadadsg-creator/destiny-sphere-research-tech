<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Абстьрактный класса. Задает интерфес класс сущности.
 */
abstract class DE_Base extends BS_Base implements DE_CommitableInterface
{
	/** Каждая сущность обязана иметь свой ID */
	protected $id;
	
	/**  Флаг того что сущность была изменена */
	protected $isModifyed = false;	
	
	/** Фла того что сущность еще не вставленна в БД */
	protected $needInsert = false;
	
	/**
	 * Флаг того что объект удален.
	 *
	 * @var boolean
	 */
	public $isDeleted = false;
	
	/**
	 * Флаг того что данному объекту нужна доволнительная обработка удаления при общем комоите
	 *
	 * @var boolean
	 */
	public $needAdditionalDeleteProcess = false;
	
	public function __construct($id=null)
	{
		assert(!is_null($id));
		
		parent::__construct();

		if(gettype($id == 'string'))
		{
			assert(!empty($id) || $id == 0);	
		}else{
			assert(is_int($id) && $id >= 0);
		}
			
		$this->id = $id;		
	}
	
	public function getNeedAdditionalDeleteProcess()
	{
		return $this->needAdditionalDeleteProcess;
	}
	
	/**
	 * Возыращает уникалбны ID объекта
	 *
	 * @return mix
	 */
	public function getID()
	{
		return $this->id;
	}
			
	public function delete()
	{
		$this->isDeleted = true;
		GB_SingletonObjectStorage::DeleteObject($this);
	}
	
	public function additionDelete()
	{
				
	}
	
	/**
	 * Проверка на идентичность двух объеквто. Сравнение идет на основание класса и  ID
	 *
	 * @param DE_Base $entity
	 * @return bool
	 */
	public function equals(DE_Base $entity)
 	{
		$this->_invariant();
		$className = get_class($this);
		assert(!is_null($entity) && ($entity instanceof $className) );
				
 		if($entity->getID() === $this->getID())
 		{
 			return true;
 		}else{
 			return false;
 		}
 	}
		
	/**
	 * Базовый метод для коммита сущности.
	 * Занимаеться тем что переберает атрибуты и коммитед если
	 * это ссылка на объект другой сущности.
	 *
	 */
	public function commit()
	{
		$this->_invariant();
		foreach ($this as $k=>$v)
		{
			if($v instanceof DE_CommitableInterface)
			{
				if(!$v->isDeleted)
				{
					$v->commit();	
				}				
			}
		}	
	}	
	
	public function setNeedInsert($value)
	{
		assert(is_bool($value));
		
		$this->needInsert = $value;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}

}//END OF CLASS

?>
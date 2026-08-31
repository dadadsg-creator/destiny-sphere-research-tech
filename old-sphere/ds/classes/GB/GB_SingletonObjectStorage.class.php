<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Статический класс для хранения объектом в глобальной области.
 * Используетсья для реализации паттрена Singleton.
 * 
 */
class GB_SingletonObjectStorage
{
	
	/**
	 * Здесь и хроняться ссылки на все объекты. Двумерный массив.
	 *
	 * @var array
	 */
	static public $storage = array();
	
	/**
	 * Объекты которые трубуют дополнилеьной обработки удаления во врем яобщего комита
	 *
	 * @var array
	 */
	static public $odjectsForAdditionalDeleteProcessing = array();	
	
	/**
	 * Возвращает массив с объектами данного класс
	 *
	 * @param string $className
	 * @return GB_Array
	 */
	static public function GetObjectsOfClass($className, $returnAsSimplArray = false)
	{
		assert(!empty($className));
		
		$objectsList = new GB_Array('integer', $className);
		
		if(!key_exists($className, self::$storage))
		{
			if($returnAsSimplArray)
			{
				return array();	
			}else{
				return $objectsList;	
			}			
		}
		
		if($returnAsSimplArray)
		{
			return self::$storage[$className];
		}else{
			foreach(self::$storage[$className] as $k=>$v)
			{
				$objectsList->addElement(null, $v);
			}
		
			return $objectsList;	
		}
		
	}
	
	/**
	 * Метод используетсья для вызова в конструкторе классов
	 * для проверки вызван конструтор из сингетона или нет.
	 */
	static public function TestSingletonCreation()
	{
		//Времено закрыта работа метода так как есть подозрения что
		//использование функции debug_backtrace() сильно перегружает сервер
		//и вызывает ошики вызванные обрщением к несуществующей памяте.
		
		/*$haveSingleton = 0;
		
		foreach(debug_backtrace() as $k=>$v)
		{
			if(array_key_exists('class', $v) && $v['class'] == __CLASS__) 
			{
				$haveSingleton++;
			}
		}
		
		assert($haveSingleton>1);	*/	
	}
	
	static public function HaveObjectInGraveYard($className, $id)
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		
		if(!key_exists($className, self::$odjectsForAdditionalDeleteProcessing))
		{
			return false;
		}
		
		if(!key_exists($id, self::$odjectsForAdditionalDeleteProcessing[$className]))
		{
			return false;
		}
		
		return true;
	}
	
	static public function GetIDsFromGraveYardByClass($className, $insertIfEmpty = false)
	{
		assert(!empty($className));
		
		$out = array();		
		
		if(key_exists($className, self::$odjectsForAdditionalDeleteProcessing))
		{
			$out = array_keys(self::$odjectsForAdditionalDeleteProcessing[$className]);
		}
		
		if($insertIfEmpty && count($out) == 0)
		{
			$out[] = 0;
		}
		
		return $out;		
	}
	
	
	/**
	 * Commit всех сущностей из хранилища которые 
	 * поддреживают интерфейс DE_CommitableInterface
	 *
	 */
	static public function CommitAllCommitable()
	{
		
		//дополнительные операции по удаления если нужны
		foreach(self::$odjectsForAdditionalDeleteProcessing as $className=>$objectsArray)
		{
			foreach($objectsArray as $id=>$object)
			{
				$object->additionDelete();	
			}			
		}
				
		//Делаем коммист всего что есть
		foreach(self::$storage as $className=>$objectsArray)
		{
			if(is_array($objectsArray))
			{
				foreach($objectsArray as $objectID=>$object)
				{
					if($object instanceof DE_CommitableInterface)
					{	
						$object->commit();
					}
				}
			}
		}		
	}
	
	static public function RefreshObjectInCollections($newElement)
	{				
		//Проходимся по коллекциям и удаляем из них
		foreach(self::$storage as $className=>$objectsArray)
		{
			if(!is_array($objectsArray) && !ereg('DC_', $className))
			{
				continue;
			}
			
			foreach($objectsArray as $objectID=>$object)
			{
				if(!($object instanceof DC_Base))
				{
					continue;
				}
				
				$object->refreshElement($newElement);			
			}
		}
	}
	
	static public function DeleteObject($deleteblaObject)
	{		
		self::_deleteObject(get_class($deleteblaObject), $deleteblaObject->getID());
		
		//Проходимся по коллекциям и удаляем из них
		foreach(self::$storage as $className=>$objectsArray)
		{
			if(!is_array($objectsArray) && !ereg('DC_', $className))
			{
				continue;
			}
			
			foreach($objectsArray as $objectID=>$object)
			{
				if(!($object instanceof DC_Base))
				{
					continue;
				}
				
				if(get_class($deleteblaObject) == $object->getStoredtypeName())
				{
					if($object->isMember($deleteblaObject))
					{
						$object->deleteElement($deleteblaObject);													
					}
				}					
			}
		}
	}
	
	/**
	 * Если еще небыл создан такой объект то создает его.
	 * Есои уже был сощздан то возвращаеть сыслку на него.
	 * 
	 * @param string $className
	 * @param string $id
	 * @return object
	 */
	static public function GetSingleton($className, $id, $data = '', $cacheType = '')
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		assert($cacheType == '' || $cacheType == 'GLOBAL_CACHE' || $cacheType == 'WORLD_CACHE');

		if((array_key_exists($className, self::$storage) && array_key_exists($id, self::$storage[$className])))
		{
			return self::$storage[$className][$id];	
		}else{			
			if(empty($cacheType))
			{
				$object = self::_createObject($className, $id, $data);
			}else{
				$cache = DS_Cache::Singleton();
				
				$mark = self::FormCacheMark($className, $id, $cacheType);

				$object = $cache->get($mark);

				if(!($object instanceof $className))
				{								
					$object = self::_createObject($className, $id, $data);
					$cache->set($mark, 10800, $object);			
				}
			}
			
			assert($object instanceof $className);
			
			self::_setObject($className, $id, $object);
			
			return $object; 
		}	
	}
	
	static public function ResetObjectCache($className, $id, $cacheType = '')
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		assert($cacheType == '' || $cacheType == 'GLOBAL_CACHE' || $cacheType == 'WORLD_CACHE');
					
		$mark = self::FormCacheMark($className, $id, $cacheType);
		
		DS_Cache::Singleton()->delete($mark);
	}
	
	static public function GetIFExists($className, $id)
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		
		if((array_key_exists($className, self::$storage) && array_key_exists($id, self::$storage[$className])))
		{
			return self::$storage[$className][$id];
		}else{
			return false;
		}		
	}
	
	static public function FormCacheMark($className, $id, $cacheType)
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		assert($cacheType == 'GLOBAL_CACHE' || $cacheType == 'WORLD_CACHE');
		
		switch ($cacheType)
		{
			case 'GLOBAL_CACHE':
				return constant("CACHING_PREFIX")."_".$className."(".$id.")";
			case 'WORLD_CACHE':				
				assert(key_exists('dbId', $_SESSION));								
				return constant("CACHING_PREFIX")."_".$_SESSION['dbId']."_".$className."(".$id.")";
		}
	}
	
	static private function _createObject ($className, $id, $data = '')
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		
		if(is_array($data))
		{	
			return new $className ($id, $data);
		}else{
			return new $className ($id);
		}
	}
	
	static private function _getObject($className, $id)
	{
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		
		//self::_initStorage($className, $id);							
		return self::$storage[$className][$id];
	}
	
	static public function GetObjectsIDForClass($className)
	{
		assert(!empty($className));
		self::_initStorage($className, 0);
		return array_keys(self::$storage[$className]); 
	}
		
	static private function _deleteObject($className, $id)
	{		
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		
		self::_initStorage($className, $id);
		
		if(!(array_key_exists($className, self::$storage) && array_key_exists($id, self::$storage[$className])))
		{
			return;
		}
		
		if(self::$storage[$className][$id]->getNeedAdditionalDeleteProcess())
		{
			self::$odjectsForAdditionalDeleteProcessing[$className][$id] = self::$storage[$className][$id];
		}				
		
		unset(self::$storage[$className][$id]);			
	}
	
	static private function _setObject($className, $id, $object)
	{		
		assert(!empty($className));
		assert(!empty($id) || $id === 0);
		assert(is_object($object));
		
		self::_initStorage($className, $id);
		
		if(!(array_key_exists($className, self::$storage) && array_key_exists($id, self::$storage[$className]))){
			self::$storage[$className][$id] = $object;
		}
	}
		
	static private function _haveObject($className, $id)
	{
		assert(!empty($className));
		//assert(!empty($id) || $id === 0);		
		
		return (array_key_exists($className, self::$storage) && array_key_exists($id, self::$storage[$className]));
	}			
	
	static private function _initStorage($className, $id=0)
	{
		assert(!empty($className));		
				
		if(!array_key_exists($className, self::$storage))
		{
			self::$storage[$className] = array();
		}
	}
	
	static public function ResetAll()
	{
		self::$storage = array();
	}
		
}//END OF CLASS

?>
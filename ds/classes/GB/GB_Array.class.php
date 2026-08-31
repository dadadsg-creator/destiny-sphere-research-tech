<?php $CVS_Version = '$Revision$ ; $Date$';
 
 /**
  * Класс типа массив. Может содержать в качестве элиментов только объекты
  */
 class GB_Array extends BS_Base
 {
 	
 	/**
	 * Массив в катором и храниться массив	 
 	 * @var array
 	 */
 	public $data = array();
 	
 	/**
 	 * Название типа элемента	 
 	 * @var string
 	 */
 	private $elementTypeName;
 	 	 	
 	/**
 	 * Название типа ключа	 
 	 * @var string
 	 */
 	private $keyTypeName;
 	
 	/**
 	 * возможные занчения типа ключа
 	 * @var array
 	 */
 	private static $allowedKeyTypeNames = array('integer', 'string'); 	 	
 	 	 	
 	/**
 	 * Конструтор класса
 	 *
 	 * @param string $keyTypeName - тип ключа. Может быть только строка или целое
 	 * @param string $elementTypeName - тип эелемента. Как простой тип так и класс.
 	 */
 	public function __construct($keyTypeName, $elementTypeName)
 	{
 		assert(!empty($elementTypeName));
 		assert(!empty($keyTypeName) && in_array($keyTypeName, self::$allowedKeyTypeNames));

 		parent::__construct();		
 		
 		$this->elementTypeName = $elementTypeName;
 		$this->keyTypeName = $keyTypeName;
 		
 		$this->_invariant('construct');
 	}
 	
 	/**
 	 * Возвращает элемент по ключу.
 	 *
 	 * @param unknown_type $key
 	 * @return unknown
 	 */
 	public function getElement($key)
 	{
 		//$this->_invariant();
 		$this->_validateKey($key);
 		assert(key_exists($key, $this->data)); 		
 		return $this->data[$key];
 	}
 	
 	/**
 	 * Добавление элемента к ммассиву
 	 *
 	 * @param unknown_type $key
 	 * @param unknown_type $element
 	 */
 	public function addElement($key, $element)
 	{ 		
 		//$this->_invariant();
 		$this->_validateKey($key);
 		$this->_validateElement($element); 		 		
		
 		if(is_null($key))
 		{
 			assert($this->keyTypeName == 'integer');
 			$this->data[] = $element;
 		}else{
 			$this->data[$key] = $element;
 		} 		 		
 	}
 	
 	/**
 	 * Возвращает колличество элементов массива
 	 *
 	 * @return integer
 	 */
 	public function countElements()
 	{
 		//$this->_invariant();
 		return (int)count($this->data);
 	}
 	
 	
 	/**
 	 * Ресетит массив
 	 *
 	 */
 	public function resetArray()
 	{
 		//$this->_invariant();
 		$this->data = array();	
 	}
 	
 	public function getArray()
 	{
 		//$this->_invariant();
 		return $this->data;
 	}
 	
 	/**
 	 * Проверяет есть ли такой элимент в массиве
 	 *
 	 * @param unknown_type $element
 	 * @return boolean
 	 */
 	public function haveElement($element)
 	{
 		//$this->_invariant();
 		$this->_validateElement($element);
 		return in_array($element, $this->data);
 	}
 	
 	public function haveKey($key)
 	{
 		//$this->_invariant();
 		$this->_validateKey($key);
 		return key_exists($key, $this->data);
 	}
 	
 	public function resetPointer()
 	{ 		
 		//$this->_invariant(); 	
 		reset($this->data); 
 	}
 	
 	public function getNextElement()
 	{
 		//$this->_invariant();
 		$out = current($this->data);
		next($this->data);
		return $out; 		
 	}
 	
 	public function fetchNextElement()
 	{
 		//$this->_invariant();
 		
 		$element = current($this->data); 		
 		unset($this->data[key($this->data)]);
 		 	
 		return $element;
 	}
 	
 	public function fetchNextKey()
 	{
 		//$this->_invariant();
 		
 		$key = key($this->data); 		
 		if(is_null($key))
 		{
 			next($this->data);
 			$key = key($this->data); 
 		}

 		unset($this->data[$key]);
 		
 		return $key;
 	}
 	
 	public function deleteCurentElement()
 	{
 		//$this->_invariant();

		$key = key($this->data);
		
		if(empty($key))
		{
			end($this->data);		
		}else{
			prev($this->data);
		}
		
		unset($this->data[key($this->data)]);
		next($this->data); 	
 	} 
 	
 	public function sortByKeys()
 	{
 		//$this->_invariant();	
 		ksort($this->data);
 	}	
 	
 	public function sortByVals()
 	{
 		//$this->_invariant();	
 		asort($this->data);
 	}	
 	
 	public function sortByValsInvers()
 	{
 		//$this->_invariant();	
 		rsort($this->data);
 	}	
 	
 	public function summVals()
 	{
 		//$this->_invariant();	
 		return array_sum($this->data);
 	}
 	
 	/**
 	 * Возвращает текущий элемент массива
 	 *
 	 * @return unknown
 	 */
 	public function getCurrentElement()
 	{
 		//$this->_invariant();
 		return current($this->data);
 	}
 	
 	/**
 	 * Возвращает текущий ключь массива
 	 *
 	 * @return unknown
 	 */
 	public function getCurrentKey()
 	{
 		//$this->_invariant(); 		
 		$key = key($this->data);
 		return $key;
 	}
 	
 	public function shuffl()
 	{
 		//$this->_invariant(); 
 		
 		srand((float)microtime() * 1000000);		
 		shuffle($this->data);
 	}
 	
 	/**
 	 * Устанваливаем указатель массива на позицию с днным ключем 
 	 *
 	 * @return unknown
 	 */
 	public function setPointerToKey($key)
 	{
 		//$this->_invariant();
 
 		if(empty($key))
 		{
 			return;
 		} 		 		
 		
 		reset($this->data);
		do{
			if($key == key($this->data))
			{
				break;
			}	
		}while(next($this->data)); 		 		
 	}
 	
 	private function _invariant($mode = 'public')
 	{
 		if(BS_Base::_exitInvariant($mode)) return;
 		
 		assert(is_array($this->data));
 		assert(is_string($this->elementTypeName) && !empty($this->elementTypeName)); 		
 		assert(is_array(self::$allowedKeyTypeNames) && (count(self::$allowedKeyTypeNames)>0));
 		assert(is_string($this->keyTypeName) && !empty($this->keyTypeName) && in_array($this->keyTypeName, self::$allowedKeyTypeNames));
 	}
 	
 	private function _validateKey($key)
 	{
 		assert(is_null($key) || (gettype($key) === $this->keyTypeName));
 	}
 	
 	private function _validateElement($element)
 	{
 		
 		if(gettype($element) == 'object')
 		{
 			assert($element instanceof $this->elementTypeName);
 		}else{

			if($this->elementTypeName == 'double')
			{
				$type = 'float';
			}else{
				$type = $this->elementTypeName;
			}
			
			if(gettype($element) == 'double')
 			{
 				$getType = 'float';
 			}else{
 				$getType = gettype($element);
 			}
 			
 			assert($type == $getType);
 		} 		
 	}
 	
 	public function __destruct()
 	{
 		parent::__destruct();
 	}
 	
 }//END OF CLASS
 
 ?>
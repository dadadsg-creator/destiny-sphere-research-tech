<?php $CVS_Version = '$Revision$ ; $Date$';

class ST_Base extends BS_Base
{

	protected $preData = array();
	
	protected $data = array();
	
	public function __construct($mark)
	{
		assert(!empty($mark));
		
		parent::__construct();
		
		$this->_getData($mark);
		
		$this->_parse();		
	}
		
	static protected function _formMark($className, $mark='')
	{
		assert(class_exists($className));
		
		//Времено закрыта работа метода так как есть подозрения что
		//использование функции debug_backtrace() сильно перегружает сервер
		//и вызывает ошики вызванные обрщением к несуществующей памяте.
		/*$trace = debug_backtrace();		
		return constant("CACHING_PREFIX")."_".$trace[1]['class']."(".$mark.")";*/
		
		return constant("CACHING_PREFIX")."_".$className."(".$mark.")";
	}	
	
	
	protected function _getData($mark)
	{
		assert(!empty($mark));

		/*$cache = DS_Cache::Singleton();
		
		$this->preData = @unserialize($cache->get($mark));

		if(empty($this->preData))
		{			
			$this->preData = $this->_getDataFromDB();
			$cache->set($mark, 10800, serialize($this->preData));			
		}	*/
		
		$this->preData = $this->_getDataFromDB();
		
		assert(is_array($this->preData));
		assert(count($this->preData)>0);
	}
		
	
	public function __destruct()
	{
		parent::__destruct();
	}
}




?>
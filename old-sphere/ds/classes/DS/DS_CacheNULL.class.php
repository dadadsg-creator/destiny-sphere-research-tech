<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для кеширования использующий мемкеш
 *
 */
class DS_CacheNULL extends BS_Base  implements DS_CacheInterface 
{	
	/**
	 * Атрибут для реализации синглетона
	 *
	 * @var DS_MemCache
	 */
	static private $instance;
		
	public function __construct()
	{
		parent::__construct();			
	}
	
	static public function Singleton()
	{
		if(!(self::$instance instanceof DS_CacheNULL))
		{
			self::$instance = new DS_CacheNULL();
		}
				
		return self::$instance;
	}
	
	
	public function set($mark, $lifeTime, $data)
	{
		settype($lifeTime, 'integer');
				
		assert(!empty($mark));
		assert($lifeTime > 0);				
	}
	
	public function get($mark)
	{
		assert(!empty($mark));
		
		return false;
	}
	
	public function delete($mark)
	{
		return;
	}
	
	public function __destruct()
	{
		parent::__destruct();				
	}	
}


?>
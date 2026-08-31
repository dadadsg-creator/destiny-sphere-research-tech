<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для кеширования использующий мемкеш
 *
 */
class DS_CacheMEM extends BS_Base  implements DS_CacheInterface 
{
    /**
     * ОБъект соединения с демоном кеша
     *
     * @var Memcache
     */
    private $memCache;  
    
    /**
     * Атрибут для реализации синглетона
     *
     * @var DS_MemCache
     */
    static private $instance;
    
    /**
     * Сколько попыток соединения делать при коннекте
     *
     * @var integer
     */
    static private $connectionCounts = 3;
    
    /**
     * Есть ли слединение
     *
     * @var integer
     */
    static private $connection = false;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->memCache = new Memcache;
        
        $connected = false;
        for($i=0; $i<self::$connectionCounts; $i++)
        {
            $connected = @$this->memCache->connect(constant('MEM_CACHE_HOST'), 11211);
            if($connected)
            {
                self::$connection = true;
                break;
            }
        }
        
        //assert($connected);
        if (!$connected) {
            SU_DebugTools::LogAll('MemCacheConectFailad', 'MamCache.log', 0);
        }
    }
    
    static public function Singleton()
    {
        if(!(self::$instance instanceof DS_CacheMEM))
        {
            self::$instance = new DS_CacheMEM();
        }
                
        return self::$instance;
    }
    
    
    public function set($mark, $lifeTime, $data)
    {
        if (self::$connection === false) {
            return;
        }
        
        settype($lifeTime, 'integer');
                
        assert(!empty($mark));
        assert($lifeTime > 0);
        
        //GP_SystemStatistic::DeltaParam('DB_CACHE', 'SET_COUNT', 1);       
        //SU_DebugTools::TimerStart('DS_CACHE_SET');    
        
        $data = serialize($data);
        
        if (strlen($data) > 950000)
        {
            //assert($this->memCache->set($mark, $data, MEMCACHE_COMPRESSED, $lifeTime));
            $this->memCache->set($mark, $data, MEMCACHE_COMPRESSED, $lifeTime);
        } else {
            //assert($this->memCache->set($mark, $data, 0, $lifeTime));
            $this->memCache->set($mark, $data, 0, $lifeTime);
        }
        
        //$timeDelta = SU_DebugTools::TimerStop('DS_CACHE_SET');        
        //GP_SystemStatistic::DeltaParam('DB_CACHE', 'SUMM_SET_TIME', $timeDelta);
    }
    
    public function get($mark)
    {
        if (self::$connection === false) {
            return;
        }
        
        assert(!empty($mark));
        
        //GP_SystemStatistic::DeltaParam('DB_CACHE', 'GET_COUNT', 1);
        //SU_DebugTools::TimerStart('DS_CACHE_GET');
        
        $res = $this->memCache->get($mark);
        
        //$timeDelta = SU_DebugTools::TimerStop('DS_CACHE_GET');
        //GP_SystemStatistic::DeltaParam('DB_CACHE', 'SUMM_GET_TIME', $timeDelta);
        
        /*if($res === false)
        {
            GP_SystemStatistic::DeltaParam('DB_CACHE', 'COUNT_MISSES', 1);
        }else{
            GP_SystemStatistic::DeltaParam('DB_CACHE', 'COUNT_MACHES', 1);
        }
        */
        
        return unserialize($res);
    }
    
    public function delete($mark)
    {
        if (self::$connection === false) {
            return;
        }
        
        assert(!empty($mark));
        
//      $this->memCache->delete($mark);
        $this->memCache->set($mark, '', 0, 0);
    }
    
    public function __destruct()
    {
        parent::__destruct();
    }   
}


?>
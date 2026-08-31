<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2007/11/19 14:32:39 $';

/**
 * Интерфейс дя кэширования одним из изх методов.
 * Сейчас поддердиваеться :
 *  DBA - кэширование с помощью современных версий DBM. ВРЕМЕННО ЗАПРЕЩЕННО
 *        так как у нас теперь используеться ресет меток кеша для некоторых объектов и 
 *        э то нельзя реализовтаь на системе где несоклько хттп серверов. 
 *  MEM - Сейчас основной способ кэширования.
 *  NULL - Это фактически отсуствие кэширования. Используеться при отладки илил при выходе из строя других
 *         систем.
 *
 */
class DS_Cache extends BS_Base implements DS_CacheInterface 
{   
    /**
     * Это для реализации синглетона
     *
     * @var DS_Cache
     */
    static private $instance;
    
    /**
     * Экземпляр текущей сиситемы кэширования.
     *
     * @var DS_CacheMEM
     */
    private $cacher;
    
    /**
     * Массив с метками котоыре необходимо рестратануть
     *
     * @var array
     */
    private $marksForDelete = array();
    
    public function __construct()
    {
        /*
        if(RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            $cacheSystemName = 'NULL';
        }else{
            $cacheSystemName = constant('CACHE_SYSTEM_NAME');
        }
        */
        $cacheSystemName = 'MEM';
        
        switch ($cacheSystemName)
        {
            case 'MEM':
                $this->cacher = DS_CacheMEM::Singleton();
                break;
            case 'DBA':
                ds_error('DBA cache system is depricated!');
                $this->cacher = DS_CacheDBA::Singleton();
                break;
            case 'NULL':
                $this->cacher = DS_CacheNULL::Singleton();
                break;
            default:
                ds_error('Unknown cache system name = '.constant('CACHE_SYSTEM_NAME'));
        }
        assert($this->cacher instanceof DS_CacheInterface);
    }
    
    /**
     * Метод реализации синглетона
     *
     * @return DS_Cache
     */
    static public function Singleton()
    {
        if(!(self::$instance instanceof DS_Cache))
        {
            self::$instance = new DS_Cache();
        }
                
        return self::$instance;
    }       
    
    /**
     * Немедленное удаление метки из кеша
     *
     * @param string $mark
     */
    public function delete($mark, $withAddMark = true)
    {
        assert(!empty($mark));
                
        $this->cacher->delete(constant("CACHING_PREFIX")."_".$mark);
        
        //Это защита. Так как когда мы посреди транзакции ресартим значение кеша, т.е. сведения в БД 
        //но трназакуция ещещ не закамичеена, о кто-то с другого соединения может занесит в кеш опять устаревшие данные.
        //поэтому надо еще сразу по завершению транзакции зарестартить кэши с помощью метода deleteAllMarksForDelete
        if($withAddMark)
        {
            $this->addMarkForDelete($mark);
        }   
    }
    
    /**
     * Добавление к списку еток на удаление новых меток.
     * по идеологии это должно вызываться сразу же после коммита,
     * но перед разлочиванием объектов.
     *
     * @param anytipe $mark
     */
    public function addMarkForDelete($mark)
    {
        assert(!empty($mark));
        
        $this->marksForDelete[$mark] = 1;
    }
    
    /**
     * Удаление всем меток что на очереди.
     *
     */
    public function deleteAllMarksForDelete()
    {
        assert(is_array($this->marksForDelete));
        
        foreach($this->marksForDelete as $mark=>$v)
        {
            assert(!empty($mark));
            
            $this->delete($mark, false);
            unset($this->marksForDelete[$mark]);
        }
    }
    
    public function get($mark)
    {
        assert(!empty($mark));
        
        return $this->cacher->get(constant("CACHING_PREFIX")."_".$mark);
    }
    
    public function set($mark, $lifeTime, $data)
    {
        assert(!empty($lifeTime));
        assert(!empty($mark));
                
        $this->cacher->set(constant("CACHING_PREFIX")."_".$mark, $lifeTime, $data);
    }
    
    function __destruct()
    {
        parent::__destruct();   
        $this->deleteAllMarksForDelete();
    }
    
    
}

?>
<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Базовый класс для посторения всех коллекций
 */
abstract class DC_Base extends DE_Base implements DC_CollectionInterface
{   
    /** Массив в котором хранитьяс коллекция */
    public $collection;     
    
    /** Имя класса ссылки на который храним */
    protected $storedClassName;
    
    /** Режимы работы коллекции по ID или по ссылкам на объекты */
    protected $mode = 'LINK';
    
    protected $allowedModes = array('ID', 'LINK');
    
    function __construct($id, $className)
    {
        assert(!empty($className) && is_string($className));
        
        parent::__construct($id);

        $this->storedClassName = $className;
        
        if($this->mode == 'ID')
        {
            $this->collection = new GB_Array('string', 'string');       
        }else{
            $this->collection = new GB_Array('string', $this->storedClassName);     
        }
        
    }   

    /** ДОабвление объекта к коллекции */
    protected function _addNewElement($newMember)
    {       
        if($this->mode == 'ID')
        {           
            assert(is_string($newMember));
            $this->collection->addElement((string)$newMember, $newMember);
        }else{
            assert($newMember instanceof DE_Base); 
            $this->collection->addElement((string)$newMember->getID(), $newMember);
        }
        
    }
    
    /** Удаление обекта из коллекции */
    protected function _getElementByID($id)
    {
        assert(!empty($id));

        $withInt = (int)$id;
                
        if($id === (string)$withInt)
        {
            settype($id, 'integer');
        }else{
            settype($id, 'string');
        }
        
        return GB_SingletonObjectStorage::GetSingleton($this->storedClassName, $id);
    }
    
    /**
     * Ресет указателя.
     *
     * @return unknown
     */
    public function resetNext()
    {       
        $this->_invariant();
        $this->collection->resetPointer();
    }       
    
    public function getArray()
    {
        $this->_invariant();
        
        return $this->collection->getArray();
    }
    
    /**
     * Возвращает колличество элементов в коллекции
     *
     * @return unknown
     */
    public function countCollection()
    {
        $this->_invariant();
        return $this->collection->countElements();
    }
    
    public function shuffle()
    {
        $this->_invariant();
        $this->collection->shuffle();
        
    }
    
    /**
     * Переход на следующей элемент.
     *
     * @return unknown
     */
    public function next()
    {
        $this->_invariant();
        
        if($this->mode == 'ID')
        {
            $id = $this->collection->getNextElement();          
            
            if($id)
            {
                return $this->_getelementByID($id); 
            }else{
                return false;
            }                               
        }else{
            return $this->collection->getNextElement();
        }       
    }
    
    public function getCurrentPosition()
    {
        return $this->collection->getCurrentKey();
    }
    
    public function setPointerToPosition($key)
    {
        return $this->collection->setPointerToKey($key);
    }
    
    public function commit()
    {
        $this->_invariant();
        parent::commit();
        $this->isModifyed = false;
    }
    
    public function getStoredtypeName()
    {
        $this->_invariant();
        return $this->storedClassName;
    }
    
    public function deleteElement($elementForDelete)
    {
                
        $simpltypes = array('integer', 'array', 'float', 'double', 'boolean');  
        
        if(in_array($this->storedClassName, $simpltypes))   
        {
            return;
        }
        
        if(!($elementForDelete instanceof $this->storedClassName))
        {
            return; 
        }
        
        $this->collection->resetPointer();
        while($element = $this->collection->getNextElement())
        {
            if($this->mode == 'LINK')
            {
                $compare = $element->getID();
            }else{
                $compare = $element;
            }

            if($compare == $elementForDelete->getID())
            {                       
                $this->collection->deleteCurentElement();
            }
        }

    }
    
    /**
     * Обнуление коллеекции
     *
     * @return unknown
     */
    public function resetCollection()
    {
        $this->collection->resetArray();
    }
    
    protected function _collectionCondition($element)
    {
        return false;
    }
    
    
    /**
     * Проверяет есть ли такой элимент в  окллекции
     *
     * @param Класс проверяемы на членство
     * @return bool
     */
    public function isMember($member)
    {
        $this->_invariant();        
        
        assert(!is_null($member) && ($member instanceof $this->storedClassName));
        

        $out = false;
        
        if($this->mode == 'LINK')
        {
            if($this->collection->haveKey((string)$member->getID()))
            {
                $out = true;
            }
        }else{
            if($this->collection->haveElement((string)$member->getID()))
            {
                $out = true;
            }
        }
        
        return $out;
    }   
    
    public function refreshElement($element)
    {
        if($this->_collectionCondition($element))
        {
            if(!$this->isMember($element))
            {
                $this->_addNewElement($element);
            }
        }else{
            if($element instanceof $this->storedClassName)
            {
                if($this->isMember($element))
                {
                    $this->deleteElement($element);
                }
            }
        }
    }
    
    protected function _invariant($mode='public')
    {   
        if(BS_Base::_exitInvariant($mode)) return;
        
        parent::_invariant($mode);
        
        assert($this->collection instanceof GB_Array);              
        assert(!empty($this->mode) && in_array($this->mode, $this->allowedModes));
        assert(is_string($this->storedClassName) && !empty($this->storedClassName));
    }
    
    public function __destruct()
    {
        parent::__destruct();
    }
}


?>
<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Пустая(несуществующая) сота
 *
 */
class DE_CombNull extends DE_Base implements DE_CombInterface
{

    private $address;

    function __construct($address = '')
    {
        parent::__construct('1000');
        assert(is_string($address));
        $this->address = $address;
    }

    public function getID()
    {
        $this->_invariant();
        return (int)0;
    }

    public function isNull()
    {
        $this->_invariant();
        return true;
    }

    public function getName()
    {
        $this->_invariant();
        return '';

    }
    
    public function getFullName()
    {
        $this->_invariant();
        return $this->address;
    }
    
    public function getFormatedAddress()
    {
        $this->_invariant();
        return $this->address;
    }

    public function GetWarArtefactsByBuildType()
    {
        ds_error('BLIN');
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

        if($entity instanceof DE_CombNull)
        {
            return true;
        }

        assert($entity instanceof DE_Comb);

        if($entity->getID() === $this->getID())
        {
            return true;
        }else{
            return false;
        }
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;

        parent::_invariant($mode);
        assert(is_string($this->address));
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function getUserInstance()
    {
        return null;
    }
}

?>
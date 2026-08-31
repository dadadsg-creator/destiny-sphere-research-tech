<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Сущность текущего веса построенной и потеряной
 */

class DE_ArmyWeight extends DE_Base
{
    /**
     * Юзверь, которому принадлежит вес армий
     *
     * @var int
     */
    private $userInstanceID;

    /**
     * Общий вес построенных армий
     *
     * @var flost
     */
    private $armyWeightBuilt;

    /**
     * Общий вес потерянных армий
     *
     * @var flost
     */
    private $armyWeightLost;

    function __construct($id)
    {
        assert(is_integer($id) && $id > 0);

        parent::__construct($id);

        $data = DS_ArmyWeight::getByUserInstanceId($id);

        $this->userInstanceID = $id;

        // Если в базе еще нет ни одной записи у этого игрока
        if(!(is_array($data) && count($data) > 0))
        {
            // Вставляем запись в базу
            DS_ArmyWeight::InsertArmyWeight($id, 0.0, 0.0);

            $this->armyWeightBuilt = 0.0;
            $this->armyWeightLost = 0.0;
        } else {
            assert(key_exists('army_weight_built', $data));
            $this->armyWeightBuilt = (float)$data['army_weight_built'];

            assert(key_exists('army_weight_lost', $data));
            $this->armyWeightLost = (float)$data['army_weight_lost'];
        }

        $this->_invariant('construct');
    }

    /**
     * Создание на основание UserInstanceID
     *
     * @param int $id
     * @return DE_ArmyWeight
     */
    static public function CreateFromUserInstanceID($id)
    {
        assert((int)$id > 0);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, (int)$id);
    }

    /**
     * Возвращает экземпляр текущего значение веса построенной армии
     *
     * @return float
     */
    public function getArmyWeightBuilt()
    {
        return $this->armyWeightBuilt;
    }

    /**
     * Возвращает экземпляр текущего значение веса потерянной армии
     *
     * @return float
     */
    public function getArmyWeightLost()
    {
        return $this->armyWeightLost;
    }

    /**
     * Изменяем значение веса построенной армии
     *
     * @param float $delta
     */
    public function deltaArmyWeightBuilt($delta)
    {
        assert(is_float($delta));

        $this->armyWeightBuilt += $delta;
        
        // Проверяем, чтобы новое значение было не меньше 0
        if(!($this->armyWeightBuilt >= 0))
        {
            ds_error('Try to set negative army weight build');  
        }
        
        $this->isModifyed = true;
    }
    
    /**
     * Изменяем значение веса потерянной армии
     *
     * @param float $delta
     */
    public function deltaArmyWeightLost($delta)
    {
        assert(is_float($delta));
        $this->armyWeightLost += $delta;
        if ($this->armyWeightLost < 0) $this->armyWeightLost = 0;
        $this->isModifyed = true;
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;

        parent::_invariant($mode);

        assert(is_float($this->armyWeightBuilt) && $this->armyWeightBuilt >= 0);
        assert(is_float($this->armyWeightLost) && $this->armyWeightLost >= 0);
        assert(is_int($this->userInstanceID) && $this->userInstanceID>0);
    }

    public function commit()
    {
        parent::commit();

        if($this->isModifyed == true)
        {
            if(DS_ArmyWeight::UpdateArmyWeight($this))
            {
                $this->isModifyed = false;
            }
        }
    }
}


?>
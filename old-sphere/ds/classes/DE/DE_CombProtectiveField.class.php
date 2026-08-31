<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Защитное поле захватываемой колонии
 *
 */
class DE_CombProtectiveField extends DE_Base
{
    private $tempoparyDefend;

    private $protectiveField;

    function __construct($id)
    {
        assert(is_int($id) && $id>0);
        parent::__construct($id);

        $data = DS_Comb::GetProtectiveField($id);

        if ($data) {
            $this->protectiveField = $data['protected_field'];
            assert($this->protectiveField >= 0 || $this->protectiveField <= 100);
        }
        else $this->protectiveField = 0;
        
        // Смотрим, активно ли здание генератора на соте
        $atb = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($id, 91);
        if (count($atb) > 0 && $atb[0]['active'] == 'f') {
            $this->protectiveField = 0;
        }

        if ($data) $temporaryDefendEnd = $data['end_temporary_defend'];
        else $temporaryDefendEnd = 1;
        if(!$temporaryDefendEnd || is_null($temporaryDefendEnd))
        {
            $temporaryDefendEnd = 1;
        }

        $this->tempoparyDefend = GB_TypeFactory::CreateConstant('GB_Date', $temporaryDefendEnd);
    }//END OF METHOD

    static public function CreateFromCombID($id)
    {
        assert(is_int($id) && $id>0);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }

    public function deltaProtectiveField($value)
    {
        assert(is_int($value));
        
        // Если здание не активно, то поле не уменьшаем
        $atb = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($this->id, 91);
        if (count($atb) > 0 && $atb[0]['active'] == 'f') {
            return;
        }

        $this->protectiveField = $this->protectiveField + $value;
        if ($this->protectiveField < 0) {
            $this->protectiveField = 0;
        }
        
        assert($this->protectiveField >= 0 && $this->protectiveField <= 100);

        $this->isModifyed = true;
    }
    
    public function setProtectiveField($value)
    {
        assert(is_int($value));
        assert($value >= 0 && $value <= 100);
        
        $this->protectiveField = $value;
        $this->isModifyed = true;
    }

    protected function _invariant($mode = 'public')
    {
        
    }

    public function commit()
    {
        $this->_invariant();
        parent::commit();

        if($this->isModifyed == true)
        {
            if(DS_Comb::SetProtectiveField($this->id, $this->protectiveField, $this->tempoparyDefend))
            {
                $this->isModifyed = false;
            }
        }
    }
    
    public function getTemporaryDefend()
    {
        return $this->tempoparyDefend;
    }
    
    public function setTemporaryDefend(GB_Date $endDate)
    {
        $this->tempoparyDefend = $endDate;
                
        // Изменяем сразу, что бы не перехватили соту в этом же рефреше
        DS_Comb::SetProtectiveField($this->id, $this->protectiveField, $this->tempoparyDefend);
    }

    public function getProtectiveField()
    {
        return $this->protectiveField;
    }
}


?>
<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Класс для опсиания сущности СОТА
 */
class DE_Comb extends DE_Base implements DE_CombInterface
{

    /** Имя соты */
    private $name;

    /** Номер соты в комлексе */
    private $number;

    /** Номер инстанса в базе */
    private $userInstanceID = 0;

    /** Номер кольца */
    private $ringNumber;

    /** Номер комплекса */
    private $complexNumber;

    /** ID комплекса */
    private $complexID;

    /**
     * Захватываемая колония
     *
     * @var boolean
     */
    private $isCapture = false;

    /**
     * Рейтинг соты
     *
     * @var int
     */
    private $combScore; 
    
    /**
     * Изменено ли имя соты
     * вынесено отдельно, так как выяснилось, что в мирах много сот, отличающихся только регистром
     *
     * @var int
     */
    private $isModifyedCName = false;

    /**
    * @param $id - ID соты
    * @desc Внести описание здесь...
    */
    function __construct($id)
    {
        assert(is_int($id) && $id>0);
        parent::__construct($id);

        $data = DS_Comb::GetByID($id);
        if (!is_array($data) || !count($data)) return NULL;

        assert(key_exists('id_comb', $data));

        assert(key_exists( 'combname', $data));
        $this->name = (string)$data['combname'];

        assert(key_exists('combnumber', $data));
        $this->number = (int)$data['combnumber'];

        assert(key_exists('complexnumber', $data));
        $this->complexNumber = (int)$data['complexnumber'];

        assert(key_exists('id_combcomplex', $data));
        $this->complexID = (int)$data['id_combcomplex'];

        assert(key_exists('ringnumber', $data));
        $this->ringNumber = (int)$data['ringnumber'];

        assert(key_exists( 'id_userinstance', $data));
        $this->userInstanceID = (int)$data['id_userinstance'];

        assert(key_exists( 'combscore', $data));
        $this->combScore = (float)$data['combscore'];

        // Если колония захватываемая
        assert(key_exists( 'is_capture', $data));
        if ($data['is_capture'] == 't') {
            $this->isCapture = true;
        }

        $this->_invariant('construct');
    }//END OF METHOD

    /**
     * Enter description here...
     *
     * @param unknown_type $worldID
     * @param unknown_type $ringNumber
     * @param unknown_type $complexNumber
     * @param unknown_type $combNumber
     * @return DE_Comb
     */
    static public function CreateFromCoordinates($worldID, $ringNumber, $complexNumber, $combNumber)
    {

        assert(is_int($worldID) &&  $worldID>0);
        assert(is_int($ringNumber) && $ringNumber>0);
        assert(is_int($complexNumber) && $complexNumber>0);
        assert(is_int($combNumber) && $combNumber>0);

        $id = (int)DS_Comb::GetIDByCordinates($worldID, $ringNumber, $complexNumber, $combNumber);

        if(!$id)
        {
            return new DE_CombNull($ringNumber.".".$complexNumber.".".$combNumber);
        }else{
            return DE_Comb::CreateFromID($id);
        }
    }

    public function GetWarArtefactsByBuildType()
    {


        $id = (int)DS_Comb::GetWarBaseByCombID($this->getID());

        assert(is_int($id) && count($id)>0);
        if ($id)
        {
            return $id;
        }
    }

    /**
     * Enter description here...
     *
     * @param integer $id
     * @return DE_Comb
     */
    static public function CreateFromID($id)
    {
        assert(is_int($id));
        if($id>0)
        {
            return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
        }else{
            return new DE_CombNull("Unknown");
        }

    }

    public function GetHomeArmy()
    {


        return DE_Army::CreateHomeArmyByCombID($this->getID());
    }

    public function GetCombArmysCollection()
    {

        return DC_CombArmys::CreateFromCombID($this->getID());
    }

    /**
     * Enter description here...
     *
     * @return DC_ArmysStayOnCombDefend
     */
    public function GetArmysOnCombDefendCollection()
    {

        return DC_ArmysStayOnCombDefend::CreateFromCombID($this->getID());
    }

    public function getComplexNumber()
    {

        return $this->complexNumber;
    }//END OF METHOD

    public function getComplexID()
    {

        return $this->complexID;
    }//END OF METHOD

    public function getRingNumber()
    {

        return $this->ringNumber;
    }//END OF METHOD

    /**
     * Enter description here...
     *
     * @return DE_Ring
     */
    public function getCombRing()
    {

        return DE_Ring::CreateFromNumber($this->ringNumber);
    }

    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Enter description here...
     *
     * @return DE_UserInstance
     */
    public function getUserInstance()
    {
        return DE_UserInstance::CreateFromID($this->getUserInstanceID());
    }

    public function getUserInstanceID()
    {

        return $this->userInstanceID;
    }

    public function setUserInstanceID($UIID)
    {
        settype($UIID, 'integer');
        assert($UIID >= 0);

        $this->userInstanceID = $UIID;
        
        // Комитим сразу, что бы не было накладок с количеством захваченных колоний
        DS_Comb::UpdateComb($this);
    }

    public function setName($combName)
    {
        assert(VD_CombName::Validate($combName, true));

        $this->name = $combName;

        $this->isModifyedCName = true;
    }

    public function getCombID()
    {
        return $this->id;
    }

    public function getFormatedAddress()
    {
        return $this->ringNumber.'.'.$this->complexNumber.'.'.$this->number;
    }

    public function getName()
    {
        if(strlen($this->name) == 0)
        {
            return '';
        }else{
            return $this->name;
        }
    }

    public function getIsCapture()
    {
        return $this->isCapture;
    }

    public function getFullName()
    {
        return $this->getName()." (".$this->getFormatedAddress().")";
    }
    
    
    public function getCombScore()
    {
        return $this->combScore;
    }

    /**
     * Enter description here...
     *
     * @return DE_CombAttributes
     */
    public function getAttributes()
    {

        return DE_CombAttributes::CreateFromCombID($this->getID());
    }
    
    public function haveOpenCentralGate()
    {
        return DS_Comb::HaveOpenCentralGate($this->getID());
    }

    public function haveOpenRingGate()
    {
        return DS_Comb::HaveOpenRingGate($this->getID());
    }

    public function haveGandolaTransporter()
    {
        return DS_Comb::HaveGandolaTransporter($this->id);
    }

    public function deltaCombScore($delta)
    {
        $this->combScore += $delta;
        $this->isModifyed = true;
    }

    public function isNull()
    {
        return false;
    }

    public function delete()
    {
        GB_SingletonObjectStorage::DeleteObject($this);
    }

    protected function _invariant($mode = 'public')
    {
        /*
        assert(is_string($this->name));
        assert(is_int($this->number) && $this->number>0);
        assert(is_int($this->userInstanceID));
        assert(is_int($this->ringNumber) && $this->ringNumber>0);
        assert(is_int($this->complexNumber) && $this->complexNumber>0);
        assert(is_int($this->complexID) && $this->complexID>0);
        assert(is_float($this->combScore));
        */
    }

    public function commit()
    {
        parent::commit();

        if($this->isModifyed == true)
        {
            if(DS_Comb::UpdateComb($this))
            {
                $this->isModifyed = false;
            }
        }
        
        if($this->isModifyedCName == true)
        {
            if(DS_Comb::UpdateCombName($this))
            {
                $this->isModifyedCName = false;
            }
        }
    }
}//END OF CLASS


?>
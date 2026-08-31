<?php $CVS_Version = '$Revision$ ; $Date$';

// команда выйти из альянса
class UA_LeaveCaprureColony extends UA_Action
{
    public $colonyId;

    public function __construct()
    {
        parent::__construct();
    }

    // проверяем параметры
    function checkLocalParams()
    {
        $this->colonyId = (int)$this->root->get_attribute ( "id" );
        assert($this->colonyId > 0);

        return true;
    }

    // проверяем допустимость действия
    function checkAction()
    {
        return true;
    }

    // выполнить дейтвие
    function performAction()
    {
        $colony = DE_Comb::CreateFromID($this->colonyId);

        // Смотрим процент оставшегося поля на колонии
        $protectedField = DE_CombProtectiveField::CreateFromCombID((int)$this->colonyId)->getProtectiveField();
        // Минимальное значение защитного поля, при котором можно отказаться от колонии
        $minField = (int)DE_WorldSettings::CreateForCurentWorld()->get('MIN_PROTECTIVE_FIELD_TO_LEAVE_COLONY');
        if ($protectedField < $minField) {
            // Вы не можете покинуть колонию, так как ваше защитное поле меньше %
            $messparams[0] = $minField;
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANT_LEAVE_COLONY", $messparams);
            return true;
        } 
        /*
        if (VERSION_NAME=="LIMITED") {
            $sql = "select id_battle from ds_battle
            where id_battlecomb = ".$this->colonyId." and battleenddate is null";
            $res = DS_Common::GetResultArray("", $sql);
            if (count($res)>0) return true;
        }
        */
        // Покидаем колонию
        ME_CaprureColony::Leave($colony);

        return true;
    }
}

?>
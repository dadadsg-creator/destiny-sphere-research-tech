<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Сущность армии
 */

class ME_ArmyOccurrence extends DE_Base
{
    //private $armyOccurrences = array();
    //private $deletedOccurrences = array();
    
    public function CreateOccurrence(DE_army $army, $occurrence, $ccurrenceAction)
    {
        assert(!empty($occurrence));
        assert(!empty($ccurrenceAction));
        
        // Смотрим, нет ли у этой армии уже этого действия на это событие
        if (count(DS_ArmyOccurrence::GetArmysOccurrenceByOccurrenceAndAction($army, $occurrence, $ccurrenceAction)) == 0) {
            // Создаем событие для армии 
            DS_ArmyOccurrence::CreateOccurrence($army, $occurrence, $ccurrenceAction);  
        } 
    }
    
    public static function HendlerOccurrence(DE_army $army, $occurrence)
    {
        assert(!empty($occurrence));
        
        $occurrences = DS_ArmyOccurrence::GetArmysOccurrencesAction($army->getID(), $occurrence);
        
        // Если есть события, то отправляем их на обработку
        for ( $i=0; $i<sizeof ( $occurrences ); $i++ )
        {
            $a = new $occurrences[$i]['occurrenceaction_handler'];
            $a->Exec($army);

            // Удаляем это событие из базы
            $deletedOccurrences[] = $occurrences[$i]['id_unitcontaineroccurrence'];
        }
    }
    
    /*private function _getArmysOccurrencesAction($idArmy, $ccurrence)
    {
        assert(is_int($idArmy) && $idArmy > 0);
        assert(!empty($ccurrence));
        
        if (!key_exists($idArmy, $this->armyOccurrences)) {
            $this->armyOccurrences[$idArmy] = DS_ArmyOccurrence::GetArmysOccurrencesAction($idArmy, $ccurrence);
        }
        
        return $this->armyOccurrences[$idArmy];
    }*/

    public function commit()
    {
        parent::commit();
                
        for ( $i=0; $i<sizeof ( $this->deletedOccurrences ); $i++ )
        {
            DS_ArmyOccurrence::DeleteOccurrence($this->deletedOccurrences[$i]['id_unitcontaineroccurrence']);
        }                   
    }
}


?>
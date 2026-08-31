<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер добавляет или снимает ресурсы с соты
 *
 */
class ME_DeltaResourcesOnComb
{
    /**
     * ОСновнйо метод собственно иреализующий весь функционал
     *
     * @param string $categoryName
     * @param DE_Comb $combTo
     * @param integer $articleID
     * @param float $amount
     * @param GB_Date $addDate
     */
    
    static public function Exec($categoryName, DE_Comb $combTo, $articleID, $amount, GB_Date $addDate)
    {       
        assert(!empty($categoryName));
        assert(is_int($articleID) && $articleID > 0);
        assert(is_float($amount));
        
        switch ($categoryName)
        {
            case 'RESOURCE':
                self::_deltaResourse($combTo, $articleID, $amount);         
                break;
            case 'ARTEFACT':
                self::_deltaArtefact($combTo, $articleID, $amount);
                break;
            case 'UNIT':
                self::_deltaUnits($combTo, $articleID, $amount, $addDate);
                break;
            default:
                ds_error("Unknown category name = ".$categoryName);
        }
    }
    
    static private function _deltaResourse(DE_Comb $combTo, $articleID, $amount)
    {
        assert(is_int($articleID) && $articleID > 0);
        assert(is_float($amount));
        
        $attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID($articleID);
        $combTo->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $attributeName, $amount);
        
    }
    
    static private function _deltaArtefact(DE_Comb $combTo, $articleID, $amount)
    {
        assert(is_int($articleID) && $articleID > 0);
        assert($amount == 1 || $amount == -1);
        
        if($amount > 0)
        {
            DS_Artefact::AddArtefactToCombStore($combTo, $articleID);
        }else{
            DS_Artefact::DeleteArtefactFromCombStore($combTo, $articleID);
        }       
    }
    
    static private function _deltaUnits(DE_Comb $combTo, $articleID, $amount, GB_Date $addDate)
    {
        assert(is_int($articleID) && $articleID > 0);
        assert(is_float($amount) && $amount != 0);
                
        $unit = DE_Unit::CreateFromID($articleID);
        
        if($amount > 0)
        {
            ME_AddUnitsToHomeArmy::AddUnit($combTo, $unit, $amount, $addDate);
        }else{
            ME_AddUnitsToHomeArmy::GetUnit($combTo, $unit, -$amount);
        }
        
        // Если мы добавляли юнитов
        if ($amount > 0) {
            // Проверяем смену лиги
            self::_checkLevelLeague($combTo->getUserInstanceID());
        }
    }
    
    private static function _checkLevelLeague($uiid)
    {
        // Если лиги выключены, ничего не делаем
        if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') != 1) {
            return;
        }
        
        // Текущая лига
        $CurLeague = DE_UserInstance::CreateFromID($uiid)->getLeagueId(); 
        
        // Если лига равна максимальной, то нет смысла что-то считать
        if ($CurLeague == DS_League::GetMaxLeague()) {
            return;
        } 
        
        $userInstanceArmys = DC_UserInstanceArmys::CreateFromUserInstanceID($uiid);

        $totalArmysPower = 0.0; 

        $userInstanceArmys->resetNext();
        while(false !== ($army = $userInstanceArmys->next()))
        {
            $totalArmysPower += $army->getArmyPower();
        }

        // Лига, соответствующая по суммарному весу армий
        $league = DS_League::GetLeagueArmyes($totalArmysPower/10);
        
        // Если есть соответствующая времени жизни лига и она больше от текущей
        if ($league && ($league['id_league'] > $CurLeague)) {
                //assert($league['id_league'] > $CurLeague);        
                // Меняем лигу
                DE_UserInstance::CreateFromID($uiid)->setLeagueId((int)$league['id_league']);

                // Сообщение
                $messparams = array($culLevel, $league['league_name']);
                $date = GB_TypeFactory::CreateConstant('GB_Date', time());
                // Командор, стало известно, что Вы обладаете слишком большим количеством мощных армии, дислоцирующихся на Ваших сотах. Принято решение перевести Вас в Лигу %, которой соответствуют такие масштабы! Поздравляем!
                DE_SystemMessages::AddMessageFromMessageCompiler($date, (int)$uiid, 'NEXT_LEAGUE_ARMIES', $messparams, 0, 'BLUE');
                //SU_DebugTools::LogAll('$uiid = '.$uiid, 'Annita.log');

                // Сообщение в зеленку
                $messageScopeID = DS_Message::GetMessageScopeIDByName('PRIVATE');
                assert($messageScopeID > 0);
                $senderName = 'DestinySphere';
                $subject = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE', array(), null, null, false);
                $recieverName = DE_User::CreateFromID(DE_UserInstance::CreateFromID((int)$uiid)->getUserID())->getName();
                $fromUIID = 0;

                $message = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE_ARMIES', $messparams, null, null, false);
                $messageID = DS_Message::CreateMessage($message, $subject, $senderName, $recieverName, $messageScopeID, $fromUIID);

                DS_Message::AddMessageToUser($messageID, (int)$uiid);
        }
    }
}

?>
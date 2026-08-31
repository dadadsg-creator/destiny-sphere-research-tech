<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 *  ласс возвращает доступные дл€ торговли скопы
 * смысл его по€влени€ в том, чтобы можно было реализовать работу с аль€нсовым скопом двум€ способами
 * 1. ¬ аль€нсе можно торговать только если есть межкольцевой конектор
 * 2. ¬ аль€нсе можно торговать из любого торговател€
 * 
 */
class GL_CombAllowedTradeScopes
{
    /**
     * —копы доступные дл€ данной соты
     *
     * @param DE_Comb $comb
     * @return array
     */
    static public function GetAllScopes(DE_Comb $comb)
    {
        $tradeScopes = array();
        $tradeScopes[] = array('id_tradescope'=>1, 'tradescopename'=>'COMB_COMPLEX_TRADING');
        
        $openGate = $comb->haveOpenCentralGate();
        $allianceTradingOnlyFromInterring = (bool)DE_WorldSettings::CreateForCurentWorld()->get('TRADING_ALLIANCE_ONLY_FROM_INTERRING_CONNECTOR');
        $haveRingGate = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($comb->getID(), 25);
        $haveIRT = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($comb->getID(), 20);
        $haveRT = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($comb->getID(), 31);
        $newbDefence = $comb->getUserInstance()->haveNewbDefend();
        
        if (($newbDefence || $openGate) && ($haveIRT || $haveRT) && VERSION_NAME!="LIMITED")
            $tradeScopes[] = array('id_tradescope'=>2, 'tradescopename'=>'RING_TRADING');

        $interring =  $newbDefence && $haveIRT && VERSION_NAME!="LIMITED" || $openGate && $haveRingGate && $haveIRT;
        if ($interring)
            $tradeScopes[] = array('id_tradescope'=>3, 'tradescopename'=>'INTERRING_TRADING');
    
        // ≈сли игрок состоит в аль€нсе и у него открыты ворота, то у него должен быть аль€нсовый скоп
        if (($interring || !$allianceTradingOnlyFromInterring) && $openGate && $comb->getUserInstance()->getAlliance()->getName() != 'EMPTY ALLIANCE') {
            $tradeScopes[] = array('id_tradescope'=>4, 'tradescopename'=>'ALLIANCE_TRADING');
        }
       
        return $tradeScopes;
    }
}

?>
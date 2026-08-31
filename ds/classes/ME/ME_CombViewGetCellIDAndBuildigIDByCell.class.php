<?php
/**
 *  ласс по номеру €чейки извлекает ID €чейки и ID здани€ на €чейке 
 *
 */
class ME_CombViewGetCellIDAndBuildigIDByCell
{
    /**
     * Ќомер €чейки с которой работаем
     *
     * @var int
     */
    static private $cellNumber;
    
    /**
     * “ип части соты с которым работаем
     *
     * @var int
     */
    static private $cellTypeID;
    
    /**
     * —ота с которой работаем
     *
     * @var DE_Comb
     */
    static private $comb;
    
    /**
     * —обственно основной мтеод
     *
     * @param int $cellNumber Ќомер €чейки
     * @param bool $sendCell ѕосылать ли ID €чейки
     * @param bool $sendBuilding ѕосылать ли ID здани€ на €чейке  
     * @param int $cellTypeID “им части соты
     * @param DE_Comb $comb —ќта ск оторой работаем
     */
    public static function Exec($cellNumber, $sendBuilding, $cellTypeID = null, $comb = null)
    {
        assert(is_int($cellNumber) && $cellNumber > 0);
        assert(is_null($cellTypeID) || (is_int($cellTypeID) && $cellTypeID > 0));
        assert(is_null($comb) || ($comb instanceof DE_Comb));
        assert(is_bool($sendBuilding));
        
        self::$cellNumber = $cellNumber;
        
        if(is_null($cellTypeID))
        {
            assert(key_exists('celltype', $_SESSION));
            self::$cellTypeID = (int)$_SESSION['celltype'];
            assert(self::$cellTypeID > 0);
        }else{
            self::$cellTypeID = $cellTypeID;
        }
        
        if(is_null($comb))
        {
            assert(key_exists('comb', $_SESSION));
            self::$comb = DE_Comb::CreateFromID((int)$_SESSION['comb']);
        }else{
            self::$comb = $comb;
        }
        
        //ƒл€ центральнйо части соты так как координана€ сетка совсем отличаетьс€ между клиентом и сервером.
        if(self::$cellTypeID == 2)
        {
            $mask = GB_CombCentralViewMask::GetClientBaseMask();
            self::$cellNumber = (int)$mask[self::$cellNumber]['SERVER_NUMBER'];
            assert(self::$cellNumber > 0);
        }
        
        
        $out = array(
            'CELL_ID' => null,
            'GROUND_TYPE_ID' => null,
            'ATTACHED_BUILDING_ID' => null,
            'BUILDING_ID' => null
        );
        
        $combCells = DE_CombCells::CreateFromCombIDAndCellTypeID(self::$comb->getID(), self::$cellTypeID);
        
        assert(key_exists(self::$cellNumber, $combCells->cells));
        
        $out['CELL_ID'] = (int)$combCells->cells[self::$cellNumber]['CELL_ID'];
        assert($out['CELL_ID'] > 0);
        
        $out['GROUND_TYPE_ID'] = (int)$combCells->cells[self::$cellNumber]['GROUND_TYPE_ID'];
        assert($out['GROUND_TYPE_ID'] > 0);
        
        if($sendBuilding)
        {
            $attachedBuildingData = DS_Building::GetAttachedBuildingByCellID($out['CELL_ID']);

            if(count($attachedBuildingData) == 0)
            {
                $out['ATTACHED_BUILDING_ID'] = 0;
                $out['BUILDING_ID'] = 0;
            }else{
                $out['ATTACHED_BUILDING_ID'] = (int)$attachedBuildingData[0]['id_attachedbuilding'];
                assert($out['ATTACHED_BUILDING_ID'] > 0);
                
                $out['BUILDING_ID'] = (int)$attachedBuildingData[0]['id_building'];
                assert($out['BUILDING_ID'] > 0);
            }
        }
        
        return $out;
    }
}

?>
<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * класс для расчета стоимости жизниобеспечения юнитов в течение одного турна.
 */
class GL_LiveUpkeep
{       
    /**
     * РАссчет подребления за трг группы юнитов группы юнитов
     *
     * @param DE_Unit $unit
     * @param numeric $amount
     * @return array()
     */
    static public function CountUnit (DE_Unit $unit, $amount, $multiply=TRUE)
    {
        assert(is_numeric($amount) && $amount >= 0);

        //ВСегда считаем только от целого значения кол-ва юнитов апкип
        $amount = ceil($amount);

        //Модификатор мир адля масштабирования апкипа
        if ($multiply) 
            $multiplier = DE_WorldSettings::CreateForCurentWorld()->get('LIVE_UPKEEP_UNITS_START');
        else
            $multiplier = 1;

        $summaryCost = self::_initResultArray();

        $unitAttributes = $unit->getAttributes();

        $attributes = $unitAttributes->getAttributeNamesByTypeName('TURN_UPKEEP');
        foreach($attributes as $k => $resourceName)
        {
            $summaryCost[$resourceName] += $unitAttributes->getAttribute('TURN_UPKEEP', $resourceName) * $amount * $multiplier;
        }

        return $summaryCost;
    }
    
    /**
     * Оставленно для совместимости
     *
     * @param DE_Unit $unit
     * @param unknown_type $amount
     * @param DE_UserInstance $userInstance
     */
    static public function CountUnits (DE_Unit $unit, $amount, DE_UserInstance $userInstance)
    {
        return self::CountUnit ($unit, $amount);
    }
    
    /**
     * Расчет потребления за турн армии
     *
     * @param DE_Army $army
     * @return array
     */
    static public function CountArmy (DE_Army $army)
    {   
        $armyStars = min(14, GL_ArmyPowerToStars::Count($army->getArmyPower()));
        $common = DS_Common::CreateForCurrentDB();
        $res = $common->getResultArray(
            "dynamic_upkeep",
            "select 1 from pg_tables where tablename='ds_dynamic_upkeep'",
            "", "", 'GLOBAL_CACHE');
        if (count($res)) {
            $res = $common->getResultArray(
                "dynamic_upkeep_".$armyStars,
                "select upkeep from ds_dynamic_upkeep where stars=".$armyStars,
                "", "", 'GLOBAL_CACHE');
            $multiplier = $res[0]['upkeep'];
        }
        else $multiplier = DE_WorldSettings::CreateForCurentWorld()->get('LIVE_UPKEEP_UNITS_START');

        $summaryCost = self::_initResultArray();

        $unitsGroupCollection = DC_ArmyUnitGroups::CreateFromArmyID($army->getID());
        $unitsGroupCollection->resetNext();

        while($unitsGroup = $unitsGroupCollection->next())
        {
            $summaryCostUnitsGroup = self::CountUnit($unitsGroup->getUnit(), $unitsGroup->getAmount(), FALSE);
            foreach($summaryCostUnitsGroup as $resourceName => $value)
            {
                $summaryCost[$resourceName] += $value * $multiplier;
            }
        }

        return $summaryCost;
    }
    
    /**
     * Расчет побления юнитами гна соте
     *
     * @param DE_Comb $comb
     * @return array()
     */
    static public function CountComb(DE_Comb $comb)
    {   
        $summaryCost = self::_initResultArray();                        
        
        $allArmysCollection = DC_CombArmys::CreateFromCombID($comb->getID());       
        $allArmysCollection->resetNext();
        while($army = $allArmysCollection->next())
        {                                                       
            if(!GL_IsArmyOnUpkeepOfComb::Count($comb, $army))
            {
                continue;
            }

            $summaryCostArmy = self::CountArmy($army);  
            foreach($summaryCostArmy as $resourceName => $value)                    
            {           
                $summaryCost[$resourceName] += $value;
            }
        }
                        
        return $summaryCost;
    }   

    /**
     * инициализация массива результата
     *
     * @return фккфн()
     */
    static private function _initResultArray()
    {
        $out = array();
        
        foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $key => $val)        
        {   
            $out[$val] = 0;
        }
        
        return $out;
    }
}

?>
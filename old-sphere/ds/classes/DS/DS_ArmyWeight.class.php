<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Статтический класс бибилотека запросов связанная с коэфициентами произведенной и потерянной армией игрка.
 */
class DS_ArmyWeight extends DS_Base
{
    /**
    *   
    * @desc Втавляет новые значения построенного и потерянного продовес
    * 
    */
    static function UpdateArmyWeight(DE_ArmyWeight $armyWeight) 
    {
        $common = self::getCommon();

        // Смотрим дату последней записи
        $sql = "SELECT 
                    *
                FROM
                    ds_army_weight_history
                WHERE
                    id_userinstance = ".$armyWeight->getID()."
                AND change_date > '".date("Y-m-d 00:00:01")."'
                ORDER BY change_date DESC
                LIMIT 1";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
    //SU_DebugTools::LogAll($sql, 'Annita.log');
    //SU_DebugTools::LogAll($res, 'Annita.log');
        // Если это сегодня, то не создаем новую запись, а просто обновляем старую
        if(count($res) > 0)
        {
            // assert($res[0]['id_army_weight_history'] > 0);
            // Обновляем данные в хистори
            $sql = "UPDATE  
                        ds_army_weight_history
                    SET 
                        army_weight_built = '".$armyWeight->getArmyWeightBuilt()."',
                        army_weight_lost = '".$armyWeight->getArmyWeightLost()."'
                    WHERE 
                    id_army_weight_history = ".$res[0]['id_army_weight_history'];
        } else {
            // Вставляем данные в хистори
            $sql = "INSERT INTO  
                        ds_army_weight_history
                    (id_userinstance, army_weight_built, army_weight_lost)
                    VALUES
                    (".$armyWeight->getID().", '".$armyWeight->getArmyWeightBuilt()."', '".$armyWeight->getArmyWeightLost()."')";
                
        }
        
        $common->executeQuery( $sql );
        assert(empty($common->errormsg));

        // Обновляем значения
        $sql = "UPDATE 
                    ds_army_weight
                SET
                    army_weight_built = '".$armyWeight->getArmyWeightBuilt()."',
                    army_weight_lost = '".$armyWeight->getArmyWeightLost()."'
                WHERE id_userinstance = ".$armyWeight->getID(); 
        $common->executeQuery ( $sql );
        assert(empty($common->errormsg));
                
        return true;

    }//END OF FUNCTION
        
    /**
    *   
    * @desc Получает параметры построенного и потерянного продовеса
    * 
    */
    static public function getByUserInstanceId($UIID)
    {
        assert(is_int($UIID) && $UIID > 0);
        
        $common = self::getCommon();

        $sql = "SELECT 
                    *
                FROM 
                    ds_army_weight
                WHERE 
                    id_userinstance = ".$UIID;
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg) && (count($res) == 0 || count($res) == 1));
    
        if(count($res) == 0)
        {
            return false;   
        }else{
            return $res[0];
        }       
    }   
        
    /**
    *   
    * @desc Устанавливает значение суммарного продовеса армии
    * 
    */
    static public function InsertArmyWeight($UIID, $armyWeightBuilt, $armyWeightLost)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_float($armyWeightBuilt) && $armyWeightBuilt >= 0);
        assert(is_float($armyWeightLost) && $armyWeightLost >= 0);
        
        $common = self::getCommon();

        $sql = "INSERT INTO  
                    ds_army_weight 
                (id_userinstance, army_weight_built, army_weight_lost)
                VALUES
                (".$UIID.", ".$armyWeightBuilt.", ".$armyWeightLost.")";
                
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
        
    /**
    *   
    * @desc Получает историю измений потеряного и построенного продовеса
    * СТАРАЯ ФУНКЦИЯ. 
    * Выводит график изменений продовеса, вместо графика значений
    * 
    */
    static public function GetWeightHistoryByUserInstanceID($UIID, $period, $countPoints)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert($period == 'month' || $period == 'week' || $period == 'days');
        assert(is_int($countPoints) && $countPoints > 0);
        
        $common = self::getCommon();

        $sql = "SELECT 
                    -- t.period,
                    COALESCE((SELECT 
                            army_weight_built
                        FROM 
                            ds_army_weight_history
                        WHERE 
                            change_date < period
                            AND 
                            id_userinstance = ".$UIID."  
                        ORDER BY 
                            change_date DESC
                        LIMIT 1
                    ), 0) as army_weight_built,
                        COALESCE((SELECT 
                            army_weight_lost
                        FROM 
                            ds_army_weight_history
                        WHERE 
                            change_date < period
                            AND 
                            id_userinstance = ".$UIID."  
                        ORDER BY 
                            change_date DESC
                        LIMIT 1
                    ), 0) as army_weigh_lost
                FROM 
                    (SELECT now() - generate_series(0, ".$countPoints.") * interval '1 ".$period."' AS period) AS t
                 ORDER BY t.period";    

        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    /**
    *   
    * @desc Получает историю измений потеряного и построенного продовеса
    * 
    */
    static public function GetWeightHistoryByUserInstanceIDNew($UIID, $period)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($period) && $period > 0);
        
        $common = self::getCommon();

        $sql = "        SELECT 
                           extract(year from change_date) as year,
                           extract(month from change_date) as month,
                           extract(day from change_date) as day,
                           army_weight_built,
                           army_weight_lost 
                        FROM 
                            ds_army_weight_history
                        WHERE 
                            change_date > now() - interval '$period days'
                            AND 
                            id_userinstance = $UIID 
                        ORDER BY change_date";  
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));

        return $res;
    }
    
    /**
    *   
    * @desc Получает историю измений реинкарнированого продовеса
    * 
    */
    static public function GetReincarnateHistoryByUserInstanceID($UIID, $period, $countPoints)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert($period == 'month' || $period == 'week' || $period == 'days');
        assert(is_int($countPoints) && $countPoints > 0);
        
        $common = self::getCommon();

        $sql = "SELECT 
                    -- t.period,
                    COALESCE((SELECT 
                            army_weight
                        FROM 
                            ds_army_reincarnation_hystory
                        WHERE 
                            date < period
                            AND 
                            id_userinstance = ".$UIID." 
                        ORDER BY 
                            date DESC
                        LIMIT 1
                    ), 0) as army_weight
                FROM 
                    (SELECT to_timestamp(".(time()+500).")  - generate_series(0, ".$countPoints.") * interval '1 ".$period."' AS period) AS t
                 ORDER BY t.period";    

        $res = $common->getResultArray ( "", $sql, "", "", "" );

        assert(empty($common->errormsg));
        $WeightHistory = array();
        // Немножко преобразуем массивчик
        for($i=1; $i < count($res); $i++)
        {           
            $WeightHistory[] = round($res[$i]['army_weight'] - $res[$i-1]['army_weight']);
        }

        return $WeightHistory;
    }
    
    /**
    *   
    * Получить список всех юнитов, которые могут быть построены на соте 
    * с учетом наук, рассы, и построенных зданий
    * (может использоваться для проверки возможности тренировки конкретного юнита)
    * 
    */
    static public function GetUnitListToReincarnation($UIID, $combId, $raceId, $unitId = false)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($combId) && $combId > 0);
        assert(is_int($raceId) && $raceId > 0);
        
        $common = self::getCommon();

        // Если нам надо проверить возможность строить определенный юнит
        $where = '';
        if ($unitId) {
            $where = '  AND id_unit = '.$unitId;
        }

        $sql = "SELECT 
                    id_unit, unitname
                FROM
                    (
                    SELECT 
                        u.id_unit, 
                        u.unitname,
                            (SELECT 
                                count(*)
                            FROM 
                                ds_produceableunit pu
                            LEFT JOIN ds_building b ON (b.id_building = pu.id_building)
                            LEFT JOIN  ds_attachedbuilding attb ON (attb.id_building = b.id_building)
                            LEFT JOIN ds_comb c ON (c.id_comb = attb.id_comb)
                            WHERE
                                pu.id_unit = u.id_unit
                                AND 
                                c.id_comb = $combId
                            ) as benough,
                            (SELECT
                                     (SELECT 
                                        count(id_unit)
                                      FROM 
                                        ds_unitneedsscience
                                      WHERE 
                                        id_unit = u.id_unit) 
                                      =
                                     (SELECT 
                                        count(uns.id_unit)
                                      FROM 
                                        ds_unitneedsscience uns,
                                        ds_userhasscience uhs
                                      WHERE
                                        uns.id_science = uhs.id_science
                                        AND 
                                        uhs.inresearch = false
                                        AND
                                        uhs.id_userinstance = $UIID
                                        AND
                                        uns.id_unit = u.id_unit
                               )
                            ) as senough,
                            (SELECT 
                                ucbr.id_race
                            FROM 
                                ds_unitcanbebuildbyrace ucbr
                            WHERE 
                                ucbr.id_unit = u.id_unit
                                AND 
                                ucbr.id_race = $raceId
                            ) as renough
                    FROM 
                        ds_unit u
                    LEFT JOIN ds_unithasclass uc ON (uc.id_unit = u.id_unit)
                    LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = u.id_localizedname AND l.id_language = 3)
                    WHERE u.id_unitcontainertype = 1
                    AND u.id_unit NOT IN (99, 98, 93)
                    ORDER BY uc.id_unitclass, l.text
                    ) as tt
                WHERE 
                    tt.benough >0
                    AND 
                    senough
                     AND 
                    renough > 0 ".$where;   
    
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    /**
    *   
    * Выбирает стоимость реинкарнации юнитов
    * 
    */
    static public function GetUnitReincarnationPrice($unitId, $worldId, $league)
    {
        assert(is_int($unitId) && $unitId > 0);
        assert(is_int($worldId) && $worldId > 0);
        
        $common = self::getCommon();

        $sql = "SELECT
                    CASE
                        WHEN an.attributename IS NULL THEN m.money_name
                        WHEN m.money_name IS NULL THEN an.attributename
                    END as full_name,
                    CASE
                        WHEN an.attributename IS NULL THEN m.short_name
                        WHEN m.money_name IS NULL THEN substr(an.attributename, 0, 3)
                    END as name,
                    CASE
                        WHEN an.attributename IS NULL THEN trcd.value*gm2.value
                        WHEN m.money_name IS NULL THEN trcd.value*gm1.value*gm2.value
                    END as price
                FROM
                    ds_unit_reincarnation_cost AS trcd
                    INNER JOIN ds_trade_course AS tc ON (tc.id_tradecourse = trcd.id_tradecourse)
                    LEFT JOIN ds_attributename AS an ON (an.id_attributename = tc.id_article AND tc.id_tradecategory = 1)
                    LEFT JOIN ds_money AS m ON (m.id_money = tc.id_article AND tc.id_tradecategory = 4)
                    LEFT JOIN ds_gamemodifier gm1 ON (gm1.id_world = trcd.id_world AND gm1.modifiername = 'UNIT_REINCARNATION_MULTIPLIER')
                    LEFT JOIN ds_gamemodifier gm2 ON (gm2.id_world = trcd.id_world and gm2.modifiername = 'UNIT_REINCARNATION_MULTIPLIER_LEAGUE_$league')
                WHERE
                    trcd.id_world = $worldId
                    AND
                    trcd.id_unit = $unitId
                ORDER BY trcd.id_tradecourse";  
        $res = $common->getResultArray ( "UNIT_REINC_PRICE_".$unitId."_".$league, $sql, "", "", "WORLD_CACHE" );
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    /**
    *   
    * Выбирает все юниты игрока, находящиеся на реинкарнации
    * 
    */
    static public function GetUnitOnReincarnation($UIID, $combId)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($combId) && $combId > 0);
        
        $common = self::getCommon();

        $sql = "SELECT 
                    eq.eventstarttime,
                    eq.eventendtime,
                    eho1.value as id_unit,
                    eho3.value as amount,
                    (SELECT u.unitname FROM ds_unit u WHERE u.id_unit = eho1.value) as unitname
                FROM
                    ds_eventqueue eq
                LEFT JOIN ds_eventparticipant ep ON (ep.id_eventqueue = eq.id_eventqueue)
                LEFT JOIN ds_event e ON (e.id_event = eq.id_event)
                LEFT JOIN ds_eventhasoption eho1 ON (eho1.id_eventqueue = eq.id_eventqueue AND eho1.id_eventoption = 1000033) -- ID_Unit
                LEFT JOIN ds_eventhasoption eho2 ON (eho2.id_eventqueue = eq.id_eventqueue AND eho2.id_eventoption = 1000034) -- ID_Comb
                LEFT JOIN ds_eventhasoption eho3 ON (eho3.id_eventqueue = eq.id_eventqueue AND eho3.id_eventoption = 1000036) -- CountToTrain

                WHERE 
                    ep.id_userinstance = $UIID      
                    AND 
                    e.eventname = 'REINCARNATE_UNIT'
                    AND 
                    eho2.value = $combId
                ORDER BY eq.eventendtime DESC"; 
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }   
    
    /**
    *   
    * Обновляет сумму реинкарнированных армий
    * и
    * вставляет запись в историю реинкарнации
    * 
    */
    static public function InsertArmyReincarnation($UIID, $armyWeight, $unitArr, $finalPrice)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_float($armyWeight) && $armyWeight > 0);
        assert(is_array($unitArr));
        assert(!empty($finalPrice));

        $common = self::getCommon();
        
        // Смотрим, есть ли у нас уже запись о реинкарнированной армии
        $sql = "SELECT * FROM
                    ds_army_reincarnation
                WHERE id_userinstance = $UIID";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        assert(count($res) == 0 || count($res) == 1);
        
        // Если записей еще не было
        if (count($res) == 0) {
            $newValue = $armyWeight;
            
            // Вставляем новое значение
            $sql = "INSERT INTO
                    ds_army_reincarnation
                (army_weight, id_userinstance)
                VALUES
                ($newValue, $UIID)";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));
        } else {
            
            $newValue = (float)($armyWeight + $res[0]['army_weight']);
            assert($newValue > 0);
            
            // Обновляем значения
            $sql = "UPDATE
                    ds_army_reincarnation
                SET army_weight = $newValue
                WHERE id_userinstance = $UIID";
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));
        }
        
        $id_army_reincarnation_hystory = self::GetNextLogID();

        // Вставляем данные в хистори
        $sql = "INSERT INTO
                    ds_army_reincarnation_hystory
                (id_army_reincarnation_hystory, id_userinstance, army_weight, total_price)
                VALUES
                ($id_army_reincarnation_hystory, $UIID, $newValue, '".$finalPrice."')"; 
        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));
        
        foreach ($unitArr as $unitId => $unitData) {
            assert($unitId > 0);
            $sql = "INSERT INTO
                        ds_army_reincarnation_unit_hystory
                    (id_army_reincarnation_hystory, id_unit, amount, price)
                    VALUES
                    ($id_army_reincarnation_hystory, $unitId, ".$unitData['amount'].", '".$unitData['res_for']."')";    
        
            $res = $common->executeQuery ( $sql );
            assert(empty($common->errormsg));
        }

        return true;
    }   
    
    /**
    *   
    * @desc Получаем сумму веса реинкарнированных армий
    * 
    */
    static public function GetReincarnateProdiumVal($UIID)
    {
        assert(is_int($UIID) && $UIID > 0);

        $common = self::getCommon();
        
        $sql = "SELECT * FROM
                    ds_army_reincarnation
                WHERE id_userinstance = $UIID";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        assert(count($res) == 0 || count($res) == 1);
        
        if (count($res) == 0) {
            return 0;
        } else {
            return $res[0]['army_weight'];
        }
    }
    
    static public function GetNextLogID()
    {
        return (int)self::getNextSeqVal(self::getCommon(), 'ds_army_reincarnation_hystory_id_army_reincarnation_hystory_seq');
    }
    
    
}//END OF CLASS


?>
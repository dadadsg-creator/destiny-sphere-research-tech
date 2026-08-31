<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Запросы связаные с платными услугами
 *
 */
class DS_PaymentService extends DS_Base
{
    /**
     * Проверяем, активен ли сервис
     * @param str $service
     * @return bool
     */
    static public function IsServiceActive($service)
    {
        assert(!empty($service));
        
        $common = self::getCommon();
        
        // Смотрим, изучается ли у игрока наука, выше 20го уровня
        $sql = "
                SELECT
                    mat.is_active
                FROM
                    ds_money_action_types mat
               WHERE
                    action_name = '$service'";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if (count($res) == 0 || $res[0]['is_active'] != 't') {
            return false;
        }
        
        return true;
    }   
    
    /**
     * Смотрим, является ли чел нубом(он считается не нубом, при изучении наки свыше 20го лвла) 
     *
     * @param int $userInstance
     * @return bool
     */
    static public function CheckUserIsNub($userInstanse)
    {
        assert($userInstanse > 0);
        $common = self::getCommon();
        
        // Смотрим, изучается ли у игрока наука, выше 20го уровня
        $sql = "
                SELECT 
                    s.level
                FROM 
                    ds_userhasscience AS uhs
                LEFT JOIN ds_science AS s ON s.id_science = uhs.id_science
                WHERE 
                    uhs.id_userinstance = $userInstanse 
                    AND 
                    uhs.inresearch = TRUE
                    AND s.level > 20";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if (count($res) > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Получаем сколько турнов осталось до окончания строительства здания
     *
     * @param int $attachedbid
     * @return int
     */
    static public function GetLeftTimeToCmplitision($attachedbid)
    {
        assert($attachedbid > 0);
        $common = self::getCommon();
        
        $sql = "SELECT 
                        extract(epoch from (eq.eventendtime-now())) as lefttime
                    from 
                        ds_eventqueue eq,
                        ds_attachedbuildingbindeventqueue abbeq
                    where 
                        eq.id_eventqueue = abbeq.id_eventqueue
                        AND 
                        abbeq.id_attachedbuilding = $attachedbid
                        AND
                        eq.istrash = false";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return (int)ceil($res[0]['lefttime']/60/DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME'));
    }
    
    /**
     * Завершаем все ивенты игрока, связанные с изучением науки и постройством зданий(не считая здания Гондола-Транспортер)
     *
     * @param int $attachedbid
     * @return
     */
    static public function ComplitTraining($attachedbid)
    {
        assert($attachedbid > 0);
        $common = self::getCommon();
        
        $sql = "UPDATE
                    ds_eventqueue 
                SET 
                    eventendtime = NOW() 
                WHERE 
                    id_eventqueue IN (
                                SELECT e.id_eventqueue 
                                FROM 
                                    ds_eventqueue e,
                                    ds_attachedbuildingbindeventqueue abbeq
                                WHERE 
                                    e.id_eventqueue = abbeq.id_eventqueue
                                    AND 
                                    abbeq.id_attachedbuilding = $attachedbid
                                            )";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return;
    }
    
    /**
     * Получаем цену ускорения науки в зависимости от ее типа и уровня
     *
     * @param str $sciencename
     * @return int
     */
    static public function GetSciencePrice($sciencename)
    {
        assert(!empty($sciencename));
        
        $common = self::getCommon();
        
        $sql = "
                SELECT  
                    sap.price
                FROM ds_science s
                LEFT JOIN ds_science_acceleration_price sap ON (sap.id_science = s.id_science)
                WHERE 
                    s.sciencename = '".$sciencename."'
                ";
        
        $res = $common->getResultArray(self::FormMark(__METHOD__, $sciencename), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));
        assert(count($res) == 1);

        return (int)$res[0]['price'];
    }
    
    /**
     *  Получаем максимальное колтичество археологов/ученых, которое можем купить
     *
     * @param str
     * @return int
     */
    static public function GetMaxUnitAmount($id_comb, $userInstanse, $unit)
    {
        assert(is_int($id_comb) && $id_comb > 0);
        assert(is_int($userInstanse) && $userInstanse > 0);
        assert(!empty($unit));
        
        $common = self::getCommon();
        
        $sql = "
            SELECT            
                co.id_comb,
                (
                     SELECT 
                            chre.value 
                     FROM  
                            ds_combhasresource chre                
                     LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
                     LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
                     LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
                     WHERE 
                        chre.id_comb = co.id_comb
                         AND  
                         attrtype.typename = 'MAX_AMOUNT'
                         AND 
                         attrname.attributename = '".$unit."'
                  ) AS max_amount,
                  (
                     SELECT 
                         chre.value 
                     FROM  
                         ds_combhasresource chre                
                     LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
                     LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
                     LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
                     WHERE 
                          chre.id_comb = co.id_comb
                         AND  
                         attrtype.typename = 'CURRENT_AMOUNT'
                         AND 
                         attrname.attributename = '$unit'
                 ) AS cur_amount ,
                  
                (
                     SELECT 
                         chre.value 
                     FROM  
                         ds_combhasresource chre                
                     LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
                     LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
                     LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
                     WHERE 
                          chre.id_comb = co.id_comb
                         AND  
                         attrtype.typename = 'AWAY_AMOUNT'
                         AND 
                         attrname.attributename = '$unit'
                  ) AS away_amount,
                  
              (SELECT 
         
                COALESCE(
                    sum(
                        (SELECT 
                                value 
                            FROM 
                                ds_eventhasoption eho
                            LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
                            WHERE 
                                eho.id_eventqueue = eq.id_eventqueue
                                 AND
                                eo.optionname = 'Amount')
                    ), 0) as sum
            FROM     
         
                ds_eventqueue eq
            LEFT JOIN ds_event e ON (e.id_event = eq.id_event)
            LEFT JOIN ds_eventparticipant ep ON (eq.id_eventqueue = ep.id_eventqueue)
            LEFT JOIN ds_userinstance uit ON (uit.id_userinstance = ep.id_userinstance)
            WHERE
                   uit.id_userinstance = ui.id_userinstance
                AND 
                    e.eventname = 'TRANSFER_RESOURCE'
                AND
                    -- нужный юнит
                        (SELECT 
                            value 
                        FROM 
                            ds_eventhasoption eho
                        LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
                        WHERE 
                            eho.id_eventqueue = eq.id_eventqueue
                             AND
                            eo.optionname = 'ID_Article' 
                        ) = (
                            SELECT 
                                id_attributename
                             FROM  
                                ds_attributename 
                             WHERE 
                                 attributename = '$unit')
                    AND
                        (SELECT 
                            value 
                        FROM 
                            ds_eventhasoption eho
                        LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
                        WHERE 
                            eho.id_eventqueue = eq.id_eventqueue
                            AND
                            eo.optionname = 'ID_Category' 
                        ) = 1 
                        ) as transfer
            FROM
                 ds_userinstance ui
                 LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
            WHERE
               ui.id_userinstance = $userInstanse
                ";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));   

        if (count($res) == 0) {
            return 0;
        } 
        
        $sum = 0;
        $maxEptyPlace = 0;
        
        // Суммируем юниты на всех сотах
        foreach ($res as $key => $data)
        {
            // Если попадаем на нужную нам соту
            if ($data['id_comb'] == $id_comb) {
                // Количество ученых, на которых нам хватит места в научных центрах
                $maxEptyPlace = (int)$data['max_amount'] - (int)$data['cur_amount'] - (int)$data['away_amount'];
            }
            
            $sum += (int)$data['cur_amount'];
            $sum += (int)$data['away_amount'];
        }
        
        // Добавляем юниты из трансфера
        $sum += (int)$res[0]['transfer'];
        
        $unitLimit = DE_WorldSettings::CreateForCurentWorld()->get($unit.'_BUY_LIMIT');
        
        // Если огараничение есть 
        if ($unitLimit > 0 ) {
            if ($unitLimit <= $sum) {
                return 0;
            } 
            return min($maxEptyPlace, $unitLimit-$sum);
        } else {
            return $maxEptyPlace;
        }
    }
    
    /**
     *  Получаем данные об изучаемой науке у игрока
     *
     * @param int $userInstanse
     * @return array
     */
    static function GetUserScience($userInstanse) 
    {
        assert($userInstanse > 0);

        $common = self::getCommon();
                
        $sql = "            SELECT 
                                ep.id_eventqueue,
                                eq.eventendtime,
                                s.level,
                                l.text,
                                s.sciencename,
                                COALESCE (ea.id_eventqueue, 0) as accelerated
                            FROM ds_eventqueue eq
                            LEFT JOIN ds_eventparticipant ep ON (ep.id_eventqueue = eq.id_eventqueue)
                            LEFT JOIN ds_event ev ON (eq.id_event = ev.id_event)
                            LEFT JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                            LEFT JOIN ds_eventoption eo ON (eho.id_eventoption = eo.id_eventoption)
                            LEFT JOIN ds_science s ON (eho.value = s.id_science)
                            LEFT JOIN ds_sciencebranch sb ON (sb.id_branch = s.id_branch)
                            LEFT JOIN ds_localizedtextlanguage l ON (sb.id_localizedname = l.id_localizedtext AND l.id_language = ".constant('DEFAULT_LANGUAGE').")
                            LEFT JOIN ds_eventacceleration ea ON (ea.id_eventqueue = eq.id_eventqueue)
                            WHERE 
                                ep.id_userinstance = $userInstanse
                                 AND 
                                eq.istrash = 'f'
                                 AND 
                                ev.eventname = 'NEW_RESEARCH'
                                 AND 
                                eo.optionname = 'ID_Science'";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
    
    /**
     *  Проверяем, является ли наука ускоренной
     *
     * @param int $idEventQueue
     * @return boolean
     */
    static function IsScienceAcceleraded($idEventQueue) 
    {
        assert($idEventQueue > 0);

        $common = self::getCommon();
                
        $sql = "            SELECT 
                                *
                            FROM 
                                ds_eventacceleration
                            WHERE 
                                id_eventqueue = $idEventQueue
                                ";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        if (count($res) > 0) {
            return true;
        }
        return false;
    }
    
    /**
     *  Обновляем время окончания изучения науки
     *
     * @param int $userInstanse
     * @return
     */
    static function SetNewScienceEndDate($userInstanse, $newEndDate) 
    {
        assert($userInstanse > 0);
        assert(!empty($newEndDate));

        $common = self::getCommon();
                
        $sql = "            
                UPDATE 
                    ds_eventqueue 
                SET 
                    eventendtime = '".$newEndDate."'
                WHERE 
                    id_eventqueue = 
                        (SELECT 
                            cbeq.id_eventqueue
                        FROM 
                            ds_combbindeventqueue cbeq,
                            ds_comb co,
                            ds_eventqueue eq,
                            ds_event e
                        WHERE 
                            cbeq.id_comb = co.id_comb
                            AND co.id_userinstance = ".$userInstanse."
                            AND cbeq.id_eventqueue = eq.id_eventqueue
                            AND eq.id_event = e.id_event
                            AND eq.isTrash = false
                            AND e.eventname = 'NEW_RESEARCH')";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }
    
    /**
     *  Помечаем ивент как ускоренный
     *
     * @param int $idEventQueue
     * @return
     */
    static function SetEventAsAccelerated($idEventQueue) 
    {
        assert($idEventQueue > 0);

        $common = self::getCommon();
        
        // Помечаем ивент как ускоренный
        $sql = "            
                INSERT INTO
                    ds_eventacceleration
                (id_eventqueue)
                VALUES
                ($idEventQueue)";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
    }
    
    /**
    * @return 
    * @desc Логирует действие
    */
    public static function Log ($userId, $isPaid, $isOrdered, $isComplete, $isCanceled, $actionName,  $prise, $credit, $info, $usersArr = 0)
    {               
        $common = self::getCommon();
        
        $idLog = DS_Base::getNextSeqVal($common, 'ds_paymentserviceslog_id_paymentserviceslog_seq');
        if(!empty($common->errormsg))
        {
            return false;
        }
        
        if ($credit == 'SC')
        {
            $SC = $prise;
        } else {
            $SC = 0;
        }
        
        if ($credit == 'GC')
        {
            $GC = $prise;
        } else {
            $GC = 0;
        }
        
        if ($isPaid == 't')
        {
            $PaidDate = 'now()';
        } else {
            $PaidDate = 'NULL';
        }
        
        if ($isOrdered == 't')
        {
            $OrderedDate = 'now()';
        } else {
            $OrderedDate = 'NULL';
        }
        
        if ($isComplete == 't')
        {
            $CompleteDate = 'now()';
        } else {
            $CompleteDate = 'NULL';
        }
        
        if ($isCanceled == 't')
        {
            $CancelDate = 'now()';
        } else {
            $CancelDate = 'NULL';
        }
        
        $sql = "INSERT INTO ds_paymentserviceslog 
                    (
                    id_paymentserviceslog,
                    is_paid,
                    paid_date,
                    is_ordered,
                    ordered_date,
                    is_complete,
                    complite_date,
                    is_canceled,
                    cancel_date,
                    action_name,
                    gc_price,
                    sc_price,
                    info
                    ) 
                VALUES 
                    (
                    $idLog,
                    '$isPaid',
                    $PaidDate,
                    '$isOrdered',
                    $OrderedDate,
                    '$isComplete',
                    $CompleteDate,
                    '$isCanceled',
                    $CancelDate,
                    '$actionName',
                    $GC,
                    $SC,
                    '$info'
                    )";
        
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        //Если игрок связанный с услугой один
        if ($usersArr == 0)
        {
            $sql = "INSERT INTO ds_paymentserviceslog_users 
                    (
                    id_paymentserviceslog,
                    id_user,
                    is_initiator,
                    is_payer
                    ) 
                VALUES 
                    (
                    $idLog,
                    ".$userId.",
                    't',
                    't'
                    )";

            $common->executeQuery($sql);
            
            assert(empty($common->errormsg));
        } else {
            //проходимся по каждому игроку, связанному с данной услугой
            foreach ($usersArr as $key => $data)
            {
                $sql = "INSERT INTO ds_paymentserviceslog_users 
                    (
                    id_paymentserviceslog,
                    id_user,
                    is_initiator,
                    is_payer
                    ) 
                VALUES 
                    (
                    $idLog,
                    ".$data['id_user'].",
                    '".$data['is_initiator']."',
                    '".$data['is_payer']."'
                    )";

                $common->executeQuery($sql);
                assert(empty($common->errormsg));
            }
        }
        
        return true;
        
    }//END OF METHOD
}

?>
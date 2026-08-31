<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Библиотека запросов к БД свзанных с боем.
 */
class DS_Battle extends DS_Base
{
    
    /**
     * Возвращает сведения по бою на сонваие ID боя
     *
     * @param unknown_type $id
     * @return unknown
     */
    static function GetBattleByID($id)
    {
        assert(is_int($id) && $id>0);
        
        $common = self::getCommon();
        
        $sql = "   SELECT
                        id_battlecomb,
                        battlestartdate,
                        battleenddate,
                        (
                            SELECT
                                id_battleparticipant
                            FROM
                                ds_battleparticipant
                            WHERE
                                id_battle = $id
                                AND participantflag = 'A'
                        ) AS id_attacker,
                        (
                            SELECT
                                id_battleparticipant
                            FROM
                                ds_battleparticipant
                            WHERE
                                id_battle = $id
                                AND participantflag = 'D' 
                            order by not iscombowner
                            limit 1
                        ) AS id_defender,
                        winner,
                        id_battleendreason,
                        id_attackercomb,
                        retreat_side,
                        retreat_round_number,
                        retreat_with_empty_damage
                    FROM
                            ds_battle    
                    WHERE
                            id_battle = $id";   
            
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));               
        if (count($res)>0)
        {
            return $res[0];
        }
        else 
        {
            return false;
        }

    }//END OF FUNCTION  
            
    /**
     * Возвращает сведения из таблицы ds_battlerparticipanrhasuhitcontainer 
     * по конкретному контейнеру
     *
     * @param integer $id - ID армии участника битвы
     * @return array
     */
    static function GetBattleParticipantArmyByID($id)
    {
        assert(is_int($id) && $id>0);
        
        $common = self::getCommon();
        
        $sql = "
                SELECT
                        *
                FROM
                    ds_battleparticipanthasunitcontainer
                WHERE
                    id_battleparticipanthasunitcontainer = $id";        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));               
        
        if (count($res)>0)
        {
            return $res[0];
        }
        else 
        {
            return false;
        }
    }//END OF FUNCTION  
    
    /**
     * Возвращает массив с ID партисипантов этой битвы
     *
     * @param integer $battleID - ID боя.
     * @return array
     */
    static function GetBattleDefendParticipants($battleID)
    {
        assert(is_int($battleID) && $battleID>0);
        
        $common = self::getCommon();
        
        $sql = "
                SELECT
                        id_battleparticipant
                FROM
                    ds_battleparticipant
                WHERE
                    id_battle = $battleID
                    AND participantflag = 'D'";
        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));               
        if (count($res)>0)
        {
            return $res;
        }
        else 
        {
            return false;
        }
    }//END OF FUNCTION  
    
        
    static function GetBattleEndReasonIDByWinnerAndReasonName(GB_BattleParticipantTypeCode $winnerCode, $reasonName)
    {       
        assert(is_string($reasonName) && !empty($reasonName));
            
        $common = self::getCommon();
        
        $sql = "SELECT
                    id_battleendreason
                FROM
                    ds_battleendreasons
                WHERE
                    reasonname = '".$reasonName."' AND
                    armytype = '".$winnerCode->getInversValue()."'";                            
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $winnerCode->getInversValue()."_".$reasonName), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );     
        assert(empty($common->errormsg));                       
        
        if (count($res)>0)
        {
            return (int)$res[0]['id_battleendreason'];
        }
        else 
        {
            return false;
        }
    }//END OF FUNCTION  

    
    static function GetBattleEndReasonByID($reasonID)
    {
        assert(is_int($reasonID) && $reasonID>0);
        
        $common = self::getCommon();
        
        $sql = "SELECT
                        *
                FROM
                    ds_battleendreasons
                WHERE
                    id_battleendreason = ".$reasonID;

        $res = $common->getResultArray ( self::FormMark(__METHOD__, $reasonID), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );     
        assert(empty($common->errormsg));               

        if (count($res)>0)
        {
            return $res[0];
        }
        else 
        {
            return false;
        }
    }//END OF FUNCTION  
    
    static function GetCombDefendUserInstancesID(DE_Comb $comb)
    {
        assert(is_null($comb));
        
        $id_comb = $comb->getCombID();
        
        $common = self::getCommon();
        
        $sql = "SELECT
                    DISTINCT uc.id_userinstance
                FROM
                    ds_unitcontainer uc,
                    ds_unitcontaineraction uca,
                    ds_unitcontaineractiontype ucat
                WHERE
                    (
                        uc.id_currentcomb = $id_comb
                        AND uc.inaction = 't'
                        AND uc.id_unitcontainer = uca.id_unitcontainer
                        AND uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                        AND ucat.actiontypename = 'DEFEND'
                    )OR(
                        uc.id_basecomb = $id_comb
                    )
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")";
        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));               
        if (count($res)>0)
        {
            return $res;
        }else{
            return false;
        }       
    }
        
    /**
     * ВСтавка в БД сущности боя и двух основных участников боя
     *
     * @param DE_Comb $comb - Сота на которой бой
     * @param DE_UserInstance $attacker - атакующий инстанс
     * @return integer
     */
    static function InsertBattle(DE_Comb $combTo, DE_Comb $combFrom, GB_Date $battleStartDate)
    {
        $attacker = $combFrom->getUserInstance();
        
        $common = self::getCommon();
        
        $id = self::getNextSeqVal($common, 'ds_battle_id_battle_seq');
        
        $sql = "INSERT INTO ds_battle               
                (id_battle, id_battlecomb, battlestartdate, id_attackercomb)
                VALUES              
                (
                    ".$id.",
                    ".$combTo->getID().", 
                    '".$battleStartDate->getPgFormat()."',
                    ".$combFrom->getID().")";       
        $res = $common->executeQuery ($sql);        
        assert(empty($common->errormsg));           
                
        return $id;     
    }
        
    
    static public function InsertBattleParticipant($battleID, DE_UserInstance $userInstance, $participantFlag, $isCombOwner)
    {
        assert(is_int($battleID) && $battleID>0);       
        assert($participantFlag=='D' || $participantFlag=='A');
        assert($isCombOwner=='t' || $isCombOwner='f');
        
        $common = self::getCommon();

        $id = self::getNextSeqVal($common, 'ds_battleparticipant_id_battleparticipant_seq');
        
        $sql = "INSERT INTO ds_battleparticipant
                (id_battleparticipant, id_battle, id_userinstance, participantflag, iscombowner)
                VALUES
                (".$id.", ".$battleID.",".$userInstance->getID().",'".$participantFlag."','".$isCombOwner."')";
    
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
        
        return $id;
    }
    
    /**
     * Апдейтим сущность боя
     *
     * @param DE_Battle $battle
     */
    static function UpdateBattle(DE_Battle $battle)
    {       
        $common = self::getCommon();
                                    
        $retreatSideNameLiteral = $battle->getRetreatSideName()->value;
        
        $setParts = array();
        
        if(!empty($retreatSideNameLiteral))
        {
            $setParts[] = "retreat_side = '".$battle->getRetreatSideName()->value."'";
            $setParts[] = "retreat_round_number = ".$battle->getRetreatRoundNumber();           
        }
                
        if(!($battle->getEndDate() instanceof GB_DateNull))
        {
            $setParts[] = "battleenddate = '".$battle->getEndDate()->getPgFormat()."'";
        }
        
        if($battle->isBattleEnded())
        {
            $setParts[] = "id_battleendreason = ".$battle->getBattleEndReason()->getID();
            $setParts[] = "winner = '".$battle->getWinnerCode()->value."'";
        }
        
        if($battle->isRetreatWithEmptyDamage())
        {
            $setParts[] = "retreat_with_empty_damage = 't'";
        } else {
            $setParts[] = "retreat_with_empty_damage = 'f'";
        }
        
        if(count($setParts) == 0)
        {
            return;
        }
        
        $sql = "UPDATE ds_battle
                SET                     
                    ".implode(', ', $setParts)."
                WHERE
                    id_battle = ".$battle->getID();             
        $res = $common->executeQuery($sql);     
        assert(empty($common->errormsg));
        
    }
    
    static function UpdateParticipantArmy(DE_BattleParticipantArmy $participantArmy)
    {
        $common = self::getCommon();
        
        $sql = "UPDATE ds_battleparticipanthasunitcontainer
                SET 
                    dieafterround = ".$participantArmy->getDieRoundNumber()."                   
                WHERE
                    id_battleparticipanthasunitcontainer = ".$participantArmy->getID();             
        $res = $common->executeQuery ($sql);        
        assert(empty($common->errormsg));

        return $res;    
    }
    
    /**
     * Сколько боёв было на данной соте за период, начиная с заданного
     *
     * @param int id_battlecomb - id соты
     * @param string date $start_date - начало периода
     * @return array
     */
    static function GetCountOfBattlesOnComb($id_battlecomb, $start_date)
    {
        assert(is_int($id_battlecomb) && $id_battlecomb>0);
        $common = self::getCommon();
        
        $sql = "SELECT count(*) as cnt 
                FROM ds_battle
                WHERE id_battlecomb = ".$id_battlecomb." and battleenddate >= '{$start_date}'";
                
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));       
        
        if (count($res)>0)
        {
            return $res[0]['cnt'];
        }
        else 
        {
            return false;
        }
    }   

    /**
     * Сколько атак совершил юзер за период, начиная с заданного
     *
     * @param int id_battlecomb - id соты
     * @param string date $start_date - начало периода
     * @return array
     */
    static function GetCountOfAttacks($id_userinstance, $startdate)
    {
        assert(is_int($id_userinstance) && $id_userinstance>0);
        $common = self::getCommon();
        
        $sql = "SELECT count(*) as cnt 
                FROM ds_battle
                WHERE id_attackercomb in ( SELECT id_comb FROM ds_comb where id_userinstance = {$id_userinstance} ) and battleenddate >= '{$startdate}'";
                
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));       
        
        if (count($res)>0)
        {
            return $res[0]['cnt'];
        }
        else 
        {
            return false;
        }
    }   
    
    /**
     * Выборка сведений об участники боя
     *
     * @param int $id - ID Участника боя.
     * @return array
     */
    static function GetBattleParticipantByID($id)
    {
        assert(is_int($id));
        $common = self::getCommon();
        
        $sql = "SELECT * 
                FROM ds_battleparticipant
                WHERE id_battleparticipant = ".$id;     
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));
        
        if (count($res)>0)
        {
            return $res[0];
        }
        else 
        {
            return false;
        }
    }   
    
    static function InsertParticipantArmy($battlepaticipantID,$unitcontainerID,$unitcontainername)
    {
        assert(is_int($battlepaticipantID) && $battlepaticipantID >0);
        assert(is_int($unitcontainerID) && $unitcontainerID > 0 );
        // assert(is_string($unitcontainername));
        
        $common = self::getCommon();
        
        $id = self::getNextSeqVal($common, 'ds_battleparticipanthasunitco_id_battleparticipanthasunitco_seq');
        
        $sql = "INSERT INTO ds_battleparticipanthasunitcontainer
                (
                    id_battleparticipanthasunitcontainer,
                    id_battleparticipant,
                    id_unitcontainer,
                    unitcontainername
                ) VALUES (
                    ".$id.",
                    ".$battlepaticipantID.", 
                    ".$unitcontainerID.", 
                    '".$unitcontainername."'
                )";     
        $res = $common->executeQuery ($sql);        
        assert(empty($common->errormsg));                   
        
        assert($id > 0);
        
        return $id;
        
    }
    
    static function UpdateBattleParticipant(DE_BattleParticipant $participant)
    {
        $common = self::getCommon();
        
        $sql = "UPDATE ds_battleparticipant
                SET 
                    participantflag = '".$participant->getParticipantFlag('PG')."', 
                    iswinner = '".$participant->getIsWinner('PG')."',
                    battlexp = '".$participant->getBattleXP()."'
                WHERE
                    id_battleparticipant = ".$participant->getID();
                
        $res = $common->executeQuery ($sql);        
        assert(empty($common->errormsg));

        return $res;    

    }
    
    static function GetBattleParticipantArmys($participantID)
    {
        assert(is_int($participantID) && $participantID>0);
        
        $common = self::getCommon();
        
        $sql = "SELECT 
                        id_battleparticipanthasunitcontainer
                FROM
                    ds_battleparticipanthasunitcontainer
                WHERE
                    id_battleparticipant = ".$participantID;
        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));               
        if (count($res)>0)
        {
            return $res;
        }
        else 
        {
            return false;
        }
    }
    
    static function GetParticipantArmiesOnComb(DE_BattleParticipant $battleParticipant, DE_Comb $comb)
    {       
        //Сперва выбераем из БД.
        $common = self::getCommon();        
                
        $createdObjects = GB_SingletonObjectStorage::GetObjectsIDForClass('DE_Army');
        $createdObjects[] = 0;
        
        $sql = "SELECT DISTINCT
                    uc.id_unitcontainer,
                    uc.unitcontainername
                FROM 
                    ds_unitcontainer AS uc 
                    JOIN ds_unitorcontainertype AS uct ON (uc.id_unitcontainertype = uct.id_unitcontainertype)
                WHERE 
                    uc.id_userinstance = ".$battleParticipant->getUserInstance()->getID()." AND
                    (
                        (uc.id_currentcomb = ".$comb->getID()." AND uc.inaction = 't') 
                        OR 
                        (uc.id_basecomb = ".$comb->getID()."  AND uc.inaction = 'f')
                    )
                    AND 
                    uc.id_unitcontainer NOT IN (".implode(',', $createdObjects).") 
                    AND
                    uct.containertypename = 'ARMY'
                    AND NOT
                    uc.id_unitcontainer IN (".implode(', ', GB_SingletonObjectStorage::GetIDsFromGraveYardByClass('DE_Army', true)).")  ";
        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));           
            
        $armysList = GB_SingletonObjectStorage::GetObjectsOfClass('DE_Army');

        $armysList->resetPointer();
        while($army = $armysList->getNextElement())
        {

            $condition1 = $army->getUserInstance()->equals($battleParticipant->getUserInstance());
            $condition2 = $army->getCurentComb()->equals($comb) || ($army->getBaseComb()->equals($comb) && !$army->getInAction());
            
            if($condition1 && $condition2)
            {
                $res[] = array(
                    'id_unitcontainer' => $army->getID(),
                    'unitcontainername' => $army->getName()
                );
            }
        }

        if (count($res)>0)
        {
            return $res;
        }
        else 
        {
            return false;
        }
    }
    
    static public function InsertBattleRound (DE_Battle $battle, $roundNumber, GB_Date $startDate, GB_TimePeriod $duration)
    {
        assert($roundNumber>=0);
        
        //Сперва выбераем из БД.
        $common = self::getCommon();

        $endDate = GB_TypeFactory::CreateBase('GB_Date', (int)1);   

        $endDate->value = $startDate->value + $duration->value;
        
        $sql = "select nextval('public.ds_battleround_id_battleround_seq') as id";
        $res = $common->getResultArray ( "", $sql, "", "", "" );    
        assert(empty($common->errormsg) && count($res) == 1);                   
        
        $id = (int)$res[0]['id'];
        assert($id > 0);    
        
        $sql = "INSERT INTO ds_battleround
                (id_battleround, id_battle, roundnumber, roundstarttime, roundendtime)
                VALUES
                (".$id.", ".$battle->getID().", ".$roundNumber.", '".$startDate->getPgFormat()."', '".$endDate->getPgFormat()."')";     
        $res = $common->executeQuery ($sql);                
        assert(empty($common->errormsg));   
                        
        return $id;
    }
    
    static function GetBattleRoundByID($roundID)
    {       
        assert(is_int($roundID) && $roundID>0);
        
        //Сперва выбераем из БД.
        $common = self::getCommon();        
        
        $sql = "SELECT * 
                FROM ds_battleround
                WHERE
                id_battleround = ".$roundID."";     
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $roundID), $sql, "", "", "GLOBAL_CACHE" );      
        assert(empty($common->errormsg));               
        
        if (count($res)>0)
        {
            return $res[0];
        }
        else 
        {
            return false;
        }
    }
    
    
    static function GetNextIDForBattleResult()
    {
        $common = self::getCommon();
        
        $sql = "select nextval('ds_battleresult_id_battleresult_seq') as id";
        $res = $common->getResultArray ( "", $sql, "", "", "" );    
        assert(empty($common->errormsg) && count($res) == 1);                   
        
        $id = (int)$res[0]['id'];
        assert($id > 0);
        
        return $id;
    }
    
    static function InsertBattleRoundResult(DE_BattleRoundResult $result)
    {       
        //Сперва выбераем из БД.
        $common = self::getCommon();

        
        $sql = "INSERT INTO ds_battleresult
                (
                    id_battleresult,
                    id_battleround, 
                    id_battleparticipant, 
                    id_unitcontainer, 
                    id_unit, 
                    amount, 
                    loses, 
                    receivedamage, 
                    outputdamage
                )
                VALUES
                (                               
                    ".$result->getID().", 
                    ".$result->getRoundID().", 
                    ".$result->getBattleParticipantID().", 
                    ".$result->getArmyID().", 
                    ".$result->getUnit()->getID().", 
                    ".$result->getAmount().", 
                    ".$result->getLoses().",  
                    ".$result->getReceiveDamage().", 
                    ".$result->getOutputDamage()."
                )";             

        $res = $common->executeQuery ($sql);                
        assert(empty($common->errormsg));                   
    }
    
    static function GetBattleRoundResultByID($resultID)
    {       
        assert(is_int($resultID) && $resultID>0);
        
        //Сперва выбераем из БД.
        $common = self::getCommon();        
        
        $sql = "SELECT * 
                FROM ds_battleresult
                WHERE
                id_battleresult = ".$resultID."";       
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $resultID), $sql, "", "", "GLOBAL_CACHE" );     
        assert(empty($common->errormsg));               
        
        if (count($res)>0)
        {
            return $res[0];
        }else{
            return false;
        }
    }
    
    static function UpdateBattleRound(DE_BattleRound $round)
    {               
        //Сперва выбераем из БД.
        $common = self::getCommon();

        $setParts = array();
        $setParts[] = " haveadds = ".$round->getAdds();
        if(!is_null($round->getRetreatSide()))
        {
            $setParts[] = " retreat_side = '".$round->getRetreatSide()->value."'";
            $retreatStarted = $round->isRetreatStarted() ? 't' : 'f';
            $setParts[] = " retreat_started = '".$retreatStarted."'";
        }
                
        $sql = "
                UPDATE ds_battleround
                SET ".implode(',', $setParts)."
                WHERE
                id_battleround = ".$round->getID()."";  
        $res = $res = $common->executeQuery ($sql);         
        assert(empty($common->errormsg));               
        
        return true;
    }
    
    static public function GetRoundIDByBattleAndNumber(DE_Battle $battle, $number)
    {
        assert($number>=0);
        
        //Сперва выбераем из БД.
        $common = self::getCommon();        
        
        $sql = "SELECT id_battleround 
                FROM ds_battleround
                WHERE
                    id_battle = ".$battle->getID()." AND
                    roundnumber = ".$number."";     
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));
        if (is_array($res) && count($res)>0 && key_exists('id_battleround', $res[0]))
            return $res[0]['id_battleround'];
        else return 0;
    }
    
    static public function GetReportIDByBattleID($battleID)
    {
        assert(is_int($battleID) && $battleID>0);
        
        $common = self::getCommon();        
        
        $sql = "SELECT id_battlereport
                FROM ds_battlereport
                WHERE
                    id_battle = ".$battleID;        
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));
        assert(is_array($res) && count($res)>0 && key_exists('id_battlereport', $res[0]));
        
        return (int)$res[0]['id_battlereport'];
    }
    
    static public function GetReportIDByBattleIDAndParticipantFlag($battleID, $participantFlag)
    {
        assert(is_int($battleID) && $battleID>0);
        assert(is_null($participantFlag) || ($participantFlag == 'A' || $participantFlag == 'D'));
            
        $common = self::getCommon();        
        
        $sql = "SELECT id_battlereport
                FROM ds_battlereport
                WHERE
                    id_battle = ".$battleID."
                    AND
                    (
                        participantflag = '".$participantFlag."'
                        OR
                        participantflag IS NULL
                    )
                    ";
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));
        assert(is_array($res) && count($res)>0 && key_exists('id_battlereport', $res[0]));
        
        return (int)$res[0]['id_battlereport'];
    }
    
    static public function GetReportByBattleIDAndParticipantFlag($battleID, $participantFlag)
    {
        assert(is_int($battleID) && $battleID>0);
        assert(is_null($participantFlag) || ($participantFlag == 'A' || $participantFlag == 'D'));
            
        $common = self::getCommon();        
        
        $sql = "SELECT *
                FROM ds_battlereport
                WHERE
                    id_battle = ".$battleID."
                    AND
                    (
                        participantflag = '".$participantFlag."'
                        OR
                        participantflag IS NULL
                    )
                    ";
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));
        assert(is_array($res));
        
        if(count($res) > 0)
        {
            return $res[0];
        }
        
        return array();
    }
        
    static public function GetBattleReportByID($reportID)
    {
        assert(is_int($reportID) && $reportID>0);
        
        $common = self::getCommon();        
        
        $sql = "SELECT *
                FROM ds_battlereport
                WHERE
                    id_battlereport = ".$reportID;      
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));
        assert(is_array($res) && count($res)>0);
        
        return $res[0];
    }
    
    static public function InsertEmptyBattleReport(DE_Battle $battle, $participantFlag)
    {
        assert($participantFlag == 'A' || $participantFlag == 'D');
        
        $common = self::getCommon();        
        
        $sql = "select nextval('public.ds_battlereport_id_battlereport_seq') as id";
        $res = $common->getResultArray ( "", $sql, "", "", "" );    
        assert(empty($common->errormsg) && count($res) == 1);                   
        
        $id = (int)$res[0]['id'];
        assert($id > 0);    
        
        $sql = "INSERT INTO ds_battlereport
                (id_battlereport, id_battle, report, participantflag)
                VALUES
                ($id, ".$battle->getID().", '', '$participantFlag')";       
        $res = $common->executeQuery ($sql);                
        assert(empty($common->errormsg));   
        
        return $id;
    }
    
    static public function InsertBattleReport(DE_BattleReport $report)
    {
        $common = self::getCommon();
        
        $flag = $report->getParticipantFlag();
        if(empty($flag))
        {
            $flag = 'null';
        } else {
            $flag = "'$flag'";
        }

        $multyLang = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_LOG_MULTY_LANG');
        if ($multyLang == 1) {
            $reportLog = serialize($report->getReport());
        } else {
            $reportLog = $report->getReport();
        }
        
        
        $sql = "
            INSERT INTO ds_battlereport
                (id_battle, report, participantflag)
            VALUES
                (".$report->getBattle()->getID().", '".$reportLog."', ".$flag.")";

//SU_DebugTools::LogAll($sql, 'Annita.log');
        $res = $common->executeQuery($sql);             
        assert(empty($common->errormsg));
    }
    
    static public function UpdateBattleReport(DE_BattleReport $report)
    {
        $common = self::getCommon();        

        $multyLang = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_LOG_MULTY_LANG');

        if ($multyLang == 1) {
            $reportLog = serialize($report->getReport());
        } else {
            $reportLog = $report->getReport();
        }
        
        $sql = "UPDATE ds_battlereport
                SET 
                    report = '".$reportLog."'
                WHERE id_battlereport = '".$report->getID()."'";
//SU_DebugTools::LogAll($sql, 'Annita.log');    
        $res = $common->executeQuery($sql);             
        assert(empty($common->errormsg));   
                
        return true;
    }
    
    static public function GetBattleActionsByLang($langID)
    {
        assert(is_int($langID) && $langID>0);
        
        $common = self::getCommon();        
        
        $sql = "SELECT *
                FROM
                    ds_battleaction AS ba JOIN ds_localizedtextlanguage AS ltl ON
                        (ba.id_localizedname = ltl.id_localizedtext)
                WHERE
                    ltl.id_language = ".$langID;
        $res = $common->getResultArray (self::FormMark(__METHOD__, $langID), $sql, "", "", "NO_CACHE" );    
        assert(empty($common->errormsg));
        assert(is_array($res) && count($res)>0);
        
        return $res;
    }
    
    static public function GetBattleRoundsByBattleID($battleID)
    {
        assert(is_int($battleID) && $battleID>0);
        
        $common = self::getCommon();        
        
        $sql = "SELECT *
                FROM ds_battleround
                WHERE id_battle = ".$battleID."
                ORDER BY roundnumber";      
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));
        assert(is_array($res));
        
        return $res;
    }
    
    static public function GetBattleRoundResults($roundID)
    {
        assert(is_int($roundID) && $roundID>0);
        
        $common = self::getCommon();        
        
        //Сперва поищем в базе
        $sql = "SELECT *
                FROM ds_battleresult
                WHERE id_battleround = ".$roundID;      
        $res = $common->getResultArray (parent::FormMark(__METHOD__, $roundID), $sql, "", "", "GLOBAL_CACHE");  
        assert(empty($common->errormsg));
        assert(is_array($res) /*&& count($res)>0*/);
        
        //Теперь давайте поищем что есть в памяти
        $roundResultList = GB_SingletonObjectStorage::GetObjectsOfClass('DE_BattleRoundResult', true);  
                    
        foreach($roundResultList as $k=>$roundResult)
        {
            if($roundResult->getRoundID() == $roundID)
            {
                $res[]['id_battleresult'] = $roundResult->getID();
            }
        }
        
        return $res;
    }
    
    static public function UpdateBattleAttribute ($battleHasAttributeID, $value)
    {       
        assert(is_float($value));
        assert($battleHasAttributeID>0);

        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_battlehasattribute
            SET value = '".$value."'
            WHERE 
                id_battlehasattribute = ".$battleHasAttributeID;
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetBattleAtrributes($battleID)
    {
        assert(is_int($battleID) && $battleID>0);
        
        $common = self::getCommon();        

        $sql = "
                SELECT 
                    bhat.id_battlehasattribute,
                    bhat.value,
                    at.typename,
                    an.attributename
                FROM
                    ds_battle b
                    INNER JOIN ds_battlehasattribute bhat ON (b.id_battle = bhat.id_battle)
                    INNER JOIN ds_attribute a ON (bhat.id_attribute = a.id_attribute)
                    INNER JOIN ds_attributetype at ON (a.id_attributetype = at.id_attributetype)
                    INNER JOIN ds_attributename an ON (a.id_attributename = an.id_attributename)
                WHERE
                    b.id_battle = ".$battleID;
        $res = $common->getResultArray (self::FormMark(__METHOD__, $battleID), $sql, "", "", "NO_CACHE" );  
        assert(empty($common->errormsg));
        assert(is_array($res)); // && count($res)>0
        
        return $res;
    }
    
    static public function CreateBattleAttributes($attributeTypeName, $attributeName, $value, $battleID)
    {
        assert ( is_string($attributeTypeName) && !empty($attributeTypeName));
        assert ( is_string($attributeName) && !empty($attributeName));
        assert ( $value >= 0);
        assert( is_int($battleID) && count($battleID)>0);

        $common = self::getCommon();
        
        $attributeID = ST_Attributes::Create()->getAttributeIDByTypeAndName($attributeTypeName, $attributeName);
        
        $id = self::getNextSeqVal($common, 'ds_battlehasattribute_id_battlehasattribute_seq');
        assert(is_int($id) && $id > 0);
        
        $sql = "
                INSERT INTO ds_battlehasattribute               
                (id_battlehasattribute, id_battle, value, id_attribute) 
                VALUES 
                (
                        ".$id.",
                        ".$battleID.",
                        ".$value.",
                        ".$attributeID."
                    );";        
        $res = $common->executeQuery($sql);             
        assert(empty($common->errormsg));                       
        
        return $id;     
    }
    
    static public function GetBattlesToUserInstance($userInstanceID, $fromDate = null)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);       
        assert(is_null($fromDate) || is_string($fromDate));
        $common = self::getCommon();        

        if(!is_null($fromDate))
        {   
            $whereInjection = "AND b.battlestartdate > '".$fromDate."'";
        }else{
            $whereInjection = '';
        }
        
        $sql = "SELECT 
                    b.id_battle
                FROM
                    ds_battle as b
                    INNER JOIN ds_battleparticipant as bp ON (bp.id_battle = b.id_battle)
                WHERE 
                    bp.id_userinstance = ".$userInstanceID."
                    ".$whereInjection."
                ORDER BY b.battlestartdate DESC";       
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));       
        
        return $res;
    }
    
    static public function NotEndedBattlesOnComb(DE_Comb $comb)
    {
        $common = self::getCommon();        

        $sql = "SELECT *
                FROM ds_battle as b
                WHERE
                    b.id_battlecomb = ".$comb->getID()."
                    AND
                    b.battleenddate is null";       
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function NotEndedBattlesWithUser($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);       

        $common = self::getCommon();
        
        $sql = "                    
                SELECT
                        *
                FROM
                    ds_userinstance as ui
                INNER JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
                INNER JOIN ds_battle b ON (c.id_comb = b.id_battlecomb OR c.id_comb = b.id_attackercomb)
                WHERE
                   ui.id_userinstance = ".$userInstanceID."
                    AND
                   b.battleenddate is null;
                ";      
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" );     
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function GetBattleOppenentsToUserInstance($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                DISTINCT u.username
            FROM
                ds_battle as b
                INNER JOIN ds_battleparticipant as bp ON (bp.id_battle = b.id_battle)
                INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = bp.id_userinstance)
                INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
            WHERE 
                b.id_battle IN 
                (
                    SELECT
                        bp.id_battle
                    FROM
                        ds_battleparticipant as bp
                    WHERE
                        bp.id_userinstance = ".$userInstanceID."
                )
                AND
                bp.id_userinstance != ".$userInstanceID."
                AND
                b.battlestartdate > NOW() - '".DE_WorldSettings::CreateForCurentWorld()->get("BATTLE_FULL_STORE_PERIOD_DAYS")." DAY'::interval
            ORDER BY u.username
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));       
        
        return $res;
    }
    
    static public function GetBattleCombsToUserInstance($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                DISTINCT r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber as coords
            FROM
                ds_battle AS b
                INNER JOIN ds_comb AS c ON (c.id_comb = b.id_battlecomb)
                INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = c.id_combcomplex)
                INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
                INNER JOIN ds_battleparticipant as bp ON (bp.id_battle = b.id_battle)
            WHERE
                bp.id_userinstance = ".$userInstanceID."
                AND
                b.battlestartdate > NOW() - '".DE_WorldSettings::CreateForCurentWorld()->get("BATTLE_FULL_STORE_PERIOD_DAYS")." DAY'::interval
            ORDER BY r.ringnumber||'.'||cc.complexnumber||'.'||c.combnumber
            ";
        
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));       
        
        return $res;
    }
    
    static public function GetBattleDatesToUserInstance($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                array_to_string(
                    ARRAY
                    (
                        SELECT
                            t.month||':'||t.min_day||'-'||t.max_day AS date
                        FROM
                            (
                                SELECT
                                    DISTINCT TO_CHAR(b.battlestartdate, 'MON') as month,
                                    EXTRACT(MONTH FROM b.battlestartdate) as month_num,
                                    EXTRACT(YEAR FROM b.battlestartdate) as year_num,
                                    MIN(EXTRACT(DAY FROM b.battlestartdate)) as min_day,
                                    MAX(EXTRACT(DAY FROM b.battlestartdate)) as max_day
                                FROM
                                    ds_battle AS b
                                    INNER JOIN ds_battleparticipant as bp ON (bp.id_battle = b.id_battle)
                                WHERE
                                    bp.id_userinstance = 4
                                    AND
                                    b.battlestartdate > NOW() - '".DE_WorldSettings::CreateForCurentWorld()->get("BATTLE_FULL_STORE_PERIOD_DAYS")." DAY'::interval
                                GROUP BY b.battlestartdate
                            ) as t
                        ORDER BY t.year_num, t.month_num    
                    ),
                    ','
                ) as dates
            ";
        
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));       
        
        return $res[0]['dates'];
    }
    
    static public function GetLastBattleDayToUserInstance($userInstanceID)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                COALESCE(MAX(EXTRACT(DAY FROM b.battlestartdate - now() + '".(DE_WorldSettings::CreateForCurentWorld()->get("BATTLE_FULL_STORE_PERIOD_DAYS")-1)." DAY'::interval)), ".DE_WorldSettings::CreateForCurentWorld()->get("BATTLE_FULL_STORE_PERIOD_DAYS").") as max_day
            FROM
                ds_battle AS b
                INNER JOIN ds_battleparticipant as bp ON (bp.id_battle = b.id_battle)
            WHERE
                bp.id_userinstance = $userInstanceID
            ";
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));       
        
        $day = (int)$res[0]['max_day'];
        
        if($day < 1)
        {
            $day = 1;
        }
        
        return $day;
    }
    
    static public function GetBattlesToUserInstanceForBattleOverview($userInstanceID, $fromDate = null, $toDate = null, $winner = null, $ownComb = null, $oponents = null, $battleCombs = null)
    {
        assert(is_int($userInstanceID) && $userInstanceID>0);       
        assert(is_null($fromDate) || is_string($fromDate));
        $common = self::getCommon();        

        $whereInjection = '';
        
        if(!is_null($fromDate))
        {   
            $whereInjection .= " AND b.battlestartdate >= '".$fromDate."' ";
        }
        
        if(!is_null($toDate))
        {   
            $whereInjection .= " AND b.battlestartdate <= '".$toDate."' ";
        }
        
        if(!is_null($winner))
        {
            if(!$winner)
            {
                $whereInjection .= " AND NOT bp.iswinner ";
            } else {
                $whereInjection .= " AND bp.iswinner ";
            }
        }
        
        
        if(!is_null($ownComb))
        {
            if(!$ownComb)
            {
                $whereInjection .= " AND NOT bp.iscombowner ";
            } else {
                $whereInjection .= " AND bp.iscombowner ";
            }
        }
        
        if(!is_null($oponents))
        {
            assert(is_array($oponents));
            $whereInjection .= " AND u.username IN ('".implode("','", $oponents)."')";
        }
        
        if(!is_null($battleCombs))
        {
            assert(is_array($battleCombs));
            $whereInjection .= " AND b.id_battlecomb IN ('".implode("','", $battleCombs)."')";
        }
        
        $sql = "SELECT 
                    DISTINCT
                    b.id_battle,
                    (
                        SELECT count(*)
                        FROM ds_battleround as br
                        WHERE br.id_battle = b.id_battle
                    ) as roundscount,
                    b.battlestartdate
                FROM
                    ds_battle as b
                    INNER JOIN ds_battleparticipant as bp ON (bp.id_battle = b.id_battle)
                    INNER JOIN ds_battleparticipant as bp_opp ON (bp_opp.id_battle = b.id_battle)
                    INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = bp_opp.id_userinstance)
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
                WHERE 
                    bp.id_userinstance = ".$userInstanceID."
                    AND
                    bp_opp.id_userinstance != ".$userInstanceID."
                    ".$whereInjection."
                ORDER BY b.battlestartdate DESC";
        $res = $common->getResultArray ("", $sql, "", "", "NO_CACHE" ); 
        assert(empty($common->errormsg));       
        
        return $res;
    }
    
    static public function GetCurrentPeacePeriod($chekDate = "")
    {       
        $common = self::getCommon();
        
        if (empty($chekDate)) {
            $chekDate = "now()";
        } else {
            $chekDate = "'$chekDate'";
        }
        
        $sql = "SELECT *
                FROM ds_peacepreiods
                WHERE 
                    start_date < $chekDate
                    AND
                    end_date > $chekDate
                    AND
                    id_world = ".DE_World::CreateForCurrentWorld()->getID();
        $res = $common->getResultArray ("", $sql, "", "", "");      
        assert(empty($common->errormsg));       
        
        if(count($res) == 0)
        {
            return false;
        }else{
            $result = array
            (
                'START_DATE' => GB_TypeFactory::CreateBase('GB_Date', $res[0]['start_date']),
                'END_DATE' => GB_TypeFactory::CreateBase('GB_Date', $res[0]['end_date'])
            );
            
            return $result;
        }
                        
    }
    
    static public function InsertItoBattleSystemLog($battleID, $UIID, $msg)
    {       
        $common = self::getCommon();
        
        settype($battleID, 'integer');
        assert($battleID > 0);

        //Может быть и 0 елси неудалоьс затукать какой именно UUIID
        settype($UIID, 'integer');
        assert($UIID >= 0);
        
        $date = GB_TypeFactory::CreateBase('GB_Date', time());
        
        $msg = addslashes($msg);
        $sql = "INSERT INTO ds_battlesystemreport
                (id_battle, add_date, id_userinstance, log_record)
                VALUES
                ($battleID, '".$date->getPgFormat()."', $UIID, E'$msg')";
        $res = $common->getResultArray ("", $sql, "", "", "");      
        assert(empty($common->errormsg));                               
    }
    
    static public function GetAwerArmyPowerInWorld(DE_World $world)
    {       
        $common = self::getCommon();
        
        $sql = "SELECT average 
                FROM subqry_armyranking_average 
                WHERE id_world = ".$world->getID();
        $res = $common->getResultArray (self::FormMark(__METHOD__, $world->getID()), $sql, "FULL_PREFIX", 'GLOBAL_CACHE');      
        assert(empty($common->errormsg) && count($res) == 1);       
        
        return $res[0]['average'];                      
    }
    
    static public function UserInstanceHaveMessagesInRedButton(DE_UserInstance $userInstance)
    {
        $common = self::getCommon();
        
        $sql = "SELECT *
                FROM ds_unitcontaineraction AS uca
                WHERE 
                    uca.id_userinstance_from = ".$userInstance->getUserInstanceID()." 
                    OR 
                    uca.id_userinstance_to = ".$userInstance->getUserInstanceID()."
                LIMIT 1";
        $res = $common->getResultArray ("", $sql);      
        assert(empty($common->errormsg));       
        
        if(count($res) == 0)
        {
            return false;
        }else{
            return true;
        }
    }
    
    static public function GetRedButton(DE_UserInstance $userInstance,  $requestCombsArray, $requestMaxDate, $requestTypes, $requestUserInstance)
    {
        assert(is_array($requestCombsArray));
        assert(($requestMaxDate instanceof GB_Date) || is_null($requestMaxDate));
        assert(is_array($requestTypes));
        assert(($requestUserInstance instanceof DE_UserInstance) || is_null($requestUserInstance));
        
        $partsSQL = array();        

        if(!is_null($requestUserInstance))
        {
            $partsSQL[] = "
                (
                    uca.id_userinstance_from = ".$requestUserInstance->getID()."
                    OR
                    uca.id_userinstance_to = ".$requestUserInstance->getID()."
                )
                ";
        }
        
        if(count($requestCombsArray) > 0)
        {
            $tmp = array();
            foreach($requestCombsArray as $k=>$v)
            {
                $tmp[] = "  uca.id_startcomb = ".$v." OR uca.id_targetcomb = ".$v." ";  
            }
            
            $partsSQL[] = implode(' OR ', $tmp);
        }
        
        if(!is_null($requestMaxDate))
        {
            $partsSQL[] = "
                uca.actionendtime <= '".$requestMaxDate->getPgFormat()."'
                ";
        }
        
        if(count($requestTypes) > 0)
        {
            $tmp = array();
            foreach($requestTypes as $k=>$v)
            {
                $tmp[] = " ucat.actiontypename = '".$v."' ";
            }
            
            $partsSQL[] = " ( ".implode(' OR ', $tmp)." ) ";
        }
        
        $common = self::getCommon();
        
        if(count($partsSQL) > 0)
        {
            $sqlAddon = " AND ".implode(' AND ', $partsSQL); 
        }else{
            $sqlAddon = '';
        }
        
        
        $sql = "
            SELECT *
            FROM
                ds_unitcontaineraction AS uca
                INNER JOIN ds_unitcontainer AS uc ON (uc.id_unitcontainer = uca.id_unitcontainer)
                INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
            WHERE
                (
                    uca.id_userinstance_from = ".$userInstance->getID()."
                    OR
                    uca.id_userinstance_to = ".$userInstance->getID()."
                )
                $sqlAddon
                ";  
//SU_DebugTools::LogAll($sql);
        $res = $common->getResultArray ("", $sql);      
        assert(empty($common->errormsg));
        
        return $res;
    }
}


?>
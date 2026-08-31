<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/03/20 12:52:25 $';

class DS_Freez extends DS_Base
{
    static public function GetNotEndedVocations(DE_UserInstance $userInstance)
    {       
        $common = self::getCommon();
        
        $sql = "SELECT *
                FROM ds_vocationmode
                WHERE 
                    (
                        enddate IS NULL
                        OR
                        enddate > now()
                    )
                    AND
                    id_userinstance = ".$userInstance->getID();
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));       
        
        if(count($res) == 0)
        {
            return $res;
        }else{
            return $res[0];
        }               
    }       
        
    static public function StartVacationMode(DE_UserInstance $userInstance, $password)
    {                   
        $common = self::getCommon();
        
        $sql = "INSERT INTO ds_vocationmode
                (id_userinstance, startdate, password)
                VALUES
                (".$userInstance->getID().", now(), '".$password."')";              
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));                       
    }   
    
    static public function DeleteVacationLicenzion(DE_UserInstance $userInstance)
    {                   
        $common = self::getCommon();
        
        $sql = "DELETE FROM ds_buildingsalowedtouserinstance
                WHERE 
                    id_building = 65
                    AND
                    id_userinstance = ".$userInstance->getID()."";              
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));                       
    }   
    
    static public function DeleteVacationBuilding(DE_UserInstance $userInstance)
    {                   
        $common = self::getCommon();
        
        
        $sql = "SELECT *
                FROM ds_attachedbuilding
                WHERE 
                    id_building = 65
                    AND
                    id_comb IN (
                        SELECT id_comb
                        FROM ds_comb
                        WHERE id_userinstance = ".$userInstance->getID()."
                    )";
        $attachedBuildingData = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($attachedBuildingData) > 0);       
        
        key_exists('id_attachedbuilding', $attachedBuildingData[0]);
        $attachedBuildingID = (int) $attachedBuildingData[0]['id_attachedbuilding'];
        assert($attachedBuildingID > 0);
        
        //на всякий случай зачистка. етсь такие кривые места где есть прилепленный кривой ивент
        $sql = "DELETE FROM ds_attachedbuildingbindeventqueue
                WHERE id_attachedbuilding = $attachedBuildingID";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        $sql = "DELETE FROM ds_attachedbuilding
                WHERE id_attachedbuilding = $attachedBuildingID";               
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));                       
    }   
    
    static public function EndVacationMode(DE_UserInstance $userInstance)
    {                   
        $common = self::getCommon();
        
        $sql = "UPDATE ds_vocationmode
                SET enddate = now()
                WHERE id_userinstance = ".$userInstance->getID()."";                
        $res = $common->getResultArray ("", $sql, "", "", "");      
        assert(empty($common->errormsg));                       
        
        GL_IsUserInVocationMode::ResetCache($userInstance);
    }   
    
    /**
     * Список боев незаконченных с участием данного инстанса
     *
     * @param DE_UserInstance $userInstance
     * @return array
     */
    static public function GetNotEndedBattles(DE_UserInstance $userInstance)
    {       
        $common = self::getCommon();
        
        //Првоеряем есть ли бои не законченые с днным пользователем
        $sql = "SELECT *
                FROM
                    ds_battle AS b
                    INNER JOIN ds_battleparticipant AS bp ON (bp.id_battle = b.id_battle)
                WHERE
                    b.battleenddate IS NULL
                    AND
                    bp.id_userinstance = ".$userInstance->getID();
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }   
    
    /**
     * Список армий которые двигаються для атаки на данную соту или с данной соты
     *
     * @param DE_UserInstance $userInstance
     * @param GB_Date $chekDate
     * @return array
     */
    static public function GetListOfEndedMooveToAttackArmys(DE_UserInstance $userInstance, GB_Date $chekDate)
    {       
        $common = self::getCommon();
        
        //Првоеряем есть ли бои не законченые с днным пользователем
        $sql = "SELECT *
                FROM
                    ds_unitcontaineraction AS uca
                    INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
                WHERE
                    (
                        uca.id_targetcomb IN
                        (
                            SELECT id_comb
                            FROM ds_comb AS c
                            WHERE c.id_userinstance = ".$userInstance->getID()."
                        )
                        OR
                        uca.id_startcomb IN
                        (
                            SELECT id_comb
                            FROM ds_comb AS c
                            WHERE c.id_userinstance = ".$userInstance->getID()."
                        )
                    )
                    AND
                    ucat.actiontypename = 'MOVE_TO_ATTACK'
                    AND
                    uca.actionendtime <= '".$chekDate->getPgFormat()."'";

        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }   
    
    /**
     * Выборка всех армий коорые связанны со мной Т.е. едут от меня
     * или ко мне но не те что возвращаються
     *
     * @param DE_UserInstance $userInstance
     * @return array
     */
    static public function AllArmysInActionsWithMy(DE_UserInstance $userInstance, $withCapture = false)
    {       
        $common = self::getCommon();
        
        $and = '';
        if ($withCapture == true) {
            $and = "AND is_capture = 'f'";
        }
        
        $sql = "SELECT
                    uc.id_unitcontainer,
                    ucat.actiontypename
                FROM
                    ds_unitcontainer AS uc
                    INNER JOIN ds_unitcontaineraction AS uca ON (uca.id_unitcontainer = uc.id_unitcontainer)                
                    INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
                WHERE
                    (
                        uca.id_startcomb IN
                        (
                            SELECT id_comb
                            FROM ds_comb
                            WHERE id_userinstance = ".$userInstance->getID()."
                            $and
                        )
                        OR
                        uca.id_targetcomb IN
                        (
                            SELECT id_comb
                            FROM ds_comb
                            WHERE id_userinstance = ".$userInstance->getID()."
                            $and
                        )
                    )
                    AND
                    ucat.actiontypename IN ('MOVE_TO_DEFEND', 'IN_DEFEND', 'MOVE_TO_ATTACK')";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }   

/////////////********** Power Shild ****************//////////////////////  
    
    static public function GetPowerShildRecord($UIID)
    {       
        assert(is_int($UIID));
        
        $common = self::getCommon();        
        
        $sql = "
            SELECT *
            FROM
                ds_powershild_on_userinstance AS pou
                INNER JOIN ds_powershild_current_status AS pscs ON (pscs.id_userinstance = pou.id_userinstance)
                INNER JOIN ds_powershild_status_name AS psn ON (psn.id_powershildstatusname = pscs.id_powershildstatusname)
            WHERE
                pou.id_userinstance = ".$UIID;
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) < 2);
        
        if(count($res) > 0)
        {
            return $res[0]; 
        }else{
            return false;   
        }       
    }   
    
    static public function InsertFirstPowerShildRecord($UIID)
    {
        assert(is_int($UIID) && $UIID > 0);
        
        $common = self::getCommon();
        
        $sql = "
            INSERT INTO ds_powershild_on_userinstance
            (id_userinstance, last_work_end_date, last_work_length)
            VALUES
            (".$UIID.", '1979-08-21 10:15:00', 1)";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        $sql = "
            INSERT INTO ds_powershild_current_status
            (id_userinstance, id_powershildstatusname, start_date, end_date)
            VALUES
            (".$UIID.", 4, '1979-08-21 10:15:00', '1979-08-21 10:16:00')";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }
    
    static public function MarkPowerShildLastWorkDate($UIID, $length, GB_Date $endDate)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($length) && $length > 0);
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_powershild_on_userinstance
            SET
                last_work_end_date = '".$endDate->getPgFormat()."',
                last_work_length = $length
            WHERE 
                id_userinstance = ".$UIID."";           
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }
    
    static public function GetPowerShildNextStatus($UIID, $isAuto = true)
    {
        assert(is_int($UIID) && $UIID > 0);     
        
        if($isAuto)
        {
            $isAutoPG = 't';
        }else{
            $isAutoPG = 'f';
        }
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                psst.*,
                fsn.statusname AS fromstatusname,
                tsn.statusname AS tostatusname
            FROM
                ds_powershild_current_status AS pscs
                INNER JOIN ds_powershild_status_cc2 AS psst ON (psst.id_fromstatus = pscs.id_powershildstatusname)
                INNER JOIN ds_powershild_status_name AS fsn ON (fsn.id_powershildstatusname = psst.id_fromstatus)
                INNER JOIN ds_powershild_status_name AS tsn ON (tsn.id_powershildstatusname = psst.id_tostatus)
            WHERE
                pscs.id_userinstance = ".$UIID."
                AND
                psst.isauto = '$isAutoPG'";         
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
        
    static public function ChangePowerShildStatusToNext ($UIID, GB_Date $startDate, GB_Date $endDate, $isAuto)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_bool($isAuto));       
        
        if($isAuto)
        {
            $isAutoPG = 't';
        }else{
            $isAutoPG = 'f';
        }

        assert($startDate->value < $endDate->value);
        
        $common = self::getCommon();
                
        //Апдейтим статус
        $sql = "
            UPDATE ds_powershild_current_status
            SET
                id_powershildstatusname = (
                    SELECT
                        psst.id_tostatus
                    FROM
                        ds_powershild_current_status AS pscs
                        INNER JOIN ds_powershild_status_cc2 AS psst ON (psst.id_fromstatus = pscs.id_powershildstatusname)
                    WHERE
                        pscs.id_userinstance = ".$UIID."
                        AND
                        psst.isauto = '".$isAutoPG."'
                ),
                start_date = '".$startDate->getPgFormat()."',
                end_date = '".$endDate->getPgFormat()."'
            WHERE 
                id_userinstance = ".$UIID."";           
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
    
    /**
     * Выборка текущего лидера по рейтингу
     *
     * @return unknown
     */
    static public function GetCurrentScoreTop($worldID, $limit)
    {               
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::getCommon();
                
        //Апдейтим статус
        $sql = "
            SELECT fr.id_userinstance
            FROM ds_finalranking AS fr
            WHERE id_world = $worldID
            ORDER BY fr.thefinal DESC
            LIMIT $limit";           
        $res = $common->getResultArray (self::FormMark(__METHOD__), $sql, "3600", "FUll_PREFIX", "GLOBAL_CACHE");
        assert(empty($common->errormsg));

        $out = array();
        foreach ($res as $row) $out[] = $row['id_userinstance'];
        return $out;
    }
    
    static public function GetAverRatingForUIIDs($UIIDs)
    {           
        $common = self::getCommon();
                
        //Апдейтим статус
        $sql = "
            SELECT round(sum(rh.thefinal) / count(*)) as averrate
            FROM ds_finalrankinghistory AS rh
            WHERE
                rh.id_userinstance in (".implode(',', $UIIDs).")
                AND
                rh.adddate > (now() - '1 DAY'::interval)";          
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) > 0);
        
        return (int)$res[0]['averrate'];
    }
        
    
    /************* Здвижка ивентов во времени *************************/
    
    static public function MoveEventInTime($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_eventqueue
            SET
                eventstarttime = eventstarttime + '$delta SECOND'::interval,
                eventendtime = eventendtime + '$delta SECOND'::interval
            WHERE
                istrash = false
                AND
                id_eventqueue IN
                (
                    SELECT id_eventqueue
                    FROM ds_eventparticipant
                    WHERE id_userinstance = $UIID
                )";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }
    
    
    static public function MoveUnitProductionInTime($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_unitinproduction
            SET
                starttime = starttime + '$delta SECOND'::interval,
                endtime = endtime + '$delta SECOND'::interval
            WHERE
                id_attachedbuilding IN
                (
                    SELECT id_attachedbuilding
                    FROM ds_attachedbuilding
                    WHERE
                        id_comb IN
                        (
                            SELECT id_comb
                            FROM ds_comb
                            WHERE id_userinstance = $UIID
                        )
                )";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }
    
    static public function MoveScienceInTime($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_userhasscience
            SET
                starttime = starttime + '$delta SECOND'::interval,
                endtime = endtime + '$delta SECOND'::interval
            WHERE
                id_userinstance = 1";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
    
    static public function MoveArchiologyInTime($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_archeologicalteam
            SET
                starttime = starttime + '$delta SECOND'::interval,
                endtime = endtime + '$delta SECOND'::interval
            WHERE
                id_comb IN
                (
                    SELECT id_comb
                    FROM ds_comb
                    WHERE id_userinstance = $UIID
                 )";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
    
    static public function MoveAttachedBuildingsInTime($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_attachedbuilding
            SET
                -- sstarttime = starttime + '$delta SECOND'::interval,
                endtime = endtime + '$delta SECOND'::interval
            WHERE
                id_attachedbuilding IN
                (
                    SELECT id_attachedbuilding
                    FROM ds_attachedbuilding
                    WHERE
                        id_comb IN
                        (
                            SELECT id_comb
                            FROM ds_comb
                            WHERE id_userinstance = $UIID
                        )
                )";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
        
    static public function MoveSpyZondAction($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        $sql = "
            UPDATE ds_spy_group_action
            SET
                end_time = end_time + '$delta SECOND'::interval
            WHERE
                id_comb_from IN
                (
                        SELECT id_comb
                        FROM ds_comb
                        WHERE id_userinstance = $UIID
                )";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
    
    static public function MoveArmyReturnInTime($UIID, $delta)
    {
        assert(is_int($UIID) && $UIID > 0); 
        assert(is_int($delta) && $delta > 0);   
        
        $common = self::getCommon();
        
        //Првоеряем есть ли бои не законченые с днным пользователем
        $sql = "
        UPDATE ds_unitcontaineraction
            SET
                actionstarttime = actionstarttime + '$delta SECOND'::interval,
                actionendtime = actionendtime + '$delta SECOND'::interval
            WHERE
                id_unitcontainer IN
                (
                     SELECT id_unitcontainer
                FROM
                    ds_unitcontaineraction AS uca
                    INNER JOIN ds_unitcontaineractiontype AS ucat ON (ucat.id_unitcontaineractiontype = uca.id_unitcontaineractiontype)
                WHERE
                    (
                        uca.id_targetcomb IN
                        (
                            SELECT id_comb
                            FROM ds_comb AS c
                            WHERE c.id_userinstance = ".$UIID."
                        )
                        OR
                        uca.id_startcomb IN
                        (
                            SELECT id_comb
                            FROM ds_comb AS c
                            WHERE c.id_userinstance = ".$UIID."
                        )
                    )
                    AND
                    ucat.actiontypename = 'MOVE_FROM'
                )";

        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }   
        
    /****************** Возаращаем ресурсы с торгов *************************/
    
    static public function GetUserTrades($UIID)
    {
        assert(is_int($UIID) && $UIID > 0);
        
        $common = self::getCommon();
        
        $sql = "
                SELECT *
                FROM
                    ds_trade AS t
                    INNER JOIN ds_tradebindeventqueue AS tbq ON (tbq.id_trade = t.id_trade)
                WHERE
                    t.id_seller = $UIID";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function  GetCategoryNameByID($categoryID)
    {
        assert((int)$categoryID > 0);
        
        $common = self::getCommon();
        
        $sql = "SELECT categoryname
                FROM ds_tradecategory
                WHERE id_tradecategory = ".$categoryID;
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg) && count($res) == 1);
        
        return $res[0]['categoryname'];
    }
    
    static public function InsertArtefact($combID, $artefactID)
    {
        assert((int)$combID > 0);
        assert((int)$artefactID > 0);
        
        $common = self::getCommon();
        
        $sql = "SELECT * FROM ds_combhasartefact WHERE id_artefact = $artefactID AND id_comb = $combID";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));   
        
        if(count($res) > 0)
        {
            return;
        }
        
        $sql = "insert into ds_combhasartefact
                ( id_artefact, id_comb )
                values 
                ( ".$artefactID.", ".$combID.")";       
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));       
    }
    
    static public function DeleteTrade($tradeID)
    {
        assert((int)$tradeID > 0);      
        
        $common = self::getCommon();

        $sql = "DELETE FROM ds_tradebindeventqueue WHERE id_trade = ".$tradeID;     
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));   
        
        $sql = "DELETE FROM ds_trade WHERE id_trade = ".$tradeID;       
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));       
    }
    
    static public function AllSpyGroupsInActionsWithMy(DE_UserInstance $userInstance)
    {
        $common = self::getCommon();
        
        $sql = "SELECT
                      sga.*,
                      c_from.id_userinstance as u_from,
                       c_to.id_userinstance as u_to
                FROM
                    ds_spy_group_action AS sga
                    INNER JOIN ds_spy_action_type AS sat ON (sat.id_spy_action = sga.id_spy_action)
                    LEFT JOIN ds_comb c_from ON (c_from.id_comb = sga.id_comb_from)
                    LEFT JOIN ds_comb c_to ON (c_to.id_comb = sga.id_comb_to)
                WHERE
                    
                     sat.action_name IN ('RESEARCH')
                     AND
                     (
                     c_from.id_userinstance = ".$userInstance->getID()."
                     OR
                     c_to.id_userinstance = ".$userInstance->getID().")";
        
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
}

?>

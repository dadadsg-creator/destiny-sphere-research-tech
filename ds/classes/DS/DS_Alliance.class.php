<?php $CVS_Version = '$Revision: 1.13 $ ; $Date: 2008/11/24 09:38:37 $';


/**
 * Статтический класс бибилотека запросов связанная с альянсами.
 */
class DS_Alliance extends DS_Base
{

    static public function GetAllAlliances($id_world)
    {
        $common = self::getCommon();
        
        $sql = "
                SELECT 
                    *
                FROM
                    ds_alliance
                WHERE
                    id_world = $id_world order by alliancetag";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE");     
        assert(empty($common->errormsg));       

            return $res;
    }
    
    static public function GetByUserInstanceID ($userinstaneID)
    {
        settype ($userinstaneID, 'integer');
        
        $common = self::getCommon();
        
        $sql = "
                SELECT 
                    id_alliance
                FROM
                    ds_userhasalliance
                WHERE
                    id_userinstance = ".$userinstaneID;
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE");     
        assert(empty($common->errormsg));       

        if ( sizeof ($res) > 0 ) {          
            return (int)$res[0]['id_alliance'];
        }else{
            return (int)0;
        }

    }
    
    static public function GetMemberExceptThis($allianceID, $userinstanceID)
    {
        settype ($userinstanceID, 'integer');
        assert ($userinstanceID > 0);
        
        settype ($allianceID, 'integer');
        assert ($allianceID > 0);
        
        $common = self::getCommon();
        
        $sql = "
                    SELECT
                        u.username,
                        uha.id_userinstance
                    FROM 
                        ds_userhasalliance uha,
                        ds_userinstance ui,
                        ds_user u
                    WHERE 
                         u.id_user = ui.id_user
                        AND 
                         ui.id_userinstance = uha.id_userinstance
                        AND
                         uha.id_alliance = $allianceID
                        AND 
                         ui.id_userinstance != $userinstanceID
                    ORDER BY joindate
                    LIMIT 1";
                    
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE");     
        assert(empty($common->errormsg));       

        return $res;
    }
    
    static public function IsUserInGlobslAlliance($userName)
    {
        return false;
        assert (!empty($userName));
        
        $common = self::getCommon();
        
        $sql = "SELECT  a.id_global_alliance
                    FROM ds_userhasalliance uha
                    LEFT JOIN ds_alliance a ON (a.id_alliance = uha.id_alliance)
                    LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = uha.id_userinstance)
                    LEFT JOIN ds_user u ON (u.id_user = ui.id_user) 
                                        
                    WHERE 
                        u.username = '".pg_escape_string($userName)."'
                    LIMIT 1";
                    
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE");     
        assert(empty($common->errormsg));       

        return $res[0]['id_global_alliance'];
    }
        
    static public function DeleteAlliance($allianceID)
    {
        settype ($allianceID, 'integer');
        assert ($allianceID > 0);
        
        $common = self::getCommon();
        
        $sql = "DELETE FROM 
                    ds_alliance
                WHERE 
                    id_alliance = $allianceID";
                    
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));       

        return $res;
    }
    
    static public function GetAllianceLeader($id_alliance)
    {
         $common = self::getCommon();       

         $sql = "select u.id_user, u.username, ua.id_userinstance FROM
ds_user u inner join ds_userinstance ui
ON ui.id_user=u.id_user inner join
ds_userhasalliancestatus ua ON
ua.id_userinstance = ui.id_userinstance
WHERE ua.id_alliance=$id_alliance";
        
      $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );  
      //echo $sql;
        assert(empty($common->errormsg));       

        return $res[0];    
    }
    
    static public function SetNewAllianceLeader($newleader, $allianceID)
    {
        settype ($newleader, 'integer');
        assert ($newleader > 0);
        
        settype ($allianceID, 'integer');
        assert ($allianceID > 0);
        
        $common = self::getCommon();
        
        $sql = "
                    INSERT INTO 
                        ds_userhasalliancestatus
                        ( id_status, id_alliance, id_userinstance )
                    VALUES 
                    ( 
                        (SELECT 
                            id_status 
                        FROM 
                            ds_useralliancestatus 
                        WHERE 
                            statusname = 'LEADER'
                        ), $allianceID, $newleader )";
                    
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));       

        return $res;
    }
    
    /**
    * @return Возвращяет сведения по альянсу    
    * @param ID альянсу $allianceID
    * @desc Возвращяет сведения по альянсу по ID
    */
    static public function GetByID ( $allianceID )
    {
        settype ($allianceID, 'integer');
        assert ($allianceID > 0);

        $common = self::getCommon();
        
        $sql = "SELECT 
                    a.*,
                    COALESCE(ahi.image_url, '') AS image_url
                FROM 
                    ds_alliance AS a
                    LEFT JOIN ds_alliance_has_image AS ahi USING (id_alliance)
                WHERE 
                    a.id_alliance = ".$allianceID;      
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );        
        assert(empty($common->errormsg));       

        if ( sizeof ($res) > 0 ) {          
            return $res[0];
        }else{
            return false;
        }
    }//END OF FUNCTION  
    
    static public function CountAlianceMembersByUserInstanceID($userInstanceID)
    {
        settype ($userInstanceID, 'integer');
        assert ($userInstanceID > 0);
        
        $common = self::getCommon();
        
        $sql = "SELECT count(*) as colvo
                FROM
                    ds_userhasalliance ua
                WHERE
                    ua.id_alliance IN (
                        SELECT ua2.id_alliance
                        FROM ds_userhasalliance ua2
                        WHERE ua2.id_userinstance = ".$userInstanceID."
                    )";     
        $res = $common->getResultArray ( self::FormMark(__METHOD__, $userInstanceID), $sql, "", "", "GLOBAL_CACHE" );       
        assert(empty($common->errormsg));       

        if ( sizeof ($res) > 0 ) {          
            return (int)$res[0]['colvo'];
        }else{
            return 0;
        }
    }
    
    /**
     * Получение информации о альянсе, по его имени
     * коммон передается снаружи, чтобы можно было использовать этот 
     * метод для проверки уникальности имени по всем мирам
     *
     * @param unknown_type $allianceName
     * @param DS_Common $common
     */
    static public function GetAllianceByName($allianceName, $worldId, DS_Common $common)
    {
        assert(!empty($allianceName));
        assert(is_int($worldId) && $worldId >= 0);
        
        if($worldId > 0)
        {
            $where = " AND id_world = ".$worldId;           
        } else {
            $where = "";
        }
        
        $sql = "
            SELECT
                *
            FROM
                ds_alliance
            WHERE
                alliancename = '".pg_escape_string($allianceName)."'
            ".$where;
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;    
    }
    
    /**
     * Получение информации об альянсе по его сокращению
     * коммон передается снаружи, чтобы можно было использовать этот 
     * метод для проверки уникальности сокращения по всем мирам
     *
     * @param unknown_type $allianceTag
     * @param DS_Common $common
     */
    static public function GetAllianceByTag($allianceTag, $worldId, DS_Common $common)
    {
        assert(!empty($allianceTag));
        assert(is_int($worldId) && $worldId >= 0);
        
        if($worldId > 0)
        {
            $where = " AND id_world = ".$worldId;           
        } else {
            $where = "";
        }
        
        $sql = "
            SELECT
                *
            FROM
                ds_alliance
            WHERE
                alliancetag = '[".pg_escape_string($allianceTag)."]'
            ".$where;
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;    
    }
    
    /**
     * Добавление юзера в альянс
     *
     * @param DE_Alliance $alliance
     * @param DE_Userinstance $userInstance
     */
    static public function AddUserToAlliance(DE_Alliance $alliance, DE_Userinstance $userInstance)
    {
        $common = self::getCommon();
        
        $sql = "
            SELECT
                add_user_to_alliance(".$userInstance->getUserInstanceID().", ".$alliance->getID().");
            ";
    
        $res = $common->executeQuery($sql);
        //$res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
    }
    
    
    /**
     * Отозвать армии, защищающие союзников по альянсу
     *
     * @param DE_Alliance $alliance
     * @param DE_Userinstance $userInstance
     */
    static public function GetUserArmyOnDefend(DE_Alliance $alliance, DE_Userinstance $userInstance)
    {
        $common = self::getCommon();
        
        //Смотрим, можно ли защищать сокомплексников
        $allowCombcomplexAttack = DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_COMBCOMPLEX_ATTACK');
        
        if ($allowCombcomplexAttack == 0) {
            $from = ' ds_comb c, ';
            $where = '  and c.id_comb = uc.id_basecomb
                        and co.id_combcomplex !=c.id_combcomplex ';
        } else {
            $from = '';
            $where = '';
        }
        
        $sql = "
                select uc.id_unitcontainer
                from ds_userhasalliance uha,
                $from
                ds_unitcontainer uc,
                ds_unitcontaineraction uca,
                ds_unitcontaineractiontype ucat,
                ds_comb co,
                ds_userhasalliance uha2
                where uha.id_alliance = ".$alliance->getID()."
                and uha.id_userinstance = uc.id_userinstance
                and uc.id_userinstance = ".$userInstance->getUserInstanceID()."
                and uc.id_unitcontainer = uca.id_unitcontainer
                and uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                and ucat.actiontypename = 'IN_DEFEND'
                and uca.id_targetcomb = co.id_comb
                and co.id_userinstance != ".$userInstance->getUserInstanceID()."
                and uha2.id_userinstance = co.id_userinstance
                and uha2.id_alliance = ".$alliance->getID()."
                $where
                    ";

        $res = $common->getResultArray ( "",$sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
/**
     * Отозвать армии союзников, защищающие члена альянса
     *
     * @param DE_Alliance $alliance
     * @param DE_Userinstance $userInstance
     */
    static public function GetArmyOnUserCombDefend(DE_Alliance $alliance, DE_Userinstance $userInstance)
    {
        $common = self::getCommon();
        
        //Смотрим, можно ли защищать сокомплексников
        $allowCombcomplexAttack = DE_WorldSettings::CreateForCurentWorld()->get('ALLOW_COMBCOMPLEX_ATTACK');
        
        if ($allowCombcomplexAttack == 0) {
            $from = ' ds_comb c, ';
            $where = '  and c.id_comb = uc.id_basecomb
                        and co.id_combcomplex !=c.id_combcomplex';
        } else {
            $from = '';
            $where = '';
        }
        
        $sql = "
                select 
                uc.id_unitcontainer
                from ds_userhasalliance uha,
                $from
                ds_unitcontainer uc,
                ds_unitcontaineraction uca,
                ds_unitcontaineractiontype ucat,
                ds_comb co
                where uha.id_alliance = ".$alliance->getID()."
                and uha.id_userinstance = uc.id_userinstance
                and uc.id_userinstance != ".$userInstance->getUserInstanceID()."
                and uc.id_unitcontainer = uca.id_unitcontainer
                and uca.id_unitcontaineractiontype = ucat.id_unitcontaineractiontype
                and ucat.actiontypename = 'IN_DEFEND'
                and uca.id_targetcomb = co.id_comb
                and co.id_userinstance = ".$userInstance->getUserInstanceID()."
                $where
                    ";
    
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res;
        
    }
    
    static public function ExpelAlliance(DE_Alliance $alliance, DE_Userinstance $userInstance)
    {
        $common = self::getCommon();
        
        $sql = "SELECT expel_alliance(".$userInstance->getUserInstanceID().", ".$alliance->getID().")";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function IsUserLeader($allianceId, $userInstance)
    {
        $common = self::getCommon();
        
        $sql = "select 
                    uha.id_alliance
                from 
                    ds_userhasalliance uha,
                    ds_userhasalliancestatus uhas,
                    ds_useralliancestatus uas
                where 
                    uha.id_alliance = uhas.id_alliance
                    and uhas.id_status = uas.id_status
                    and uas.statusname = 'LEADER'
                    and uha.id_alliance = $allianceId
                    and uhas.id_userinstance = $userInstance";      
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function IsUserInAlliance($allianceId, $userInstance)
    {
        $common = self::getCommon();
        
        $sql = "select uha.id_alliance
                from ds_userhasalliance uha
                where uha.id_alliance = $allianceId
                and uha.id_userinstance = $userInstance";
        
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetAllAllianceMembers(DE_Alliance $alliance)
    {
        $common = self::getCommon();
        
        $sql = "
            SELECT 
                id_userinstance
            FROM 
                ds_userhasalliance
            WHERE
                id_alliance = ".$alliance->getID()."                
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }

}//END OF CLASS


?>
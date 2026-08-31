<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_SearchUserInstance extends SI_Base 
{
    static public function GetUserDataByUIID($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT
                    ui.*,
                    
                    w.worldname,
                    w.world_identifier,
                    
                    u.id_user, 
                    u.id_language, 
                    u.username, 
                    u.email, 
                    u.registrationdate, 
                    u.vacationmode, 
                    u.admin, 
                    u.vacationstarttime, 
                    u.accountactive, 
                    u.selfdescription, 
                    u.password, 
                    u.time_delta, 
                    u.activate_mail,
                    
                    gm.value * 60 as turn_time,
                    
                    vm.startdate,
                    vm.enddate,
                    vm.password,
                    bau.id_building
                FROM
                    ds_userinstance AS ui
                    INNER JOIN ds_world AS w ON (w.id_world = ui.id_world)
                    INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
                    INNER JOIN ds_gamemodifier as gm on (gm.id_world = w.id_world)
                    
                    LEFT JOIN ds_vocationmode AS vm ON (vm.id_userinstance = ui.id_userinstance AND (vm.enddate IS NULL OR  vm.enddate > now()))
                    LEFT JOIN ds_buildingsalowedtouserinstance AS bau ON (bau.id_userinstance = ui.id_userinstance)
                WHERE
                    ui.id_userinstance = ".(int)$UIID."
                    AND
                    gm.modifiername = 'TURN_TIME'
                LIMIT 1";       
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);
        
        return $res[0];
    }
    
    static public function GetWorldData($worldID)
    {
        assert((int)$worldID > 0);  
//      assert(!empty($modifaerName));      
        
        $sql = "SELECT                      
                    *
                FROM                    
                    ds_world AS w
                    INNER JOIN ds_worldhastype AS wht ON (wht.id_world = w.id_world)
                    INNER JOIN ds_worldtype AS wt ON (wt.id_worldtype = wht.id_worldtype)
                WHERE
                    w.id_world = ".$worldID."";     
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);
        
        return $res[0];     
    }       
    
    static public function GetWorldModifarValue($worldID, $modifaerName)
    {
        assert((int)$worldID > 0);  
        assert(!empty($modifaerName));      
        
        $sql = "SELECT                      
                    gm.value
                FROM                    
                    ds_gamemodifier as gm
                WHERE
                    gm.id_world = ".$worldID."
                    AND
                    gm.modifiername = '".$modifaerName."'";     
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        assert(count($res) == 1);
        
        return $res[0]['value'];        
    }       
    
    static public function GetUserDataByName($userName)
    {
        assert(!empty($userName));          
        
        $sql = "SELECT * 
                FROM ds_user
                WHERE 
                    username = '".$userName."'";        
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
        
        if(count($res) == 0)
        {
            return array();
        }else{
            return $res[0];         
        }
    }   
    
    static public function GetUserDataByEmail($email)
    {
        assert(!empty($email));         
        
        $sql = "SELECT * 
                FROM ds_user
                WHERE 
                    email = '".$email."'";      
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
        
        if(count($res) == 0)
        {
            return array();
        }else{
            return $res[0];         
        }
    }
    
    static public function GetUserInstanceDataByUserID($userID, $worldID)
    {
        assert((int)$userID > 0);   
        assert((int)$worldID > 0);      
        
        $sql = "SELECT * 
                FROM ds_userinstance 
                WHERE 
                    id_user = ".(int)$userID."
                    AND
                    id_world = ".(int)$worldID;     
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
        
        return $res;        
    }   
    
    static public function GetReligion($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT * FROM  ds_userhasreligion WHERE id_userinstance = ".(int)$UIID;     
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }else{
            return null;
        }       
    }   
    
    static public function SetReligion($religionID, $UIID, $statusID)
    {
        settype($UIID, 'integer');
        assert($UIID > 0);      
        
        settype($religionID, 'integer');
        assert($religionID > 0);        
        
        settype($religionID, 'integer');
        assert($religionID > 0);        
        
        $sql = "INSERT INTO ds_userhasreligion 
                (id_religion, id_userinstance, id_status)
                VALUES
                ($religionID, $UIID, $statusID)";       
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
    }   
    
    static public function SetUserBounus($attributeID, $UIID, $value, $start_date, $end_date)
    {
        settype($UIID, 'integer');
        assert($UIID > 0);      
        
        settype($attributeID, 'integer');
        assert($attributeID > 0);       
        
        assert($value > 0); 
        
        assert(!empty($start_date));    
        assert(!empty($end_date));  
        
        $sql = "INSERT INTO ds_userhasbonus 
                (id_attribute, id_userinstance, value, start_date, end_date)
                VALUES
                ($attributeID, $UIID, $value, '$start_date', '$end_date')";     
    
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
    }   
    
    static public function SetArmyWeight($UIID, $army_weight_built, $army_weight_lost)
    {
        settype($UIID, 'integer');
        assert($UIID > 0);      
        
        settype($army_weight_built, 'float');
        // assert($army_weight_built >= 0);    
        
        settype($army_weight_lost, 'float');
        // assert($army_weight_lost >= 0);     
        
        $sql = "INSERT INTO ds_army_weight 
                (id_userinstance, army_weight_built, army_weight_lost)
                VALUES
                ($UIID, '".$army_weight_built."', '".$army_weight_lost."')";        
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
    }   
    
    static public function SetRanking($UIID, $ranking)
    {
        settype($UIID, 'integer');
        assert($UIID > 0);      
        
        settype($ranking['science_ranking'], 'float');
        assert($ranking['science_ranking'] >= 0);   
        
        settype($ranking['artefact_ranking'], 'float');
        assert($ranking['artefact_ranking'] >= 0);  
        
        settype($ranking['building_ranking'], 'float');
        assert($ranking['building_ranking'] >= 0);  
        
        settype($ranking['battle_ranking'], 'float');
        assert($ranking['battle_ranking'] >= 0);    
        
        settype($ranking['id_level'], 'integer');
        assert($ranking['id_level'] >= 0);  
        
        if ($ranking['id_level'] == 0) {
            $ranking['id_level'] = 1;
        }
        
        $sql = "INSERT INTO ds_ranking
                (id_userinstance, science_ranking, artefact_ranking, building_ranking, battle_ranking, id_level)
                VALUES
                ($UIID, '".$ranking['science_ranking']."', '".$ranking['artefact_ranking']."', '".$ranking['building_ranking']."', '".$ranking['battle_ranking']."', ".$ranking['id_level'].")";        
        $res = self::$common->getResultArray("", $sql, "", "", "");

        assert(empty(self::$common->errormsg));     
    }   
    
    static public function GetScinces($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT
                    *
                FROM
                    ds_userhasscience as uhs                    
                WHERE
                    uhs.id_userinstance = $UIID";       
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;        
    }
    
    static public function GetUserBonuses($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT * FROM ds_userhasbonus WHERE id_userinstance = ".(int)$UIID;     
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res) > 0)
        {
            return $res;
        }else{
            return null;
        }       
    }   
    
    static public function GetArmyWeight($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT * FROM ds_army_weight WHERE id_userinstance = ".(int)$UIID;      
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res) > 0)
        {
            return $res;
        }else{
            return null;
        }       
    }   

    static public function GetRanking($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT * FROM ds_ranking WHERE id_userinstance = ".(int)$UIID;      
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res) > 0)
        {
            return $res;
        }else{
            return null;
        }       
    }   

    static public function GetBattleXP($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT
                    *
                FROM
                    ds_battlexp                 
                WHERE
                    id_userinstance = $UIID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        if(count($res) > 0)
        {
            return $res[0];
        }else{
            return array('value' => 0, 'absolute_value' => 0);
        }       
    }
    
    static public function SettBattleXP($UIID, $value)
    {
        assert((int)$UIID > 0);
        assert(is_array($value));   
        
        $sql = "INSERT INTO ds_battlexp
                (id_userinstance, value, absolute_value)
                VALUES
                ($UIID, ".$value['value'].", ".$value['absolute_value'].")";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));     
    }
    
    static public function GetUserCombs($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT
                    id_comb
                FROM
                    ds_comb
                WHERE
                    id_userinstance = $UIID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;                
    }   
    
    static public function InsertNewInstance($userID, $worldID, $raceID, $lastRefreshDate, GB_Date $newbDefend, $league, $nubhelplevel)
    {
        assert((int)$userID > 0);
        assert((int)$raceID > 0);
        assert((int)$worldID > 0);
        assert(!empty($lastRefreshDate));
        
        $id = self::GetNextValFromSeq('ds_userinstance_id_userinstance_seq');               
                        
        $sql = "INSERT INTO ds_userinstance
                (id_user, id_race, id_world, id_userinstance, lastrefresh, newb_defend_enddate, id_league, nubhelplevel)
                VALUES
                (".(int)$userID.", ".(int)$raceID.", ".(int)$worldID.", ".(int)$id.", '".$lastRefreshDate."', '".$newbDefend->getPgFormat()."', $league, ".(int)$nubhelplevel.")";
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
        
        return $id;
    }
    
    static public function InsertMessageScope($UIID, $scopeID)
    {
        assert((int)$UIID > 0);
        assert((int)$scopeID > 0);      
        
        $sql = "INSERT INTO ds_userallowedmessagescope
                (id_userinstance, id_messagescope)
                VALUES
                ($UIID, $scopeID)";     
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));     
    }
    
    static public function InsertUserScience($UIID, $scienceID, $inResearch)
    {
        assert((int)$UIID > 0);
        assert((int)$scienceID > 0);        

        if($inResearch == 't')
        {
            $inResearchTmp = 'TRUE';
        }else{
            $inResearchTmp = 'FALSE';
        }
        
        $sql = "INSERT INTO ds_userhasscience
                (id_science, id_userinstance, inresearch)
                VALUES
                (".(int)$scienceID.", ".(int)$UIID.", $inResearchTmp)"; 
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));     
    }   
    
    static public function UpdateEndOfUserScience($UIID, $scienceID, $startTime, $endTime)
    {
        settype($UIID, 'integer');
        assert((int)$UIID > 0);
        
        settype($scienceID, 'integer');
        assert((int)$scienceID > 0);        
                        
        assert(!empty($startTime));
        assert(!empty($endTime));
        
        $sql = "UPDATE ds_userhasscience
                SET 
                    inresearch = true,
                    starttime = '$startTime',
                    endtime = '$endTime'
                WHERE 
                    id_science = $UIID
                    AND
                    id_userinstance = $scienceID";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));     
    }   
    
    static public function GetUserAbility($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT
                    *
                FROM
                    ds_userhasability
                WHERE
                    id_userinstance = $UIID";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;                
    }   
    
    static public function InsertUserAbility($UIID, $abilityID)
    {
        assert((int)$UIID > 0);
        assert((int)$abilityID > 0);        
                        
        $sql = "INSERT INTO ds_userhasability
                (id_ability, id_userinstance)
                VALUES
                (".(int)$abilityID.", ".(int)$UIID.")";     
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));     
    }
    
    static public function GetColonizeChail($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT
                    c.*
                FROM
                    ds_colony as c
                    INNER JOIN ds_comb as cfrom on (cfrom.id_comb = c.id_comb_from)
                    INNER JOIN ds_comb as cto on (cto.id_comb = c.id_comb_to)
                WHERE
                    NOT (cto.id_userinstance IS NULL)
                    AND
                    cfrom.id_userinstance = ".$UIID;
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));
        
        return $res;                
    }   
    
    static public function InsertColony($combFromID, $combToID)
    {
        assert((int)$combFromID > 0);
        assert((int)$combToID > 0);     
                        
        $sql = "INSERT INTO ds_colony
                (id_comb_from, id_comb_to)
                VALUES
                (".(int)$combFromID.", ".(int)$combToID.")";
        $res = self::$common->executeQuery ($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static public function GetColonizators($UIID)
    {
        assert((int)$UIID > 0);     
        
        $sql = "SELECT *
                FROM
                    ds_unitcontainer as uc
                WHERE
                    uc.id_unitcontainertype = 3
                    AND
                    uc.id_userinstance = ".$UIID;
        $res = self::$common->getResultArray("", $sql, "", "", "");

        assert(empty(self::$common->errormsg));
        
        return $res;                
    }

    static public function InsertColonyzators($UIID, $baseCombID, $currentCombID)
    {
        settype($UIID, 'integer');
        assert($UIID > 0);
        
        settype($baseCombID, 'integer');
        assert($baseCombID > 0);    
        
        settype($currentCombID, 'integer');
        assert($currentCombID >= 0);
        
        if($currentCombID == 0)
        {
            $currentCombID = 'NULL';
        }
        
        $sql = "INSERT INTO ds_unitcontainer
                (id_basecomb, id_unitcontainertype, id_userinstance, moveable, inaction, id_currentcomb)
                VALUES
                ($baseCombID, 3, $UIID, true, false, $currentCombID)";      
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));     
    }
    
    static public function InsertUser($userData)
    {
        assert(is_array($userData));
        
                        
        $id = self::GetNextValFromSeq('ds_user_id_user_seq');       
        
        $sql = "INSERT INTO ds_user
                (id_user, id_language, username, email, registrationdate, accountactive, password, time_delta, activate_mail)
                VALUES
                (".$id.", '".$userData['id_language']."',  '".$userData['username']."', 
                '".$userData['email']."', '".$userData['registrationdate']."', true, '".$userData['password']."', 
                '".$userData['time_delta']."', 1)"; 
        $res = self::$common->executeQuery ($sql);              
        assert(empty(self::$common->errormsg));
        
        return $id;
    }
    
    static public function EndBattles($UIID)
    {
        settype($UIID, 'integer');
        assert($UIID > 0);
        
        $sql = "
            SELECT
                return_army(id_unitcontainer)
            FROM
                ds_unitcontainer
            WHERE
                id_userinstance = $UIID
            ";
        self::$common->executeQuery($sql);              
        assert(empty(self::$common->errormsg));
    }
    
    static public function InsertVocationMode($UIID, $startDate, $endDate, $password)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(!empty($startDate));
        
        if(empty($endDate))
        {
            $endDate = 'NULL';
        } else {
            $endDate = "'$endDate'";
        }
        
        if(empty($password))
        {
            $password = 'NULL';
        } else {
            $password = "'$password'";
        }
        
        $sql = "
            INSERT INTO ds_vocationmode
                (id_userinstance, startdate, enddate, password)
            VALUES
                ($UIID, '$startDate', $endDate, $password)      
            ";
        self::$common->executeQuery ($sql);             
        assert(empty(self::$common->errormsg));
    }
    
    static public function InsertGrandBuilding($UIID, $grandBuilding)
    {
        assert(is_int($UIID) && $UIID > 0);
        assert(is_int($grandBuilding) && $grandBuilding > 0);
        
        $sql = "
            INSERT INTO 
                    ds_buildingsalowedtouserinstance
                    (id_userinstance, id_building, adddate)
                VALUES
                    (".$UIID.", ".$grandBuilding.", now())";
        self::$common->executeQuery ($sql);             
        assert(empty(self::$common->errormsg));
    }
}


?>
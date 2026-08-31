<?php $CVS_Version = '$Revision$ ; $Date$';


class DS_Economical extends DS_Base
{
    
    static public function GetBaseResourceProduction($combID, $resource)
    {
        assert(!empty($resource));
        assert(is_int($combID) && $combID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                sum(ba.value) as  production,
                count(*) as colvo               
            FROM
                ds_attachedbuilding AS ab
                INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
                INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
                INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
                INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
            WHERE
                    ab.id_comb = ".$combID."
                AND ab.active = 't'
                AND ab.underconstruction = 'f'
                AND ab.inactivation = 'f'                               
                AND att.typename = 'TURN_PRODUCTION'
                AND atn.attributename = '".$resource."'";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0];
    }
    
    static public  function GetBuildingArtResorceBonus($combID, $buildingName, $resourse, $bonusType, $scope)
    {
        assert(!empty($buildingName));
        assert(!empty($resourse));      
        assert($combID>0);
        assert($bonusType == 'BONUS_RELATIVE' || $bonusType == 'BONUS_ABSOLUTE');       
        $allowedScopes = array('COMB', 'COMBCOMPLEX', 'ALLIANCE');
        assert(in_array($scope, $allowedScopes));
        
        $common = self::getCommon();
        
        $sql = "
            SELECT DISTINCT sum(ahb.value) as rs
            FROM
                ds_artefactattachedcomb aac
                INNER JOIN ds_attachedbuilding AS ab ON (aac.id_attachedbuilding = ab.id_attachedbuilding)
                INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
                INNER JOIN ds_artefactinstance AS ai ON (aac.id_artefact = ai.id_artefactinstance)
                INNER JOIN ds_artefact AS a ON (ai.id_artefact = a.id_artefact)
                INNER JOIN ds_artefactscope AS ascop ON (a.id_artefactscope = ascop.id_artefactscope)
                INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
                INNER JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
            WHERE
                    aac.id_comb = ".$combID."        
                AND ab.active = 't'
                AND ab.underconstruction = 'f'
                AND ab.inactivation = 'f'               
                AND b.buildingname like '".$buildingName."%'
                AND atn.attributename = '".$resourse."'
                AND att.typename = '".$bonusType."'
                AND ascop.scopename = '".$scope."'";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return (float)$res[0]['rs'];
    }   
    
    static public function GetLocalArtBonusForVendorBuilding($combID, $resourse, $bonusType)
    {
        assert(!empty($resourse));
        assert($bonusType == 'BONUS_RELATIVE' || $bonusType == 'BONUS_ABSOLUTE');
        assert($combID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                sum(artb.value) as rs
            FROM
                ds_attachedbuilding AS ab
                INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)             
                INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
                INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
                INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
                INNER JOIN ds_artefactattachedbuilding AS aab ON (ab.id_attachedbuilding = aab.id_attachedbuilding)
                INNER JOIN ds_artefactinstance AS arti ON (aab.id_artefact = arti.id_artefactinstance)
                INNER JOIN ds_artefact AS art ON (arti.id_artefact = art.id_artefact)
                INNER JOIN ds_artefacthasbonus AS artb ON (art.id_artefact = artb.id_artefact)
                INNER JOIN ds_attribute AS atra ON (artb.id_attribute = atra.id_attribute)
                INNER JOIN ds_attributename AS atna ON (atra.id_attributename = atna.id_attributename)
                INNER JOIN ds_attributetype AS atta ON (atra.id_attributetype = atta.id_attributetype)
            WHERE               
                    ab.id_comb = ".$combID."                
                AND ab.active = true
                AND ab.underconstruction = false
                AND ab.inactivation = false
                AND ab.inupgrade = false                    
                AND att.typename = 'TURN_PRODUCTION'
                AND atn.attributename = '".$resourse."'
                AND atta.typename = '".$bonusType."'
                AND atna.attributename = '".$resourse."'";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
    
        return (float)$res[0]['rs'];
    }
    
    static public function GetResearchBonus($UIID)
    {
        assert(is_int($UIID) && $UIID>=0);
        
        $common = self::getCommon();
        
        $sql = "SELECT 
                    atn.attributename,  
                    s.level,
                    r.exchangerate
                FROM 
                    ds_science s,
                    ds_sciencebranch sbr,                   
                    ds_sciencebranchattributename sban,
                    ds_resource r,
                    (
                        SELECT 
                            max(s.id_science) AS id_science
                        FROM 
                            ds_userhasscience uhs,
                            ds_science s,
                            ds_sciencebranch sbr
                        WHERE 
                                uhs.id_science = s.id_science
                            AND s.id_branch = sbr.id_branch
                            AND uhs.id_userinstance = ".$UIID."
                            AND uhs.inresearch = false
                        GROUP BY sbr.id_branch
                    ) t,
                    ds_attributename atn
                WHERE 
                        s.id_science = t.id_science
                    AND s.id_branch = sbr.id_branch
                    AND sbr.id_branch = sban.id_sciencebranch
                    AND sban.id_attributename = r.id_attributename
                    AND atn.id_attributename = sban.id_attributename";               
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetComplexArtefactResourceBonus($combID, $resourse, $bonusType)
    {
        assert(!empty($resourse));
        assert($bonusType == 'BONUS_RELATIVE' || $bonusType == 'BONUS_ABSOLUTE');
        assert($combID>0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                sum(ahb.value) as rs
            FROM
                ds_artefactattachedcomplex AS aacc
                INNER JOIN ds_artefactinstance AS ai ON (aacc.id_artefact = ai.id_artefactinstance)
                INNER JOIN ds_artefact AS a ON (ai.id_artefact = a.id_artefact)
                INNER JOIN ds_artefactscope AS ascop ON (a.id_artefactscope = ascop.id_artefactscope)
                INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
                INNER JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
            WHERE
                    atn.attributename = '".$resourse."'
                AND att.typename = '".$bonusType."' 
                AND aacc.id_combcomplex = (
                    SELECT tc.id_combcomplex
                    FROM ds_comb tc
                    WHERE tc.id_comb = ".$combID."
                )";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0]['rs'];
    }
    
    static public function GetAlianceArtefactResourceBonus($UIID, $resourse, $bonusType)
    {
        assert(!empty($resourse));
        assert($bonusType == 'BONUS_RELATIVE' || $bonusType == 'BONUS_ABSOLUTE');
        assert($UIID>=0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                sum(ahb.value) as rs
            FROM
                ds_artefactattachedalliance AS aacc
                INNER JOIN ds_artefactinstance AS ai ON (aacc.id_artefact = ai.id_artefactinstance)
                INNER JOIN ds_artefact AS a ON (ai.id_artefact = a.id_artefact)
                INNER JOIN ds_artefactscope AS ascop ON (a.id_artefactscope = ascop.id_artefactscope)
                INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
                INNER JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
            WHERE
                    atn.attributename = '".$resourse."'
                AND att.typename = '".$bonusType."' 
                AND aacc.id_alliance = (
                    SELECT tc.id_alliance
                    FROM ds_userhasalliance tc
                    WHERE tc.id_userinstance = ".$UIID."
                )";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0]['rs'];
    }
    
    static public function GetReligionResourceBonus($UIID, $resourse, $bonusType)
    {
        assert(!empty($resourse));
        assert($bonusType == 'BONUS_RELATIVE' || $bonusType == 'BONUS_ABSOLUTE');
        assert($UIID>=0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                sum(ra.value) as rs
            FROM
                ds_userhasreligion AS uhr
                INNER JOIN ds_religion AS r ON (uhr.id_religion = r.id_religion)
                INNER JOIN ds_religionhasattribute AS ra ON (r.id_religion = ra.id_religion)
                INNER JOIN ds_attribute AS a ON (ra.id_attribute = a.id_attribute)
                INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
            WHERE
                    uhr.id_userinstance = ".$UIID."
                AND at.typename = '".$bonusType."'
                AND an.attributename = '".$resourse."'";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0]['rs'];
    }
    
    static public function GetRaceResourceBonus ($UIID, $resourse, $bonusType)
    {
        assert(!empty($resourse));
        assert($bonusType == 'BONUS_RELATIVE' || $bonusType == 'BONUS_ABSOLUTE');
        assert($UIID>=0);
        
        $common = self::getCommon();
        
        $raceID = DE_UserInstance::CreateFromID($UIID)->getRaceID();
        
        $sql = "
            SELECT
                sum(ra.value) as rs
            FROM
                ds_race AS r 
                INNER JOIN ds_racehasattribute AS ra ON (r.id_race = ra.id_race)
                INNER JOIN ds_attribute AS a ON (ra.id_attribute = a.id_attribute)
                INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
            WHERE
                r.id_race = ".$raceID."
                AND an.attributename = '".$resourse."'
                AND at.typename = '".$bonusType."'";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0]['rs'];
    }
    
    static public function GetResourceProductionCost ($combID, $resource, $price)
    {
        assert($combID > 0);
        assert(!empty($resource));
        assert(!empty($price));
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                  sum(ba.value) as rs                           
            FROM
                ds_attachedbuilding AS ab
                INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
                INNER JOIN ds_buildingtype AS bt ON (b.id_buildingtype = bt.id_buildingtype)
                INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
                INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
                INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
            WHERE
                    ab.id_comb = ".$combID."
                
                AND bt.type = 'RES'
                AND ab.active = 't'
                AND ab.underconstruction = 'f'
                AND ab.inactivation = 'f' 
                AND att.typename = 'TURN_UPKEEP'
                AND atn.attributename = '".$price."'
                AND ab.id_attachedbuilding in (
                    SELECT
                       ab2.id_attachedbuilding                      
                    FROM
                       ds_attachedbuilding AS ab2
                       INNER JOIN ds_building AS b2 ON (ab2.id_building = b2.id_building)
                       INNER JOIN ds_buildingtype AS bt2 ON (b2.id_buildingtype = bt2.id_buildingtype)
                       INNER JOIN ds_buildinghasattribute as ba2 ON (b2.id_building = ba2.id_building)
                       INNER JOIN ds_attribute AS atr2 ON (ba2.id_attribute = atr2.id_attribute)
                       INNER JOIN ds_attributename AS atn2 ON (atr2.id_attributename = atn2.id_attributename)
                       INNER JOIN ds_attributetype AS att2 ON (atr2.id_attributetype = att2.id_attributetype)
                     WHERE
                          ab2.id_comb = ".$combID."
                          AND bt2.type = 'RES'
                          AND att2.typename = 'TURN_PRODUCTION'
                          AND atn2.attributename = '".$resource."'
                        
                )";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0]['rs'];
    }
    
    static public function GetSimplBuildingsCost ($combID, $resource)
    {
        assert($combID > 0);
        assert(!empty($resource));      
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                  sum(ba.value) as rs                           
            FROM
                ds_attachedbuilding AS ab
                INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
                INNER JOIN ds_buildingtype AS bt ON (b.id_buildingtype = bt.id_buildingtype)
                INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
                INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
                INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
            WHERE
                    ab.id_comb = ".$combID."
                AND ab.active = 't'
                AND ab.underconstruction = 'f'
                AND ab.inactivation = 'f'         
                AND bt.type != 'RES'
                AND att.typename = 'TURN_UPKEEP'
                AND atn.attributename = '".$resource."'";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res[0]['rs'];
    }
    
    static public function GetCombBuildingsTurnUpkeep($combID)
    {
        assert($combID > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                atn.attributename,
                sum(ba.value) as upkeep                         
            FROM
                ds_attachedbuilding AS ab
                INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
                INNER JOIN ds_buildingtype AS bt ON (b.id_buildingtype = bt.id_buildingtype)
                INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
                INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
                INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
            WHERE
                    ab.id_comb = ".$combID."                                
                AND ab.active = 't'
                AND ab.underconstruction = 'f'
                AND ab.inactivation = 'f'
                AND att.typename = 'TURN_UPKEEP'    
            GROUP BY atn.attributename";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function GetUserBonus(DE_Userinstance $userInstance, $resourse)
    {
        assert(!empty($resourse));
        
        $common = self::getCommon();
                
        $sql = "
            SELECT
                sum(COALESCE(uhb.value,0)) as rs
            FROM
                ds_userhasbonus AS uhb
                INNER JOIN ds_attribute AS a ON (uhb.id_attribute = a.id_attribute)
                INNER JOIN ds_attributename AS an ON (a.id_attributename = an.id_attributename)
                INNER JOIN ds_attributetype AS at ON (a.id_attributetype = at.id_attributetype)
            WHERE
                uhb.id_userinstance = ".$userInstance->getID()."
                AND an.attributename = '".$resourse."'
                AND at.typename = 'BONUS_RELATIVE'
                AND uhb.start_date < now()
                AND uhb.end_date > now()
                ";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));           

        return (float)$res[0]['rs'];
    }
}
?>
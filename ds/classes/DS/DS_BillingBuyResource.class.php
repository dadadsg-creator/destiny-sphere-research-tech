<?php

class DS_BillingBuyResource extends DS_Base 
{
    static public function getUIIDByUIDAndWorldName($uid, $worldname)
    {
        assert(is_int($uid) && $uid > 0);
        assert(!empty($worldname));
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                wc.dbname,
                wc.id_world,
                uic.id_userinstance
            FROM
                ds_userinstancecollection AS uic
                INNER JOIN ds_worldcollection AS wc USING (id_worldcollection)
            WHERE
                uic.id_user = $uid
                AND
                wc.worldname = E'".pg_escape_string($worldname)."'
            ";
        
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function getCombID($dbID, $UIID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($UIID) && $UIID > 0);
        
        $common = self::getCommon();
        $common->setDbId($dbID);
        
        $sql = "
            SELECT
                id_comb
            FROM
                ds_comb AS c
                INNER JOIN ds_combcomplex AS cc USING (id_combcomplex)
                INNER JOIN ds_ring AS r ON (r.id_ring = cc.id_ring)
            WHERE
                id_userinstance = ".$UIID."
                AND
                r.ringname != 'RING_COLONIZE'
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return (int)$res[0]['id_comb'];
    }
    
    static public function getUserBuyResourceCount($userID, $worldID)
    {
        assert(is_int($userID) && $userID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        
        $common = self::getCommon();
        
        $sql = "
            SELECT
                ubr.count,
                ubr.id_attribute,
                an.attributename
            FROM
                ds_user_buy_resource_count AS ubr
                INNER JOIN ds_attribute AS at USING (id_attribute)
                INNER JOIN ds_attributename AS an ON (an.id_attributename = at.id_attributename)
            WHERE
                ubr.id_user = $userID
                AND
                ubr.id_world = $worldID
            ";
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res;
    }
    
    static public function insertUserBuyResource(DE_UserBoughtResources $buyResource)
    {
        $common = self::getCommon();
        
        $sql = "
                INSERT INTO ds_user_buy_resource_count
                    (id_user, id_world, id_attribute, count)
                SELECT
                    ".$buyResource->getUserId().",
                    ".$buyResource->getWorldId().",
                    at.id_attribute,
                    0
                FROM
                    ds_attribute AS at
                    INNER JOIN ds_attributetype AS att USING (id_attributetype)
                    INNER JOIN ds_attributename AS an USING (id_attributename)
                WHERE
                    att.typename = 'CURRENT_AMOUNT'
                    AND
                    an.attributename IN ('".implode("','", GB_ResourcesList::$allResources)."')
                ";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function commitUserBuyResource(DE_UserBoughtResources $buyResource)
    {
        $common = self::getCommon();
        
        if($buyResource->needCreation())
        {
            self::insertUserBuyResource($buyResource);
        }
        
        foreach(GB_ResourcesList::$allResources as $k => $res)
        {
            $sql = "
                UPDATE
                    ds_user_buy_resource_count
                SET
                    count = ".$buyResource->get($res)."
                WHERE
                    id_user = ".$buyResource->getUserId()."
                    AND
                    id_world = ".$buyResource->getWorldId()."
                    AND
                    id_attribute = ".$buyResource->getAttrID($res)."
                ";
            $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }
    }
}

?>
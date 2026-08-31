<?php
class SMS_DataDase
{
    private $common;
    
    public static function GetCountries()
    {
        $common = DS_Common::CreateForCurentDB(0);
        
        $sql = "SELECT
                        *
                    FROM
                    ds_sms_countries
                WHERE 
                    is_active = 't'
                ORDER BY  id_country";
        
        $res = $common->getResultArray('', $sql);
        echo $common->errormsg;
        assert(empty($common->errormsg));
        
        return $res;
    }   
    
    public static function GetUserWorlds(DE_User $user)
    {
        $common = DS_Common::CreateForCurentDB(0);
        
        $sql = 'SELECT
                        wc.id_world,
                        wc.worldname,
                        wc.dbname,
                        wc.world_identifier

                    FROM
                        ds_userinstancecollection uic
                    INNER JOIN ds_worldcollection wc ON (wc.id_worldcollection = uic.id_worldcollection)
                    WHERE
                    
                                             uic.id_user = '.$user->getID();
        
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        
        return $res;
    }

    public static function GetUserCountry(DE_User $user, $active = false)
    {
        $common = DS_Common::CreateForCurentDB(0);

        $sql = " SELECT
                        *
                    FROM
                        ds_sms_user_country suc
                    LEFT JOIN ds_sms_countries sc ON (sc.id_country = suc.id_country)
                    WHERE
                        suc.id_user = ".$user->getID()."";

        if ($active) {
            $sql  .= " AND sc.is_active = 't'";
        }
        
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        return $res[0]['id_country'];
    }

    public static function SetUserCountry($userId, $countryId)
    {       
        $common = DS_Common::CreateForCurentDB(0);
        assert($countryId > 0);
        assert($userId > 0);

        $sql = "INSERT INTO
                            ds_sms_user_country
                            (id_country, id_user)
                        VALUES
                            ($countryId, $userId)";
        
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        
        return;
    }
        
    public static function UpdateUserCountry($userId, $countryId)
    {
        $common = DS_Common::CreateForCurentDB(0);
        assert($countryId > 0);
        assert($userId > 0);

        $sql = "UPDATE
                        ds_sms_user_country
                    SET
                        id_country = $countryId
                    WHERE
                        id_user = $userId";
        
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        
        return;
    }
        
    public static function GetWorldIdentifierById($dbId, $world)
    {
        assert($dbId >= 0);
        assert($world > 0);
        
        $common = DS_Common::CreateForCurentDB($dbId);

        $sql = "SELECT
                       w.world_identifier
                       FROM
                       ds_world w
                       WHERE
                       w.id_world = $world";
        
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));
        return $res[0]['world_identifier'];
    }
    
    public static function GetUserCombsByWorld(DE_User $user, $dbId, $worldId)
    {
        assert($dbId >= 0);
        assert($worldId > 0);
        
        $common = DS_Common::CreateForCurentDB($dbId);
        
        $sql = 'SELECT
                  ui.id_world,
                  c.combnumber,
                  cc.complexnumber,
                  r.ringnumber
                FROM ds_userinstance as ui
                INNER JOIN ds_comb c ON (c.id_userinstance = ui.id_userinstance)
                LEFT JOIN ds_combcomplex cc ON (cc.id_combcomplex = c.id_combcomplex)
                LEFT JOIN ds_ring r ON (r.id_ring = cc.id_ring)

                    WHERE
                    ui.id_user = '.$user->getID().'
                    AND
                  ui.id_world = '.$worldId;

        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        return $res;
    }
}

?>